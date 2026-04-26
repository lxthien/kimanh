<?php

namespace App\Form;

use App\Entity\ConstructionMaterial;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConstructionMaterialType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code', ChoiceType::class, array(
                'label' => 'Mã vật tư',
                'choices' => array(
                    'Bê tông thương phẩm' => 'BETONG',
                    'Thép xây dựng' => 'THEP',
                    'Gạch xây' => 'GACH',
                    'Cát xây tô' => 'CAT',
                    'Sơn nước' => 'SON',
                    'Gạch hoàn thiện sàn' => 'GACHLAT',
                    'Dây điện tổng hợp' => 'DAYDIEN',
                    'Ống nước và phụ kiện' => 'ONGNUOC',
                ),
                'placeholder' => '--- Chọn mã vật tư ---'
            ))
            ->add('name', TextType::class, array(
                'label' => 'Tên vật tư'
            ))
            ->add('category', ChoiceType::class, array(
                'label' => 'Nhóm vật tư',
                'choices' => array(
                    'Bê tông' => 'Bê tông',
                    'Cốt thép' => 'Cốt thép',
                    'Xây tô' => 'Xây tô',
                    'Hoàn thiện' => 'Hoàn thiện',
                    'MEP' => 'MEP',
                    'Khác' => 'Khác',
                )
            ))
            ->add('unit', ChoiceType::class, array(
                'label' => 'Đơn vị tính',
                'choices' => array(
                    'm3 (Khối)' => 'm3',
                    'kg (Kilogam)' => 'kg',
                    'viên' => 'vien',
                    'm2 (Mét vuông)' => 'm2',
                    'm (Mét dài)' => 'm',
                ),
                'placeholder' => '--- Chọn đơn vị ---'
            ))
            ->add('unitPrice', NumberType::class, array(
                'label' => 'Đơn giá',
                'scale' => 2
            ))
            ->add('wastagePercent', NumberType::class, array(
                'label' => 'Hao hụt (%)',
                'scale' => 2,
                'required' => false
            ))
            ->add('isActive', CheckboxType::class, array(
                'label' => 'Đang sử dụng',
                'required' => false
            ))
            ->add('note', TextareaType::class, array(
                'label' => 'Ghi chú',
                'required' => false
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ConstructionMaterial::class,
        ));
    }
}
