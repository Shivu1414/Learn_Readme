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
use Webkul\Modules\Wix\WixmpBundle\Entity\Seller;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerPlan;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class SellerFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $plan_list = $options['plan_list'];
        $custom_field_data = $options['custom_field_data'];
        
        $builder
            ->add('seller',TextType::class)
            ->add('email',EmailType::class)
            ->add(
                'phone',
                TelType::class               
            )
            ->add('address',TextType::class)
            ->add('address2',TextType::class)
            ->add('city',TextType::class)
            ->add('state',TextType::class)
            ->add('country',CountryType::class)
            ->add('zipcode',TextType::class)
            ->add('allowedCategories', TextType::class)
            ->add('allowedCustomerDetails', TextType::class)
            ->add('password', PasswordType::class,[
                'constraints' => [
                    new Regex([
                        "pattern" => '/^(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z])(?=.*\W)(?!.* ).{8,16}$/',
                        "message" => "please enter valid password standard",
                    ]),
                ]
            ])
            ->add('confirmPassword', PasswordType::class,['mapped' => false]);
        ;

        if($options['data']->getId() == null) {
            $builder->add('plan', ChoiceType::class, array(
                'choices' => $plan_list,
                'mapped' => false
            ));
        } else {
            $builder->add('status',ChoiceType::class,['choices'  => array(
                'active' => 'A',
                'disabled' => 'D')]
            );
        }

                   
        $builder->add('username',TextType::class, array(
            'mapped' => false
        ));

        if (!empty($custom_field_data)) {
            foreach ($custom_field_data as $customField) {
                // $fieldName = preg_replace('/\s+/', '', $customField->getFeildName());
                $fieldName = $customField->getFeildName();
                $optionsValue = explode(',', $customField->getOptions());
                $optionData = [];
                if(isset($optionsValue) && !blank($optionsValue)) {
                    foreach($optionsValue as $key=>$value) {
                        $optionData[$value] = $value;
                    }
                }

                $formType = $customField->getType();
                if($formType == "text") {
                    $typeClass = TextType::class;
                } elseif ($formType == "textarea") {
                    $typeClass = TextareaType::class;
                } elseif($formType == "email") {
                    $typeClass = EmailType::class;
                } elseif($formType == "option") {
                    $typeClass = ChoiceType::class;
                }
                // elseif($formType == "file") {
                //     $typeClass = FileType::class;
                // } 
                else {
                    $typeClass = TextType::class;
                }
                
                if($formType == "option") {
                    $builder->add($fieldName,$typeClass, array(
                            'label' => $customField->getLabel(),
                            'choices' => $optionData,
                            'mapped' => false,
                        )
                    );
                } 
                elseif ($formType == "checkbox") {
                    $builder->add($fieldName,ChoiceType::class,[
                        'multiple'=>true,
                        'expanded'=>true,
                        'mapped' => false,
                        'choices'=> $optionData,
                         ]);
                } elseif ($formType == "radio") {
                    $builder->add($fieldName,ChoiceType::class,[
                        'multiple'=> false,
                        'expanded'=> true,
                        'mapped' => false,
                        'choices'=> $optionData,
                        ]);
                } 
                else {
                    $builder->add($fieldName,$typeClass, array(
                            'label' => $customField->getLabel(),
                            'mapped' => false,
                        )
                    );
                }
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Seller::class,
        ]);

        $resolver->setRequired('plan_list');
        $resolver->setRequired('custom_field_data');
    }
}
