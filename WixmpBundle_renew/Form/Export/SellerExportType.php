<?php

namespace Webkul\Modules\Wix\WixmpBundle\Form\Export;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType; 

class SellerExportType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('file_name')
			->add(
				'output_type',
				ChoiceType::class, [
					'choices'  => $this->get_output_type()
				]
			)
			->add('delimiter',
				ChoiceType::class, [
					'choices'  => $this->get_delimiter_type()
				]
			)
			
			->add('primary_fields',
				ChoiceType::class, [
					'multiple' => true,
					'expanded' => true,
					'choices'  => $options['export_fields']['primary_fields'],
					'attr' => array('class'=>'wk-primary-fields'),
					'disabled' => true,
					'data' => array_keys($options['export_fields']['primary_fields'])
				]
			)
			->add('other_fields',
				ChoiceType::class, [
					'multiple' => true,
					'expanded' => true,
					'choices'  => $options['export_fields']['other_fields'],
					'attr' => array('class'=>'wk-other-fields'),
					'data' => array_keys($options['export_fields']['other_fields'])
				]
			)
		;
	}

	public function get_output_type()
	{
		return [
			'Direct Download' => 'D',
			'Screen' => 'S'
		];
	}

	public function get_delimiter_type()
	{
		return [
			'Comma' => 'C',
			'Semicolon' => 'S',
			'Tab' => 'T'
		];
	}

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			// Configure your form options here
		]);
        $resolver->setRequired('export_fields');
	}
}
