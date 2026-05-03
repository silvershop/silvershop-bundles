<?php

declare(strict_types=1);

namespace SilverShop\Bundles\Extensions;

use SilverShop\Bundles\Page\BundleProduct;
use SilverShop\Model\Order;
use SilverShop\Model\Product\OrderItem as ProductOrderItem;
use SilverStripe\Core\Extension;

/**
 * Restores bundled component stock when an order is cancelled, mirroring silvershop/stock
 * behaviour for the primary line product.
 *
 * @extends Extension<Order>
 */
class BundleOrderExtension extends Extension
{
    public function onStatusChange(string $fromStatus, string $toStatus): void
    {
        if (!in_array($toStatus, ['AdminCancelled', 'MemberCancelled'], true)) {
            return;
        }

        foreach ($this->owner->Items() as $orderItem) {
            if (!$orderItem instanceof ProductOrderItem) {
                continue;
            }

            if (!empty($orderItem->ProductVariationID)) {
                continue;
            }

            $product = $orderItem->Product();
            if (!$product instanceof BundleProduct) {
                continue;
            }

            $proxy = ProductOrderItem::create();
            $proxy->Quantity = (int) $orderItem->Quantity;

            foreach ($product->BundledProducts() as $bundled) {
                if ($bundled->hasMethod('incrementStock')) {
                    $bundled->incrementStock($proxy);
                }
            }
        }
    }
}
