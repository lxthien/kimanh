<?php

namespace AppBundle\Form;

use AppBundle\Entity\Redirect;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class RedirectType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('oldUrl', TextType::class, [
                'label' => 'Old URL (e.g., /old-path or old-path)',
                'attr' => ['placeholder' => '/old-path']
            ])
            ->add('newUrl', TextType::class, [
                'label' => 'New URL (can be absolute link http://... or relative /new-path)',
                'attr' => ['placeholder' => '/new-path or https://example.com']
            ])
            ->add('statusCode', ChoiceType::class, [
                'label' => 'Redirect Type (Status Code)',
                'choices' => [
                    '301 Moved Permanently (SEO Friendly)' => 301,
                    '302 Found (Temporary Redirect)' => 302,
                    '307 Temporary Redirect' => 307,
                    '308 Permanent Redirect' => 308,
                ],
                'data' => 301, // Default value
            ])
            ->add('enable', CheckboxType::class, [
                'required' => false,
                'label' => 'label.enable',
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Redirect::class,
        ]);
    }
}
