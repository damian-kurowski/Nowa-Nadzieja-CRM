<?php

namespace App\Form;

use App\Entity\UmowaZlecenia;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UmowaZleceniaType extends AbstractType
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numerUmowy', TextType::class, [
                'label' => 'Numer umowy',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'np. UZ/2024/01/001 (zostanie wygenerowany automatycznie)',
                    'readonly' => true,
                ],
                'required' => false,
                'help' => 'Numer zostanie wygenerowany automatycznie po zapisaniu'
            ])
            ->add('zleceniobiorca', EntityType::class, [
                'label' => 'Zleceniobiorca (osoba wykonująca zlecenie)',
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return sprintf('%s %s (%s)', 
                        $user->getImie(), 
                        $user->getNazwisko(), 
                        $user->getEmail()
                    );
                },
                'query_builder' => function () {
                    return $this->userRepository->createQueryBuilder('u')
                        ->orderBy('u.nazwisko', 'ASC')
                        ->addOrderBy('u.imie', 'ASC');
                },
                'attr' => [
                    'class' => 'form-select',
                ],
                'placeholder' => 'Wybierz osobę...',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Zleceniobiorca jest wymagany']),
                ],
                'help' => 'Osoba, która będzie wykonywać zlecenie'
            ])
            ->add('sekretarzPartii', EntityType::class, [
                'label' => 'Sekretarz partii (opcjonalnie)',
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return sprintf('%s %s', $user->getImie(), $user->getNazwisko());
                },
                'choices' => $this->getSecretaries(),
                'attr' => [
                    'class' => 'form-select',
                ],
                'placeholder' => 'Wybierz sekretarza partii...',
                'required' => false,
                'help' => 'Jeśli nie wybierzesz, dane zostaną pobrane automatycznie z systemu'
            ])
            ->add('zakresUmowy', ChoiceType::class, [
                'label' => 'Zakres umowy (kategoria)',
                'choices' => UmowaZlecenia::getZakresChoices(),
                'attr' => [
                    'class' => 'form-select',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Zakres umowy jest wymagany']),
                ],
                'placeholder' => 'Wybierz zakres...'
            ])
            ->add('opisZakresu', TextareaType::class, [
                'label' => 'Szczegółowy opis zakresu prac',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Opisz szczegółowo, jakie prace będą wykonywane w ramach umowy...',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Szczegółowy opis zakresu jest wymagany']),
                    new Assert\Length([
                        'min' => 10,
                        'max' => 2000,
                        'minMessage' => 'Opis zakresu musi mieć co najmniej {{ limit }} znaków',
                        'maxMessage' => 'Opis zakresu nie może być dłuższy niż {{ limit }} znaków'
                    ])
                ],
                'help' => 'Opisz dokładnie, jakie usługi będą świadczone (min. 10 znaków)'
            ])
            ->add('typOkresu', ChoiceType::class, [
                'label' => 'Typ okresu obowiązywania',
                'choices' => UmowaZlecenia::getTypOkresuChoices(),
                'attr' => [
                    'class' => 'form-select',
                    'data-toggle' => 'period-type',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Typ okresu jest wymagany']),
                ],
                'help' => 'Wybierz czy umowa ma okres określony czy nieokreślony'
            ])
            ->add('dataOd', DateType::class, [
                'label' => 'Data rozpoczęcia umowy',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'min' => (new \DateTime())->format('Y-m-d'),
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Data rozpoczęcia jest wymagana']),
                    new Assert\GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'Data rozpoczęcia nie może być w przeszłości'
                    ])
                ],
                'help' => 'Data od kiedy umowa ma obowiązywać'
            ])
            ->add('dataDo', DateType::class, [
                'label' => 'Data zakończenia umowy (tylko dla okresu określonego)',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'data-period-end' => 'true',
                ],
                'required' => false,
                'help' => 'Data do kiedy umowa ma obowiązywać (tylko dla okresu określonego)'
            ])
            ->add('wynagrodzenie', MoneyType::class, [
                'label' => 'Wynagrodzenie',
                'currency' => 'PLN',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0,00',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Wynagrodzenie jest wymagane']),
                    new Assert\Positive(['message' => 'Wynagrodzenie musi być większe od zera'])
                ],
                'help' => 'Łączne wynagrodzenie za wykonanie zlecenia'
            ])
            ->add('pobranieKontaZSkladek', CheckboxType::class, [
                'label' => 'Pobierz numer konta z danych składkowych',
                'attr' => [
                    'class' => 'form-check-input',
                    'data-toggle' => 'account-source',
                ],
                'required' => false,
                'help' => 'Jeśli zaznaczone, numer konta zostanie pobrany z systemu składek'
            ])
            ->add('numerKonta', TextType::class, [
                'label' => 'Numer konta bankowego zleceniobiorcy',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'PL12 3456 7890 1234 5678 9012 3456',
                    'maxlength' => 34,
                    'data-account-manual' => 'true',
                ],
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'min' => 26,
                        'max' => 34,
                        'minMessage' => 'Numer konta musi mieć co najmniej {{ limit }} znaków',
                        'maxMessage' => 'Numer konta nie może być dłuższy niż {{ limit }} znaków'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[A-Z]{2}[0-9]{2}[\s0-9]*$/',
                        'message' => 'Numer konta musi być w formacie IBAN'
                    ])
                ],
                'help' => 'Numer konta w formacie IBAN, na który będzie wypłacane wynagrodzenie'
            ])
            ->add('czyStudent', CheckboxType::class, [
                'label' => 'Zleceniobiorca jest studentem (nie ukończył 26 lat)',
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'required' => false,
                'help' => 'Zaznacz jeśli zleceniobiorca jest studentem w wieku do 26 lat (wpływa na składki)'
            ])
            ->add('uwagi', TextareaType::class, [
                'label' => 'Uwagi dodatkowe (opcjonalnie)',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Dodatkowe uwagi lub informacje dotyczące umowy...',
                ],
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 1000,
                        'maxMessage' => 'Uwagi nie mogą być dłuższe niż {{ limit }} znaków'
                    ])
                ],
                'help' => 'Opcjonalne uwagi lub dodatkowe informacje'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Utwórz umowę zlecenia',
                'attr' => [
                    'class' => 'btn btn-primary btn-lg',
                ],
            ]);

        // Add form event listener to handle dynamic fields
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    public function onPreSetData(FormEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();

        if ($data && $data->getId()) {
            // Editing existing contract - show current status
            $form->add('status', ChoiceType::class, [
                'label' => 'Status umowy',
                'choices' => UmowaZlecenia::getStatusChoices(),
                'attr' => [
                    'class' => 'form-select',
                ],
                'help' => 'Aktualny status umowy'
            ]);
        }
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();

        // Validate dataDo based on typOkresu
        if (isset($data['typOkresu']) && $data['typOkresu'] === UmowaZlecenia::TYP_OKRESU_OD_DO) {
            if (empty($data['dataDo'])) {
                // Add validation error - this will be handled by form validation
            }
        }

        // Handle account number logic
        if (isset($data['pobranieKontaZSkladek']) && $data['pobranieKontaZSkladek']) {
            // If getting account from contributions, clear manual input
            $data['numerKonta'] = '';
            $event->setData($data);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UmowaZlecenia::class,
        ]);
    }

    private function getSecretaries(): array
    {
        $allUsers = $this->userRepository->findAll();
        $secretaries = [];
        
        foreach ($allUsers as $user) {
            $roles = $user->getRoles();
            if (in_array('ROLE_SEKRETARZ', $roles) || in_array('ROLE_SEKRETARZ_PARTII', $roles)) {
                $secretaries[] = $user;
            }
        }
        
        // Sort by last name
        usort($secretaries, function($a, $b) {
            return strcmp($a->getNazwisko(), $b->getNazwisko());
        });
        
        return $secretaries;
    }
}