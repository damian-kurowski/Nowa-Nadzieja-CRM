<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Region;
use App\Entity\Oddzial;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class KandydatApiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Dane podstawowe
            ->add('imie', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(message: 'Imię jest wymagane'),
                    new Assert\Length(min: 2, max: 255, minMessage: 'Imię musi mieć co najmniej {{ limit }} znaki', maxMessage: 'Imię nie może być dłuższe niż {{ limit }} znaków'),
                    new Assert\Regex(pattern: '/^[\p{L}\s-]+$/u', message: 'Imię może zawierać tylko litery, spacje i myślniki')
                ]
            ])
            ->add('drugieImie', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(max: 255, maxMessage: 'Drugie imię nie może być dłuższe niż {{ limit }} znaków'),
                    new Assert\Regex(pattern: '/^[\p{L}\s-]*$/u', message: 'Drugie imię może zawierać tylko litery, spacje i myślniki')
                ]
            ])
            ->add('nazwisko', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(message: 'Nazwisko jest wymagane'),
                    new Assert\Length(min: 2, max: 255, minMessage: 'Nazwisko musi mieć co najmniej {{ limit }} znaki', maxMessage: 'Nazwisko nie może być dłuższe niż {{ limit }} znaków'),
                    new Assert\Regex(pattern: '/^[\p{L}\s-]+$/u', message: 'Nazwisko może zawierać tylko litery, spacje i myślniki')
                ]
            ])
            ->add('pesel', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(message: 'PESEL jest wymagany'),
                    new Assert\Regex(pattern: '/^\d{11}$/', message: 'PESEL musi składać się z 11 cyfr')
                ]
            ])
            ->add('oldId', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(max: 255, maxMessage: 'Old ID nie może być dłuższe niż {{ limit }} znaków')
                ]
            ])

            // Adres zamieszkania
            ->add('ulicaZamieszkania', TextType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(message: 'Ulica zamieszkania jest wymagana'),
                    new Assert\Length(max: 255, maxMessage: 'Ulica nie może być dłuższa niż {{ limit }} znaków')
                ]
            ])
            ->add('nrDomuZamieszkania', TextType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(message: 'Numer domu zamieszkania jest wymagany'),
                    new Assert\Length(max: 20, maxMessage: 'Numer domu nie może być dłuższy niż {{ limit }} znaków')
                ]
            ])
            ->add('nrLokaliZamieszkania', TextType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(message: 'Numer lokalu zamieszkania jest wymagany'),
                    new Assert\Length(max: 20, maxMessage: 'Numer lokalu nie może być dłuższy niż {{ limit }} znaków')
                ]
            ])
            ->add('kodPocztowyZamieszkania', TextType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(message: 'Kod pocztowy zamieszkania jest wymagany'),
                    new Assert\Regex(pattern: '/^\d{2}-\d{3}$/', message: 'Kod pocztowy musi być w formacie XX-XXX')
                ]
            ])
            ->add('miastoZamieszkania', TextType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(message: 'Miasto zamieszkania jest wymagane'),
                    new Assert\Length(max: 255, maxMessage: 'Miasto nie może być dłuższe niż {{ limit }} znaków')
                ]
            ])
            ->add('pocztaZamieszkania', TextType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(message: 'Poczta zamieszkania jest wymagana'),
                    new Assert\Length(max: 255, maxMessage: 'Poczta nie może być dłuższa niż {{ limit }} znaków')
                ]
            ])

            // Adres korespondencyjny
            ->add('ulicaKorespondencyjny', TextType::class, [
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Assert\Length(max: 255, maxMessage: 'Ulica nie może być dłuższa niż {{ limit }} znaków')
                ]
            ])
            ->add('nrDomuKorespondencyjny', TextType::class, [
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Assert\Length(max: 20, maxMessage: 'Numer domu nie może być dłuższy niż {{ limit }} znaków')
                ]
            ])
            ->add('nrLokaliKorespondencyjny', TextType::class, [
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Assert\Length(max: 20, maxMessage: 'Numer lokalu nie może być dłuższy niż {{ limit }} znaków')
                ]
            ])
            ->add('kodPocztowyKorespondencyjny', TextType::class, [
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Assert\Regex(pattern: '/^\d{2}-\d{3}$/', message: 'Kod pocztowy musi być w formacie XX-XXX')
                ]
            ])
            ->add('miastoKorespondencyjne', TextType::class, [
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Assert\Length(max: 255, maxMessage: 'Miasto nie może być dłuższe niż {{ limit }} znaków')
                ]
            ])
            ->add('pocztaKorespondencyjna', TextType::class, [
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Assert\Length(max: 255, maxMessage: 'Poczta nie może być dłuższa niż {{ limit }} znaków')
                ]
            ])

            // Dane kontaktowe
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Assert\NotBlank(message: 'Email jest wymagany'),
                    new Assert\Email(message: 'Nieprawidłowy format email'),
                    new Assert\Length(max: 180, maxMessage: 'Email nie może być dłuższy niż {{ limit }} znaków')
                ]
            ])
            ->add('telefon', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(message: 'Telefon jest wymagany'),
                    new Assert\Regex(pattern: '/^(\+?48)?[1-9]\d{8}$/', message: 'Nieprawidłowy format numeru telefonu')
                ]
            ])

            // Przynależność (pole tekstowe zamiast entity - będzie mapowane później)
            ->add('przynaleznosc', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(max: 2000, maxMessage: 'Przynależność nie może być dłuższa niż {{ limit }} znaków')
                ]
            ])
            ->add('regionNazwa', TextType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(message: 'Nazwa regionu jest wymagana'),
                    new Assert\Length(max: 255, maxMessage: 'Nazwa regionu nie może być dłuższa niż {{ limit }} znaków')
                ]
            ])
            ->add('oddzialNazwa', TextType::class, [
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Assert\Length(max: 255, maxMessage: 'Nazwa oddziału nie może być dłuższa niż {{ limit }} znaków')
                ]
            ])
            ->add('okregNazwa', TextType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(message: 'Nazwa okręgu jest wymagana'),
                    new Assert\Length(max: 255, maxMessage: 'Nazwa okręgu nie może być dłuższa niż {{ limit }} znaków')
                ]
            ])

            // Pełnione funkcje publiczne
            ->add('funkcjePubliczne', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(max: 2000, maxMessage: 'Funkcje publiczne nie mogą być dłuższe niż {{ limit }} znaków')
                ]
            ])

            // Historia wyborów
            ->add('historiaWyborow', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(max: 2000, maxMessage: 'Historia wyborów nie może być dłuższa niż {{ limit }} znaków')
                ]
            ])

            // Zgoda RODO
            ->add('zgodaRodo', CheckboxType::class, [
                'constraints' => [
                    new Assert\IsTrue(message: 'Zgoda RODO jest wymagana')
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => false,
        ]);
    }
}