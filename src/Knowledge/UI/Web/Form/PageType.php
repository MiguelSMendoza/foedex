<?php

declare(strict_types=1);

namespace App\Knowledge\UI\Web\Form;

use App\Taxonomy\Domain\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class PageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Título',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 3, max: 160),
                ],
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'required' => false,
                'empty_data' => '',
                'help' => 'Si lo dejas vacío, se genera a partir del título.',
            ])
            ->add('excerpt', TextareaType::class, [
                'label' => 'Extracto',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('markdown', TextareaType::class, [
                'label' => 'Contenido Markdown',
                'attr' => [
                    'rows' => 20,
                    'data-markdown-input' => 'true',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 20),
                ],
            ])
            ->add('changeSummary', TextType::class, [
                'label' => 'Resumen del cambio',
                'required' => false,
            ])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'multiple' => true,
                'required' => false,
                'label' => 'Categorías existentes',
            ])
            ->add('newCategories', TextType::class, [
                'label' => 'Nuevas categorías',
                'required' => false,
                'mapped' => false,
                'help' => 'Separadas por comas.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PageData::class,
        ]);
    }
}
