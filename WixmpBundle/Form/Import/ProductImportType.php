<?php

namespace Webkul\Modules\Wix\WixmpBundle\Form\Import;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Validator\Constraints\File;

class ProductImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('file', FileType::class, [
                'attr' => [
                    'accept' => '.csv',
                    //'accept' => '.csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'maxSizeMessage' => 'Maximum supported file size is 2 MB',
                    ])
                ]
            ])
            ->add('delimiter',
                ChoiceType::class, [
                    'choices' => $this->get_delimiter_type(),
                ]
            )
            ->add('skip_existing_products',
                CheckboxType::class, ['required' => false, 'attr' => ['checked' => 'checked']]
            )
            ->add('categories_seperator', HiddenType::class)/* not using now, TODO: REMOVE COMPLETELY */
        ;
    }

    public function get_delimiter_type()
    {
        return [
            'Comma' => 'C',
            'Semicolon' => 'S',
            'Tab' => 'T',
        ];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
