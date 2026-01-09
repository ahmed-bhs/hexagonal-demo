<?php

declare(strict_types=1);

namespace App\Cadeau\Demande\UI\Http\Web\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * UI Layer - Symfony Form Type.
 *
 * Defines the structure and validation of a web form.
 * Part of the UI layer in hexagonal architecture.
 *
 * Used by controllers to:
 * - Render HTML forms
 * - Validate user input
 * - Map form data to DTOs/Commands
 */
final class DemandeCadeauType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomDemandeur', TextType::class, [
                'label' => 'Votre nom complet',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Le nom est obligatoire'),
                    new Assert\Length(min: 2, max: 100),
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'placeholder' => 'Jean Dupont',
                ],
            ])
            ->add('emailDemandeur', EmailType::class, [
                'label' => 'Votre email',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'L\'email est obligatoire'),
                    new Assert\Email(message: 'Email invalide'),
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'placeholder' => 'jean.dupont@example.com',
                ],
            ])
            ->add('telephoneDemandeur', TelType::class, [
                'label' => 'Votre téléphone',
                'required' => false,
                'constraints' => [
                    new Assert\Length(max: 20),
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'placeholder' => '+33 6 12 34 56 78',
                ],
            ])
            ->add('cadeauSouhaite', TextType::class, [
                'label' => 'Cadeau souhaité',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Le cadeau souhaité est obligatoire'),
                    new Assert\Length(min: 2, max: 255),
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'placeholder' => 'Vélo électrique, Ordinateur portable, etc.',
                ],
            ])
            ->add('motivation', TextareaType::class, [
                'label' => 'Pourquoi souhaitez-vous ce cadeau ?',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'La motivation est obligatoire'),
                    new Assert\Length(min: 10, max: 2000),
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'rows' => 5,
                    'placeholder' => 'Expliquez pourquoi vous souhaitez recevoir ce cadeau...',
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Soumettre ma demande',
                'attr' => [
                    'class' => 'w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Uncomment to map form to a DTO class:
            // 'data_class' => DemandeCadeauInput::class,

            'attr' => [
                'class' => 'needs-validation',
                'novalidate' => true,
            ],
        ]);
    }
}
