<?php

namespace App\Form;

use App\Entity\Faktura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class FakturaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numerFaktury', TextType::class, [
                'label' => 'Numer faktury/dokumentu',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'np. FA/2024/001',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Numer faktury jest wymagany']),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Numer faktury nie może być dłuższy niż {{ limit }} znaków'
                    ])
                ]
            ])
            ->add('kwota', MoneyType::class, [
                'label' => 'Kwota płatności',
                'currency' => 'PLN',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0,00',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Kwota jest wymagana']),
                    new Assert\Positive(['message' => 'Kwota musi być większa od zera'])
                ]
            ])
            ->add('numerKonta', TextType::class, [
                'label' => 'Numer konta odbiorcy',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'PL12 3456 7890 1234 5678 9012 3456',
                    'maxlength' => 32,
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Numer konta jest wymagany']),
                    new Assert\Length([
                        'min' => 26,
                        'max' => 34,
                        'minMessage' => 'Numer konta musi mieć co najmniej {{ limit }} znaków (IBAN)',
                        'maxMessage' => 'Numer konta nie może być dłuższy niż {{ limit }} znaków'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[A-Z]{2}[0-9]{2}[\s0-9]*$/',
                        'message' => 'Numer konta musi być w formacie IBAN (np. PL12 3456 7890 1234...)'
                    ])
                ],
                'help' => 'Wprowadź numer konta w formacie IBAN (26 cyfr) lub z spacjami'
            ])
            ->add('dataPlatnosci', DateType::class, [
                'label' => 'Data płatności (proponowana/wymagana)',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Data płatności jest wymagana']),
                ],
                'help' => 'Kiedy powinna zostać wykonana płatność'
            ])
            ->add('celPlatnosci', TextareaType::class, [
                'label' => 'Cel płatności (objaśnienie)',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Szczegółowy opis celu płatności, za co jest płacone, itp.',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Cel płatności jest wymagany']),
                    new Assert\Length([
                        'min' => 10,
                        'max' => 1000,
                        'minMessage' => 'Cel płatności musi mieć co najmniej {{ limit }} znaków',
                        'maxMessage' => 'Cel płatności nie może być dłuższy niż {{ limit }} znaków'
                    ])
                ],
                'help' => 'Opisz szczegółowo, za co jest płacone (min. 10 znaków)'
            ])
            ->add('kategoria', ChoiceType::class, [
                'label' => 'Kategoria wydatku',
                'choices' => Faktura::getKategoriaChoices(),
                'attr' => [
                    'class' => 'form-select',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Kategoria wydatku jest wymagana']),
                ],
                'placeholder' => 'Wybierz kategorię...'
            ])
            ->add('pilnosc', ChoiceType::class, [
                'label' => 'Priorytet płatności',
                'choices' => Faktura::getPilnoscChoices(),
                'attr' => [
                    'class' => 'form-select',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Priorytet płatności jest wymagany']),
                ],
                'help' => 'Pilne płatności będą priorytetowo traktowane przez skarbnika partii'
            ])
            ->add('nazwaDostaway', TextType::class, [
                'label' => 'Nazwa dostawcy/odbiorcy',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'np. Firma ABC Sp. z o.o.',
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Nazwa dostawcy nie może być dłuższa niż {{ limit }} znaków'
                    ])
                ],
                'help' => 'Opcjonalnie - nazwa firmy lub osoby otrzymującej płatność'
            ])
            ->add('adresDostaway', TextType::class, [
                'label' => 'Adres dostawcy/odbiorcy',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'ul. Przykładowa 1, 00-001 Warszawa',
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Adres dostawcy nie może być dłuższy niż {{ limit }} znaków'
                    ])
                ],
                'help' => 'Opcjonalnie - adres firmy lub osoby otrzymującej płatność'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Zapisz fakturę',
                'attr' => [
                    'class' => 'btn btn-primary btn-lg',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Faktura::class,
        ]);
    }
}