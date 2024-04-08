<?php

namespace Webkul\Modules\Wix\WixmpBundle\Form\Seller;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class SettingFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $emailRegexPattern = '/^(?!\.)[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
        $builder
            // ->add('paypalPayoutEmail', EmailType::class, ['label' => 'paypalPayoutEmail','required' => false,'empty_data' => ''])
            // ->add('stripePayoutEmail', EmailType::class, ['label' => 'stripePayoutEmail','required' => false,'empty_data' => ''])
            ->add('payoutFirstName', TextType::class, ['required' => false, 'empty_data' => ''])
            ->add('payoutLastName', TextType::class, ['required' => false, 'empty_data' => ''])
            ->add('payoutBankName', TextType::class, ['required' => false, 'empty_data' => ''])
            ->add('payoutBankIBAN', TextType::class, ['required' => false, 'empty_data' => ''])
            // ->add('paypalPayoutEmail', EmailType::class, ['label' => 'paypalPayoutEmail','required' => false,'empty_data' => ''])
            ->add('paypalPayoutEmail', EmailType::class, [
                'label' => 'paypalPayoutEmail',
                'required' => true,
                'empty_data' => '',
                'constraints' => [
                    new Regex([
                        "pattern" => $emailRegexPattern,
                        "message" => "please enter valid email address",
                    ]),
                ]
            ])
            ->add('stripePayoutEmail', EmailType::class, [
                'label' => 'stripePayoutEmail','required' => true,
                'empty_data' => '',
                'constraints' => [
                    new Regex([
                        "pattern" => $emailRegexPattern,
                        "message" => "Please enter valid email address",
                    ]),
                ]                
            ])
            ->add('urlGenerate', SubmitType::class, ['label' => 'Save']);
    }
}
