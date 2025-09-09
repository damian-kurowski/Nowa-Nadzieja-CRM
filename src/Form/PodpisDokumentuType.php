<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<mixed>
 */
class PodpisDokumentuType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('podpisElektroniczny', HiddenType::class, [
                'required' => false,
            ])
            ->add('komentarz', TextareaType::class, [
                'label' => 'Komentarz do podpisu',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Opcjonalny komentarz do podpisu...',
                ],
                'help' => 'Dodatkowe uwagi lub komentarz do podpisu',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Podpisz dokument',
                'attr' => [
                    'class' => 'btn btn-success btn-lg',
                    'id' => 'sign-button',
                ],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
