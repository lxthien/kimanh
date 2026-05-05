<?php

namespace App\Form;

use App\Entity\ProjectAttendance;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectAttendanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date', DateType::class, array(
                'label' => 'Ngày chấm công',
                'widget' => 'single_text',
            ))
            ->add('workerName', TextType::class, array(
                'label' => 'Tên nhân công'
            ))
            ->add('hours', NumberType::class, array(
                'label' => 'Số giờ'
            ))
            ->add('note', TextType::class, array(
                'label' => 'Ghi chú',
                'required' => false
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ProjectAttendance::class,
        ));
    }
}
