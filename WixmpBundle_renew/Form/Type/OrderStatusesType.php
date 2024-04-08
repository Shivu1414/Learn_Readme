<?php
/**
 *  Categories type input.
 */

namespace Webkul\Modules\Wix\WixmpBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webkul\Modules\Wix\WixmpBundle\Utils\SalesHelper;
/**
 * Defines the custom form field type used to manipulate tags values across
 * Bootstrap-tagsinput javascript plugin.
 *
 * See https://symfony.com/doc/current/cookbook/form/create_custom_field_type.html
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class OrderStatusesType extends AbstractType
{
    public function __construct(SalesHelper $salesHelper)
    {
        
        $this->salesHelper = $salesHelper;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $order_statuses = array_flip($this->salesHelper->get_order_status_list());
        $resolver->setDefaults([
            'choices' =>$order_statuses,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
