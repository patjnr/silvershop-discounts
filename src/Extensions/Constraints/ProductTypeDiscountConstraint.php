<?php

namespace SilverShop\Discounts\Extensions\Constraints;

use SilverShop\Discounts\Model\Discount;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ListboxField;
use SilverShop\Model\OrderItem;
use SilverStripe\Core\ClassInfo;

class ProductTypeDiscountConstraint extends ItemDiscountConstraint
{
    private static $db = [
        'ProductTypes' => 'Text'
    ];

    public function updateCMSFields(FieldList $fields)
    {
        //multiselect subtypes of orderitem
        if ($this->owner->isInDB() && $this->owner->ForItems) {
            $fields->addFieldToTab(
                'Root.Constraints.ConstraintsTabs.Product',
                ListBoxField::create(
                    'ProductTypes',
                    _t(__CLASS__.'.PRODUCTTYPES', 'Product types'),
                    $this->getTypes(false, $this->owner)
                )->setMultiple(true)
            );
        }
    }

    public function check(Discount $discount)
    {
        $types = $this->getTypes(true, $discount);
        //valid if no categories defined
        if (!$types) {
            return true;
        }
        $incart = $this->itemsInCart($discount);
        if (!$incart) {
            $this->error(_t(__CLASS__.'.PRODUCTTYPESNOTINCART', 'The required product type(s), are not in the cart.'));
        }

        return $incart;
    }

    /**
     * This function is used by ItemDiscountAction, and the check function above.
     * @param OrderItem $item
     * @param Discount $discount
     * @return bool
     */
    public function itemMatchesCriteria(OrderItem $item, Discount $discount)
    {
        $types = $this->getTypes(true, $discount);
        if (!$types) {
            return true;
        }
        $buyable = $item->Buyable();
        return isset($types[$buyable->class]);
    }

    protected function getTypes($selected, Discount $discount)
    {
        $types = $selected ? array_filter(explode(',', $discount->ProductTypes)) : $this->BuyableClasses();
        if ($types && !empty($types)) {
            $types = array_combine($types, $types);
            foreach ($types as $type => $name) {
                $types[$type] = singleton($type)->i18n_singular_name();
            }
            return $types;
        }
    }

    protected function BuyableClasses()
    {
        $implementors = ClassInfo::implementorsOf('Buyable');
        $classes = [];
        foreach ($implementors as $key => $class) {
            $classes = array_merge($classes, array_values(ClassInfo::subclassesFor($class)));
        }
        $classes = array_combine($classes, $classes);
        unset($classes['ProductVariation']);
        return $classes;
    }
}
