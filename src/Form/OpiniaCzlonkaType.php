<?php

namespace App\Form;

use App\Entity\OpiniaCzlonka;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<OpiniaCzlonka>
 */
class OpiniaCzlonkaType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('opinia', TextareaType::class, [
                'label' => 'Opinia o członku',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 6,
                    'placeholder' => 'Napisz swoją opinię o tym członku partii. Opinia będzie widoczna tylko dla osób funkcyjnych...',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Opinia nie może być pusta.',
                    ]),
                    new Length([
                        'min' => 10,
                        'max' => 2000,
                        'minMessage' => 'Opinia musi mieć co najmniej {{ limit }} znaków.',
                        'maxMessage' => 'Opinia nie może być dłuższa niż {{ limit }} znaków.',
                    ]),
                ],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OpiniaCzlonka::class,
        ]);
    }
}
