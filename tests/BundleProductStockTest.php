<?php

declare(strict_types=1);

namespace SilverShop\Bundles\Tests;

use SilverShop\Bundles\Page\BundleProduct;
use SilverShop\Model\Order;
use SilverShop\Model\Product\OrderItem;
use SilverShop\Page\Product;
use SilverShop\Stock\Model\ProductWarehouse;
use SilverShop\Stock\Model\ProductWarehouseStock;
use SilverStripe\Dev\SapphireTest;

class BundleProductStockTest extends SapphireTest
{
    protected static $fixture_file = 'fixtures.yml';

    public function testOnPlacementDecrementsBundledProductStock(): void
    {
        /** @var BundleProduct $bundle */
        $bundle = $this->objFromFixture(BundleProduct::class, 'bundle');
        /** @var Product $a */
        $a = $this->objFromFixture(Product::class, 'component_a');
        /** @var Product $b */
        $b = $this->objFromFixture(Product::class, 'component_b');

        $warehouse = $this->objFromFixture(ProductWarehouse::class, 'warehouse');
        $this->setWarehouseStock($warehouse, $a, 10);
        $this->setWarehouseStock($warehouse, $b, 8);

        // OrderItem::Product() resolves the buyable from the Live stage.
        $bundle->publishRecursive();
        $a->publishRecursive();
        $b->publishRecursive();

        $order = Order::create(['Status' => 'Cart']);
        $order->write();

        $item = OrderItem::create([
            'ProductID' => $bundle->ID,
            'OrderID' => $order->ID,
            'Quantity' => 2,
        ]);
        $item->write();

        $item->onPlacement();

        $this->assertSame(8, $a->getWarehouseStockQuantity());
        $this->assertSame(6, $b->getWarehouseStockQuantity());
    }

    public function testCanPurchaseRequiresBundledStock(): void
    {
        /** @var BundleProduct $bundle */
        $bundle = $this->objFromFixture(BundleProduct::class, 'bundle');
        /** @var Product $a */
        $a = $this->objFromFixture(Product::class, 'component_a');

        $warehouse = $this->objFromFixture(ProductWarehouse::class, 'warehouse');
        $this->setWarehouseStock($warehouse, $a, 0);

        $bundle->publishRecursive();
        $a->publishRecursive();
        $this->objFromFixture(Product::class, 'component_b')->publishRecursive();

        $this->assertFalse($bundle->canPurchase(null, 1));
    }

    private function setWarehouseStock(ProductWarehouse $warehouse, Product $product, int $quantity): void
    {
        $data = [
            'WarehouseID' => $warehouse->ID,
            'ProductID' => $product->ID,
            'ProductClass' => $product->getClassName(),
        ];
        $stock = ProductWarehouseStock::get()->filter($data)->first();
        if (!$stock) {
            $stock = ProductWarehouseStock::create($data);
        }

        $stock->Quantity = (string) $quantity;
        $stock->write();
    }
}
