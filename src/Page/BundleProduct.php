<?php

declare(strict_types=1);

namespace SilverShop\Bundles\Page;

use SilverShop\Page\Product;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Security\Member;

/**
 * A product that groups other {@link Product} records. Purchasing this product
 * removes stock from each bundled product (via {@see decrementStock} when available,
 * e.g. with silvershop/stock).
 *
 * @method ManyManyList<Product> BundledProducts()
 */
class BundleProduct extends Product
{
    private static string $table_name = 'SilverShop_BundleProduct';

    private static array $many_many = [
        'BundledProducts' => Product::class,
    ];

    private static string $singular_name = 'Bundle product';

    private static string $plural_name = 'Bundle products';

    private static string $description = 'A product made up of other products; stock is taken from each component when sold.';

    /**
     * @return array<string, string>
     */
    public function fieldLabels($includerelations = true): array
    {
        $labels = parent::fieldLabels($includerelations);
        $labels['BundledProducts'] = _t(__CLASS__ . '.BundledProducts', 'Bundled products');

        return $labels;
    }

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        $excludeID = $this->isInDB() ? $this->ID : 0;
        $source = Product::get()->exclude('ID', $excludeID)->sort('Title')->map('ID', 'Title')->toArray();

        $fields->addFieldToTab(
            'Root.Bundle',
            ListboxField::create(
                'BundledProducts',
                $this->fieldLabel('BundledProducts'),
                $source
            )->setDescription(
                _t(
                    __CLASS__ . '.BundledProductsDescription',
                    'Each of these products will have stock adjusted when this bundle is purchased (if stock support is installed).'
                )
            )
        );

        return $fields;
    }

    public function canPurchase(?Member $member = null, int $quantity = 1): bool
    {
        if (!parent::canPurchase($member, $quantity)) {
            return false;
        }

        if (!$this->BundledProducts()->exists()) {
            return false;
        }

        foreach ($this->BundledProducts() as $bundled) {
            if (!$bundled->canPurchase($member, $quantity)) {
                return false;
            }
        }

        return true;
    }

    public function validate(): ValidationResult
    {
        $result = parent::validate();

        if ($this->isInDB() && $this->BundledProducts()->filter('ID', $this->ID)->exists()) {
            $result->addFieldError(
                'BundledProducts',
                _t(__CLASS__ . '.CannotBundleSelf', 'A bundle cannot include itself.')
            );
        }

        return $result;
    }
}
