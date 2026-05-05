<?php

namespace App\Form;

use App\Entity\ProjectDocument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectDocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, array(
                'label' => 'Tên tài liệu/Hồ sơ'
            ))
            ->add('type', ChoiceType::class, array(
                'label' => 'Loại',
                'choices' => array(
                    'Bản vẽ' => 'drawing',
                    'Hợp đồng' => 'contract',
                    'Biên bản' => 'report',
                    'Hình ảnh hiện trường' => 'photo',
                    'Khác' => 'other',
                )
            ))
            ->add('file', FileType::class, array(
                'label' => 'Chọn file',
                'mapped' => false, // We will handle upload manually in controller for MVP
                'required' => true
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ProjectDocument::class,
        ));
    }
}
