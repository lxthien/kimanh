<?php

namespace App\Form;

use App\Entity\ProjectCostItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectCostItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('category', ChoiceType::class, array(
                'label' => 'Danh mục',
                'choices' => array(
                    'Vật tư' => 'material',
                    'Nhân công' => 'labor',
                    'Thầu phụ' => 'subcontract',
                    'Máy móc' => 'machinery',
                    'Khác' => 'other',
                )
            ))
            ->add('description', TextType::class, array(
                'label' => 'Diễn giải'
            ))
            ->add('amount', NumberType::class, array(
                'label' => 'Số tiền',
                'scale' => 2
            ))
            ->add('costDate', DateType::class, array(
                'label' => 'Ngày chi',
                'widget' => 'single_text',
            ))
            ->add('type', ChoiceType::class, array(
                'label' => 'Loại chi phí',
                'choices' => array(
                    'Thực tế chi' => 'actual',
                    'Dự toán' => 'estimate',
                    'Phát sinh' => 'extra',
                )
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ProjectCostItem::class,
        ));
    }
}
