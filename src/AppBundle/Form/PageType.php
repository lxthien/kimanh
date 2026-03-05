<?php

namespace AppBundle\Form;

use AppBundle\Entity\News;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Vich\UploaderBundle\Form\Type\VichFileType;

class PageType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', null, [
                'attr' => ['class' => 'sluggable'],
                'label' => 'label.title',
            ])
            ->add('parent', EntityType::class, [
                'class' => News::class,
                'choice_label' => 'title',
                'required' => false,
                'label' => 'label.parent_page',
                'query_builder' => function ($er) {
                    return $er->createQueryBuilder('n')
                        ->where('n.postType = :postType')
                        ->setParameter('postType', 'page')
                        ->orderBy('n.title', 'ASC');
                },
                'placeholder' => 'Chọn trang cha (tùy chọn)',
            ])
            ->add('url', TextType::class, [
                'attr' => ['class' => 'url', 'readonly' => 'readonly'],
                'label' => 'label.url',
            ])
            ->add('imageFile', VichFileType::class, [
                'required' => false,
                'allow_delete' => true,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'label.description',
            ])
            ->add('contents', TextareaType::class, [
                'attr' => ['class' => 'txt-ckeditor', 'data-height' => '500'],
                'label' => 'label.contents',
            ])
            ->add('postType', HiddenType::class, [
                'label' => 'label.postType',
            ])
            ->add('pageTitle', TextType::class, [
                'required' => false,
                'label' => 'label.pageTitle',
            ])
            ->add('pageDescription', TextareaType::class, [
                'required' => false,
                'label' => 'label.pageDescription',
            ])
            ->add('pageKeyword', TextType::class, [
                'required' => false,
                'label' => 'label.pageKeyword',
            ])
            ->add('template', ChoiceType::class, [
                'required' => false,
                'label' => 'Template',
                'choices' => ['Mặc định' => '2_columns', 'Landing page' => '1_column'],
                'empty_data' => '2_column',
                'placeholder' => false
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => News::class,
        ]);
    }
}
