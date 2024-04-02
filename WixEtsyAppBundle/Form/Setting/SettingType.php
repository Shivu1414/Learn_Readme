<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\Form\Setting;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class SettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('shop', TextType::class, [
                'required' => false,
            ])
            ->add('etsy_category', TextType::class, [
                'required' => false,
            ])
            ->add('shipping_profile', TextType::class, [
                'required' => false,
            ])
            ->add('auto_sync_prots_etsy', CheckboxType::class, [
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
