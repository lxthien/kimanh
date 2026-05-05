<?php

namespace App\Form;

use App\Entity\ConstructionProject;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConstructionProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('projectName', TextType::class, array(
                'label' => 'Tên công trình'
            ))
            ->add('address', TextType::class, array(
                'label' => 'Địa chỉ',
                'required' => false
            ))
            ->add('customerName', TextType::class, array(
                'label' => 'Tên chủ đầu tư',
                'required' => false
            ))
            ->add('customerPhone', TextType::class, array(
                'label' => 'Số điện thoại',
                'required' => false
            ))
            ->add('status', ChoiceType::class, array(
                'label' => 'Trạng thái',
                'choices' => array(
                    'Nháp' => 'draft',
                    'Đang báo giá' => 'estimating',
                    'Đang thi công' => 'active',
                    'Hoàn thành' => 'completed',
                    'Tạm dừng' => 'paused',
                )
            ))
            ->add('type', ChoiceType::class, array(
                'choices' => array(
                    'Nhà phố' => 1,
                    'Biệt thự' => 2,
                    'Nhà cấp 4' => 3,
                ),
                'label' => 'Loại công trình'
            ))
            ->add('finishLevel', ChoiceType::class, array(
                'choices' => array(
                    'Gói cơ bản' => 1,
                    'Gói tiêu chuẩn' => 2,
                    'Gói cao cấp' => 3,
                ),
                'label' => 'Mức hoàn thiện'
            ))
            ->add('contractValue', NumberType::class, array(
                'label' => 'Giá trị hợp đồng (VNĐ)',
                'scale' => 2,
                'required' => false
            ))
            ->add('targetMargin', ChoiceType::class, array(
                'choices' => array(
                    '15%' => 15,
                    '18%' => 18,
                    '20%' => 20,
                    '22%' => 22,
                    '25%' => 25,
                ),
                'label' => 'Biên lợi nhuận mục tiêu'
            ))
            ->add('wide', NumberType::class, array(
                'label' => 'Chiều rộng (m)',
                'scale' => 2
            ))
            ->add('long', NumberType::class, array(
                'label' => 'Chiều dài (m)',
                'scale' => 2
            ))
            ->add('floor', ChoiceType::class, array(
                'choices' => array(
                    '1 tầng' => 1,
                    '2 tầng' => 2,
                    '3 tầng' => 3,
                    '4 tầng' => 4,
                    '5 tầng' => 5,
                    '6 tầng' => 6,
                    '7 tầng' => 7,
                ),
                'label' => 'Số tầng'
            ))
            ->add('basement', ChoiceType::class, array(
                'choices' => array(
                    'Không có tầng hầm' => 0,
                    'Bán hầm' => 1,
                    '1 tầng hầm' => 2,
                ),
                'label' => 'Phương án tầng hầm'
            ))
            ->add('mong', ChoiceType::class, array(
                'choices' => array(
                    'Móng cọc' => 1,
                    'Móng băng' => 2,
                    'Móng đơn' => 3,
                    'Móng bè' => 4,
                ),
                'label' => 'Loại móng'
            ))
            ->add('mai', ChoiceType::class, array(
                'choices' => array(
                    'Mái BTCT đúc bằng' => 1,
                    'Mái tôn lạnh' => 2,
                    'Mái ngói kèo thép' => 3,
                    'Mái BTCT lợp ngói' => 4,
                ),
                'label' => 'Loại mái'
            ))
            ->add('alley', ChoiceType::class, array(
                'choices' => array(
                    'Mặt tiền xe tải vào được' => 1,
                    'Hẻm 3m - 5m' => 2,
                    'Hẻm dưới 3m' => 3,
                ),
                'label' => 'Tiếp cận công trình'
            ))
            ->add('subcontract', ChoiceType::class, array(
                'choices' => array(
                    'Tự quản lý tổ đội' => 1,
                    'Khoán hỗn hợp' => 2,
                    'Khoán thầu phụ nhiều hạng mục' => 3,
                ),
                'label' => 'Mô hình triển khai'
            ))
            ->add('notes', TextareaType::class, array(
                'label' => 'Ghi chú nội bộ',
                'required' => false
            ))
            ->add('startDate', DateType::class, array(
                'label' => 'Ngày khởi công',
                'required' => false,
                'widget' => 'single_text',
            ))
            ->add('endDate', DateType::class, array(
                'label' => 'Ngày kết thúc (dự kiến)',
                'required' => false,
                'widget' => 'single_text',
            ))
            ->add('realProgress', NumberType::class, array(
                'label' => 'Tiến độ thực tế (%)',
                'scale' => 2,
                'required' => false
            ))
            ->add('analyze', SubmitType::class, array(
                'label' => 'Phân tích'
            ))
            ->add('saveProject', SubmitType::class, array(
                'label' => 'Lưu dự án'
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ConstructionProject::class,
        ));
    }
}
