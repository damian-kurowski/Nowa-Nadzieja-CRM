<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<mixed>
 */
class PhotoUploadType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('photo', FileType::class, [
                'label' => 'Wybierz zdjęcie',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Proszę wybierz zdjęcie do wgrania.',
                    ]),
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Proszę wgraj prawidłowy plik obrazu (JPEG, PNG, GIF, WebP)',
                        'maxSizeMessage' => 'Plik jest zbyt duży ({{ size }} {{ suffix }}). Maksymalny rozmiar to {{ limit }} {{ suffix }}.',
                    ]),
                ],
                'attr' => [
                    'accept' => 'image/*',
                    'class' => 'form-control',
                ],
            ])
            ->add('upload', SubmitType::class, [
                'label' => 'Wgraj zdjęcie',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ]);
    }
}
