<?php

namespace App\Form;

use App\Entity\Oddzial;
use App\Entity\Okreg;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/**
 * @extends AbstractType<User>
 */
class CzlonekType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('imie', TextType::class, [
                'label' => 'Imię',
                'required' => true,
                'attr' => ['readonly' => true],
                'disabled' => true,
            ])
            ->add('drugieImie', TextType::class, [
                'label' => 'Drugie imię',
                'required' => false,
                'attr' => ['readonly' => true],
                'disabled' => true,
            ])
            ->add('nazwisko', TextType::class, [
                'label' => 'Nazwisko',
                'required' => true,
                'attr' => ['readonly' => true],
                'disabled' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
            ])
            ->add('pesel', TextType::class, [
                'label' => 'PESEL',
                'required' => false,
                'attr' => ['readonly' => true],
                'disabled' => true,
            ])
            ->add('dataUrodzenia', DateType::class, [
                'label' => 'Data urodzenia',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['readonly' => true],
                'disabled' => true,
            ])
            ->add('plec', ChoiceType::class, [
                'label' => 'Płeć',
                'choices' => [
                    'Kobieta' => 'kobieta',
                    'Mężczyzna' => 'mężczyzna',
                    'Inne' => 'inne',
                ],
                'required' => false,
                'attr' => ['disabled' => true],
                'disabled' => true,
            ])
            ->add('telefon', TelType::class, [
                'label' => 'Telefon',
                'required' => false,
            ])
            ->add('adresZamieszkania', TextareaType::class, [
                'label' => 'Adres zamieszkania',
                'required' => false,
            ])
            ->add('okreg', EntityType::class, [
                'class' => Okreg::class,
                'choice_label' => 'nazwa',
                'label' => 'Okręg',
                'required' => true,
            ])
            ->add('oddzial', EntityType::class, [
                'class' => Oddzial::class,
                'choice_label' => 'nazwa',
                'label' => 'Oddział',
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Aktywny' => 'aktywny',
                    'Nieaktywny' => 'nieaktywny',
                    'Zawieszony' => 'zawieszony',
                ],
            ])
            ->add('zatrudnienieSpolki', TextareaType::class, [
                'label' => 'Zatrudnienie w spółkach miejskich/Skarbu Państwa',
                'required' => false,
            ])
            ->add('socialMedia', TextareaType::class, [
                'label' => 'Social Media (JSON)',
                'required' => false,
                'help' => 'Format: {"facebook": "link", "twitter": "link"}',
            ])
            ->add('informacjeOmnie', TextareaType::class, [
                'label' => 'Informacje o mnie',
                'required' => false,
            ])
            ->add('zdjecie', FileType::class, [
                'label' => 'Zdjęcie',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Proszę wybrać prawidłowy format zdjęcia (JPEG/PNG)',
                    ]),
                ],
            ])
            ->add('cv', FileType::class, [
                'label' => 'CV',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ],
                        'mimeTypesMessage' => 'Proszę wybrać prawidłowy format CV (PDF/DOC/DOCX)',
                    ]),
                ],
            ])
            ->add('dodatkoweInformacje', TextareaType::class, [
                'label' => 'Dodatkowe informacje',
                'required' => false,
            ])
            ->add('notatkaWewnetrzna', TextareaType::class, [
                'label' => 'Notatka wewnętrzna',
                'required' => false,
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
