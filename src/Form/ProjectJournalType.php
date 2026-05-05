<?php

namespace App\Form;

use App\Entity\ProjectJournal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectJournalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('logDate', DateType::class, array(
                'label' => 'Ngày ghi nhật ký',
                'widget' => 'single_text',
            ))
            ->add('weather', TextType::class, array(
                'label' => 'Thời tiết',
                'required' => false
            ))
            ->add('content', TextareaType::class, array(
                'label' => 'Nội dung thi công',
                'attr' => array('rows' => 4)
            ))
            ->add('workerCount', IntegerType::class, array(
                'label' => 'Số lượng nhân công'
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ProjectJournal::class,
        ));
    }
}
