<?php

namespace App\Form;

use App\Entity\ProjectTask;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectTaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, array(
                'label' => 'Tên công việc'
            ))
            ->add('stage', ChoiceType::class, array(
                'label' => 'Giai đoạn',
                'choices' => array(
                    'Thiết kế' => 'design',
                    'Xin phép' => 'permit',
                    'Thi công' => 'construction',
                    'Nghiệm thu' => 'acceptance',
                )
            ))
            ->add('status', ChoiceType::class, array(
                'label' => 'Trạng thái',
                'choices' => array(
                    'Chờ' => 'pending',
                    'Đang làm' => 'in_progress',
                    'Hoàn thành' => 'completed',
                )
            ))
            ->add('dueDate', DateType::class, array(
                'label' => 'Hạn chót',
                'required' => false,
                'widget' => 'single_text',
            ))
            ->add('assignedTo', EntityType::class, array(
                'class' => User::class,
                'choice_label' => 'name',
                'label' => 'Phụ trách',
                'required' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.name', 'ASC');
                },
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ProjectTask::class,
        ));
    }
}
