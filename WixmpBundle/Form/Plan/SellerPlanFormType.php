<?php

namespace Webkul\Modules\Wix\WixmpBundle\Form\Plan;

use App\Entity\Application;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotEqualTo;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerPlan;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class SellerPlanFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'conditions',
                HiddenType::class
            )
            ->add(
                'code',
                TextType::class,
                [
                    'constraints' => array(
                        new NotBlank(),
                        new Length(array('min' => 3)),
                        // new Regex("/^\w+/"),
                        new Regex("/^[A-Za-z0-9 ]+$/"),
                    ),
                ]
            )
            ->add('plan',TextType::class,[
                    'constraints' => array(
                        new NotBlank(),
                        new Length(array('min' => 3)),
                        new Regex("/^\w+/u")
                    ),
                    
                ])
            ->add('price',TextType::class,[
                'constraints' => array(
                    new NotBlank()
                )
            ])
            ->add('description',TextareaType::class)
            ->add('intervalValue',IntegerType::class,[
                'constraints' => array(
                    new NotEqualTo(0),
                    new NotBlank(),
                    new Regex("/^\d+/")
                )
            ])
            ->add('intervalType',ChoiceType::class,[
                'choices'  => array(
                    'days' => 'D',
                    'monthly' => 'M',
                    'quarterly' => 'Q',
                    'half-yearly' => 'H',
                    'yearly' => 'Y',
                    )]
                )
            ->add('status',ChoiceType::class,['choices'  => array(
                'active' => 'A',
                'disabled' => 'D')]
            )
            ->add('bestChoice',ChoiceType::class,['choices'  => array(
                'yes' => 'Y',
                'no' => 'N')]
            )
            // ->add('application', EntityType::class, array(
            //     'class' => Application::class,
            //     'query_builder' => function (EntityRepository $er) {
            //         return $er->createQueryBuilder('application')
            //             ->orderBy('application.id', 'ASC');
            //     },
            //     'choice_label' => 'name',
            //     'multiple' => true,
            //     'expanded' => true,
            // ));
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SellerPlan::class,
        ]);
    }
}
