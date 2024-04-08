<?php

namespace Webkul\Modules\Wix\WixmpBundle\Form\Seller;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webkul\Modules\Wix\WixmpBundle\Entity\CustomFeilds;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TelType;


class CustomFieldFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('feild_name',TextType::class,
            [
                'constraints' => array(
                    // new NotBlank(),
                    new Length(array('min' => 1)),
                    new Regex("/^[A-Za-z0-9_ ]+$/"),
                ),
            ])

            ->add('label',TextType::class,
            [
                'constraints' => array(
                    new NotBlank(),
                    new Length(array('min' => 1, 'max' => 50)),
                    new Regex("/^[A-Za-z0-9_ ]+$/")
                    // new Regex("/^\w+/"),
                ),
            ])

            ->add(
                'type',
                ChoiceType::class,
                ['choices' => array(
                    'Text' => 'text',
                    'Email' => 'email',
                    'Textarea' => 'textarea',
                    'SelectBox' => 'option',
                    'CheckBox' => 'checkbox',
                    'Radio' => 'radio',
                    // 'File' => 'file',
                ),
            ])

            ->add('options',TextType::class)

            ->add(
                'isRequired',
                ChoiceType::class,
                ['choices' => array(
                    'No' => 0,
                    'Yes' => 1
                ),
            ])

            // ->add('class',ChoiceType::class,
            // ['choices' => array(
            //     'choose_class' => '',
            //     'wk-integer' => 'wk-integer',
            //     'wk-required' => 'wk-required',
            // ),
            // ])

            ->add(
                'status',
                ChoiceType::class,
                ['choices' => array(
                    'active' => 'A',
                    'disabled' => 'D',
                ),
            ]
            )
        ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CustomFeilds::class,
            'empty_data' => ''
        ]);

    }
}
