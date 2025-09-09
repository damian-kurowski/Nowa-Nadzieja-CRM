<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;

/**
 * @extends AbstractType<User>
 */
class ProfilType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Basic information
            ->add('email', EmailType::class, [
                'label' => 'Adres email',
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('imie', TextType::class, [
                'label' => 'Imię',
                'attr' => ['class' => 'form-control', 'readonly' => true],
                'required' => true,
                'disabled' => true,
            ])
            ->add('drugieImie', TextType::class, [
                'label' => 'Drugie imię',
                'attr' => ['class' => 'form-control', 'readonly' => true],
                'required' => false,
                'disabled' => true,
            ])
            ->add('nazwisko', TextType::class, [
                'label' => 'Nazwisko',
                'attr' => ['class' => 'form-control', 'readonly' => true],
                'required' => true,
                'disabled' => true,
            ])
            ->add('pesel', TextType::class, [
                'label' => 'PESEL',
                'attr' => [
                    'class' => 'form-control',
                    'readonly' => true,
                    'placeholder' => '12345678901',
                    'maxlength' => 11,
                    'pattern' => '[0-9]{11}',
                ],
                'required' => false,
                'help' => '11 cyfr, dane osobowe nie podlegają edycji',
                'disabled' => true,
            ])
            ->add('dataUrodzenia', DateType::class, [
                'label' => 'Data urodzenia',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control', 'readonly' => true],
                'required' => false,
                'disabled' => true,
            ])
            ->add('plec', ChoiceType::class, [
                'label' => 'Płeć',
                'attr' => ['class' => 'form-select', 'disabled' => true],
                'choices' => [
                    'Wybierz...' => '',
                    'Kobieta' => 'kobieta',
                    'Mężczyzna' => 'mężczyzna',
                    'Inna' => 'inna',
                ],
                'required' => false,
                'disabled' => true,
            ])
            
            // Contact information
            ->add('telefon', TelType::class, [
                'label' => 'Telefon',
                'attr' => ['class' => 'form-control', 'placeholder' => '+48 123 456 789'],
                'required' => false,
            ])
            ->add('adresZamieszkania', TextareaType::class, [
                'label' => 'Adres zamieszkania',
                'attr' => ['class' => 'form-control', 'rows' => 3],
                'required' => false,
            ])
            
            // Professional information
            ->add('zatrudnienieSpolki', TextareaType::class, [
                'label' => 'Zatrudnienie w spółkach',
                'attr' => ['class' => 'form-control', 'rows' => 3],
                'required' => false,
                'help' => 'Informacje o zatrudnieniu w spółkach prawa handlowego',
            ])
            ->add('zatrudnienieSpolkiMiejskie', TextareaType::class, [
                'label' => 'Zatrudnienie w spółkach miejskich',
                'attr' => ['class' => 'form-control', 'rows' => 2],
                'required' => false,
            ])
            ->add('zatrudnienieSpolkiSkarbuPanstwa', TextareaType::class, [
                'label' => 'Zatrudnienie w spółkach Skarbu Państwa',
                'attr' => ['class' => 'form-control', 'rows' => 2],
                'required' => false,
            ])
            ->add('zatrudnienieSpolkiKomunalne', TextareaType::class, [
                'label' => 'Zatrudnienie w spółkach komunalnych',
                'attr' => ['class' => 'form-control', 'rows' => 2],
                'required' => false,
            ])
            
            // CV upload
            ->add('cvFile', FileType::class, [
                'label' => 'CV (PDF)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.pdf',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Proszę wgrać plik PDF',
                    ])
                ],
                'help' => 'Maksymalny rozmiar: 5MB, format: PDF',
            ])
            
            // Social media and personal information
            ->add('socialMedia', TextareaType::class, [
                'label' => 'Social Media (linki)',
                'attr' => ['class' => 'form-control', 'rows' => 2, 'placeholder' => 'Facebook, Twitter, LinkedIn...'],
                'required' => false,
            ])
            ->add('informacjeOmnie', TextareaType::class, [
                'label' => 'Informacje o mnie',
                'attr' => ['class' => 'form-control', 'rows' => 4],
                'required' => false,
            ])
            ->add('historiaWyborow', TextareaType::class, [
                'label' => 'Historia startów w wyborach',
                'attr' => ['class' => 'form-control', 'rows' => 3],
                'required' => false,
                'help' => 'Informacje o poprzednich startach wyborczych',
            ])
            ->add('dodatkoweInformacje', TextareaType::class, [
                'label' => 'Dodatkowe informacje',
                'attr' => ['class' => 'form-control', 'rows' => 3],
                'required' => false,
            ])
            
            // Password change
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'first_options' => [
                    'label' => 'Nowe hasło',
                    'attr' => ['class' => 'form-control'],
                ],
                'second_options' => [
                    'label' => 'Powtórz nowe hasło',
                    'attr' => ['class' => 'form-control'],
                ],
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Hasło musi mieć co najmniej {{ limit }} znaków',
                        'max' => 4096,
                    ]),
                ],
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
