<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GlobalSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('siteName', TextType::class, ['required' => false, 'label' => 'Tên Website'])
            ->add('pageTitle', TextType::class, ['required' => false, 'label' => 'Tiêu đề trang'])
            ->add('pageDescription', TextareaType::class, ['required' => false, 'label' => 'Mô tả trang'])
            ->add('pageKeyword', TextType::class, ['required' => false, 'label' => 'Từ khóa trang'])
            ->add('topKeyword', TextareaType::class, ['required' => false, 'attr' => ['rows' => 10], 'label' => 'Từ khóa hàng đầu'])
            
            ->add('emailContact', EmailType::class, ['required' => false, 'label' => 'Email Liên hệ'])
            ->add('hotLine1', TextType::class, ['required' => false, 'label' => 'Hotline 1'])
            ->add('hotLine2', TextType::class, ['required' => false, 'label' => 'Hotline 2'])
            
            ->add('marqueeHomepage', TextareaType::class, ['required' => false, 'attr' => ['rows' => 7], 'label' => 'Dòng chữ chạy trang chủ'])
            
            ->add('listPrices', TextType::class, ['required' => false, 'label' => 'Bảng giá (ID bài viết/danh mục)'])
            ->add('listCategoryOnHomepage', TextareaType::class, ['required' => false, 'attr' => ['rows' => 7], 'label' => 'Danh mục hiển thị trang chủ'])
            ->add('numberRecordOnPage', NumberType::class, ['required' => false, 'label' => 'Số bài viết mỗi trang'])
            
            ->add('displayNameCommentAs', TextType::class, ['required' => false, 'label' => 'Tên hiển thị bình luận Admin'])
            
            ->add('facebookMessenger', TextareaType::class, ['required' => false, 'attr' => ['rows' => 10], 'label' => 'Code Facebook Messenger'])
            ->add('googleAnalytic', TextareaType::class, ['required' => false, 'attr' => ['rows' => 7], 'label' => 'Mã Google Analytics'])
            ->add('GTM', TextType::class, ['required' => false, 'label' => 'Google Tag Manager ID'])
            ->add('ga4MeasurementID', TextType::class, ['required' => false, 'label' => 'GA4 Measurement ID'])
            ->add('fbApp', TextType::class, ['required' => false, 'label' => 'Facebook App ID'])
            
            ->add('linkToFacebook', TextType::class, ['required' => false, 'label' => 'Link Facebook'])
            ->add('linkToGooglePlus', TextType::class, ['required' => false, 'label' => 'Link Google+'])
            ->add('linkToTwitter', TextType::class, ['required' => false, 'label' => 'Link Twitter'])
            ->add('linkToYoutube', TextType::class, ['required' => false, 'label' => 'Link Youtube'])
            ->add('linkToLinkedin', TextType::class, ['required' => false, 'label' => 'Link Linkedin'])
            ->add('linkToInstagram', TextType::class, ['required' => false, 'label' => 'Link Instagram'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // no data class, we pass an array
        ]);
    }
}
