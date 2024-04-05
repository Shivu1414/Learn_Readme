<?php
/**
 *  Categories type input.
 */

namespace Webkul\Modules\Wix\WixmpBundle\Form\Type;

//use Webkul\Modules\Bigcommerce\MarketplaceBundle\Form\DataTransformer\CategoriesTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\CallbackTransformer;

/**
 * Defines the custom form field type used to manipulate tags values across
 * Bootstrap-tagsinput javascript plugin.
 *
 * See https://symfony.com/doc/current/cookbook/form/create_custom_field_type.html
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class CategoriesType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

        // The Tag collection must be transformed into a comma separated string.
        // We could create a custom transformer to do Collection <-> string in one step,
        // but here we're doing the transformation in two steps (Collection <-> array <-> string)
        // and reuse the existing CollectionToArrayTransformer.

        // ->addModelTransformer(new CategoriesTransformer($this));
        ->addModelTransformer(
            new CallbackTransformer(
                function ($dataAsString) {
                    if (empty($dataAsString)) {
                        $dataAsString = '';
                    }
                    // transform the string back to an array
                    return explode(',', $dataAsString);
                }, function ($dataAsArray) {
                    if (empty($dataAsArray)) {
                        $dataAsArray = [];
                    }
                    // transform the array to a string
                    return implode(',', $dataAsArray);
                }
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextType::class;
    }
}
