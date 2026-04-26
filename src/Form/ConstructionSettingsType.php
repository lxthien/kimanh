<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConstructionSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // Foundation Coefficients
            ->add('foundation_c_1', NumberType::class, ['label' => 'Móng cọc', 'scale' => 2, 'required' => false])
            ->add('foundation_c_2', NumberType::class, ['label' => 'Móng băng', 'scale' => 2, 'required' => false])
            ->add('foundation_c_3', NumberType::class, ['label' => 'Móng đơn', 'scale' => 2, 'required' => false])
            ->add('foundation_c_4', NumberType::class, ['label' => 'Móng bè', 'scale' => 2, 'required' => false])

            // Roof Coefficients
            ->add('roof_c_1', NumberType::class, ['label' => 'Mái BTCT đúc bằng', 'scale' => 2, 'required' => false])
            ->add('roof_c_2', NumberType::class, ['label' => 'Mái tôn lạnh', 'scale' => 2, 'required' => false])
            ->add('roof_c_3', NumberType::class, ['label' => 'Mái ngói kèo thép', 'scale' => 2, 'required' => false])
            ->add('roof_c_4', NumberType::class, ['label' => 'Mái BTCT lợp ngói', 'scale' => 2, 'required' => false])

            // Basement Coefficients
            ->add('basement_c_0', NumberType::class, ['label' => 'Không có tầng hầm', 'scale' => 2, 'required' => false])
            ->add('basement_c_1', NumberType::class, ['label' => 'Bán hầm', 'scale' => 2, 'required' => false])
            ->add('basement_c_2', NumberType::class, ['label' => '1 tầng hầm', 'scale' => 2, 'required' => false])

            // Sale Rates - Type 1 (Nhà phố)
            ->add('sale_rate_1_1', NumberType::class, ['label' => 'Nhà phố - Gói cơ bản', 'required' => false])
            ->add('sale_rate_1_2', NumberType::class, ['label' => 'Nhà phố - Gói tiêu chuẩn', 'required' => false])
            ->add('sale_rate_1_3', NumberType::class, ['label' => 'Nhà phố - Gói cao cấp', 'required' => false])

            // Sale Rates - Type 2 (Biệt thự)
            ->add('sale_rate_2_1', NumberType::class, ['label' => 'Biệt thự - Gói cơ bản', 'required' => false])
            ->add('sale_rate_2_2', NumberType::class, ['label' => 'Biệt thự - Gói tiêu chuẩn', 'required' => false])
            ->add('sale_rate_2_3', NumberType::class, ['label' => 'Biệt thự - Gói cao cấp', 'required' => false])

            // Sale Rates - Type 3 (Nhà cấp 4)
            ->add('sale_rate_3_1', NumberType::class, ['label' => 'Nhà cấp 4 - Gói cơ bản', 'required' => false])
            ->add('sale_rate_3_2', NumberType::class, ['label' => 'Nhà cấp 4 - Gói tiêu chuẩn', 'required' => false])
            ->add('sale_rate_3_3', NumberType::class, ['label' => 'Nhà cấp 4 - Gói cao cấp', 'required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);
    }
}
