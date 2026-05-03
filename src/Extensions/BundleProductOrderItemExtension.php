<?php

declare(strict_types=1);

namespace SilverShop\Bundles\Extensions;

use SilverShop\Bundles\Page\BundleProduct;
use SilverShop\Model\OrderItem as BaseOrderItem;
use SilverShop\Model\Product\OrderItem as ProductOrderItem;
use SilverStripe\Core\Extension;

/**
 * When a bundle is placed on an order, decrement stock on each bundled product
 * for the purchased quantity (delegates to {@see decrementStock} when present).
 *
 * @extends Extension<BaseOrderItem>
 */
class BundleProductOrderItemExtension extends Extension
{
    public function onPlacement(): void
    {
        if (!$this->owner instanceof ProductOrderItem) {
            return;
        }

        if (!empty($this->owner->ProductVariationID)) {
            return;
        }

        $product = $this->owner->Product();
        if (!$product instanceof BundleProduct) {
            return;
        }

        $quantity = (int) $this->owner->Quantity;
        $proxy = ProductOrderItem::create();
        $proxy->Quantity = $quantity;

        foreach ($product->BundledProducts() as $bundled) {
            if ($bundled->hasMethod('decrementStock')) {
                $bundled->decrementStock($proxy);
            }
        }
    }
}
