<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\WystepMedialny;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<WystepMedialny>
 */
class WystepMedialnyType extends AbstractType
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dataIGodzina', DateTimeType::class, [
                'label' => 'Data i godzina wydarzenia',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('nazwaMediaRedakcji', TextType::class, [
                'label' => 'Nazwa media/redakcji/stacji/portalu',
                'required' => true,
            ])
            ->add('nazwaProgramu', TextType::class, [
                'label' => 'Nazwa programu/audycji',
                'required' => false,
            ])
            ->add('tematyRozmowy', TextareaType::class, [
                'label' => 'Tematy rozmowy/wywiadu',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('linkDoNagrania', UrlType::class, [
                'label' => 'Link do nagrania (opcjonalnie)',
                'required' => false,
            ])
            ->add('dziennikarzProwadzacy', TextType::class, [
                'label' => 'Dziennikarz prowadzący',
                'required' => false,
            ])
            ->add('numerTelefonu', TelType::class, [
                'label' => 'Numer telefonu do osoby odpowiedzialnej/kontaktowej',
                'required' => false,
            ])
            ->add('mowcy', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getFullName().' ('.$user->getEmail().')';
                },
                'multiple' => true,
                'expanded' => false,
                'label' => 'Mówcy/osoby występujące',
                'attr' => ['class' => 'select2'],
                'required' => false,
                'choices' => $this->getMowcyChoices(),
            ]);
    }

    /**
     * @return array<int, User|null>
     */
    private function getMowcyChoices(): array
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            return [];
        }
        $conn = $this->entityManager->getConnection();

        // Build the base query for members with ROLE_CZLONEK_PARTII or higher roles
        $sql = "
            SELECT u.* FROM \"user\" u
            WHERE (u.roles::jsonb @> '[\"ROLE_CZLONEK_PARTII\"]'::jsonb 
                   OR u.roles::jsonb @> '[\"ROLE_FUNKCYJNY\"]'::jsonb
                   OR u.roles::jsonb @> '[\"ROLE_PREZES_PARTII\"]'::jsonb
                   OR u.roles::jsonb @> '[\"ROLE_SEKRETARZ_PARTII\"]'::jsonb)
        ";

        $params = [];

        // Apply restrictions based on user roles
        if ($this->security->isGranted('ROLE_ZARZAD_KRAJOWY') || $this->security->isGranted('ROLE_ADMIN')) {
            // Zarząd krajowy i admini widzą wszystkich
            // No additional restrictions
        } elseif ($this->security->isGranted('ROLE_ZARZAD_OKREGU')) {
            // Zarząd okręgu widzi osoby ze swojego okręgu + siebie
            if ($currentUser->getOkreg()) {
                $sql .= ' AND (u.okreg_id = :okreg_id OR u.id = :current_user_id)';
                $params['okreg_id'] = $currentUser->getOkreg()->getId();
                $params['current_user_id'] = $currentUser->getId();
            }
        } elseif ($this->security->isGranted('ROLE_ZARZAD_ODDZIALU')) {
            // Zarząd oddziału widzi osoby ze swojego oddziału + siebie
            if ($currentUser->getOddzial()) {
                $sql .= ' AND (u.oddzial_id = :oddzial_id OR u.id = :current_user_id)';
                $params['oddzial_id'] = $currentUser->getOddzial()->getId();
                $params['current_user_id'] = $currentUser->getId();
            }
        } elseif ($this->security->isGranted('ROLE_FUNKCYJNY')) {
            // Inni funkcyjni widzą osoby ze swojego okręgu + siebie
            if ($currentUser->getOkreg()) {
                $sql .= ' AND (u.okreg_id = :okreg_id OR u.id = :current_user_id)';
                $params['okreg_id'] = $currentUser->getOkreg()->getId();
                $params['current_user_id'] = $currentUser->getId();
            }
        } elseif ($this->security->isGranted('ROLE_CZLONEK_PARTII')) {
            // Zwykły członek widzi tylko osoby ze swojego okręgu + siebie
            if ($currentUser->getOkreg()) {
                $sql .= ' AND (u.okreg_id = :okreg_id OR u.id = :current_user_id)';
                $params['okreg_id'] = $currentUser->getOkreg()->getId();
                $params['current_user_id'] = $currentUser->getId();
            }
        }

        $sql .= ' ORDER BY u.nazwisko ASC, u.imie ASC';

        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery($params);
        $usersData = $resultSet->fetchAllAssociative();

        // Convert to User entities
        $userRepository = $this->entityManager->getRepository(User::class);
        $users = [];
        foreach ($usersData as $userData) {
            $users[] = $userRepository->find($userData['id']);
        }

        // Ensure current user is always included if they can be a speaker
        if ($this->security->isGranted('ROLE_CZLONEK_PARTII')
                           || $this->security->isGranted('ROLE_FUNKCYJNY')
                           || $this->security->isGranted('ROLE_PREZES_PARTII')
                           || $this->security->isGranted('ROLE_SEKRETARZ_PARTII')) {
            $currentUserIncluded = false;
            foreach ($users as $user) {
                if ($user && $user->getId() === $currentUser->getId()) {
                    $currentUserIncluded = true;
                    break;
                }
            }

            // If current user not found in the list, add them
            if (!$currentUserIncluded) {
                array_unshift($users, $currentUser); // Add at beginning
            }
        }

        // Final safety check - make sure current user is absolutely always included
        $currentUserFound = false;
        foreach ($users as $user) {
            if ($user && $user->getId() === $currentUser->getId()) {
                $currentUserFound = true;
                break;
            }
        }

        if (!$currentUserFound
            && ($this->security->isGranted('ROLE_CZLONEK_PARTII')
             || $this->security->isGranted('ROLE_FUNKCYJNY')
             || $this->security->isGranted('ROLE_PREZES_PARTII')
             || $this->security->isGranted('ROLE_SEKRETARZ_PARTII'))) {
            array_unshift($users, $currentUser);
        }

        return $users;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WystepMedialny::class,
        ]);
    }
}
