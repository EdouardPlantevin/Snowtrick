<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Trick;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrickType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre'
            ])
            ->add('description', CKEditorType::class, [
                'label' => 'Description',
                'required' => true
            ])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'title',
                'multiple' => true,
                'expanded' => true
            ])
            ->add('photos', FileType::class, [
                'label' => 'Ajouter vos images',
                'attr' => [
                    'accept' => 'image/*'
                ],
                'multiple' => true,
                'required' => false,
                'mapped' => false
            ])
            ->add('video', TextType::class, [
                'label' => 'Ajoute une vidéo',
                'required' => false,
                'mapped' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trick::class,
        ]);
    }
}
