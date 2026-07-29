<?php

namespace Database\Seeders;

use App\Actions\CreateOrderAction;

use App\Actions\CreateProductAction;
use App\Actions\RecordExpenseAction;
use App\Actions\RecordOwnerWithdrawalAction;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('role', 'owner')->first();
        $manager = User::where('role', 'manager')->first();

        $productsData = [
            [
                'product_name' => 'ALAS Signature Oversized Heavyweight Tee - Black',
                'sku' => 'ALAS-OS-BLK-L',
                'category' => 'T-Shirts',
                'color' => 'Black',
                'size' => 'L',
                'description' => '240 GSM Premium Cotton Oversized Streetwear T-shirt.',
                'selling_price' => 750.00,
                'cost_price' => 320.00,
                'initial_stock' => 35,
                'min_stock_threshold' => 10,
            ],
            [
                'product_name' => 'ALAS Signature Oversized Heavyweight Tee - White',
                'sku' => 'ALAS-OS-WHT-M',
                'category' => 'T-Shirts',
                'color' => 'White',
                'size' => 'M',
                'description' => '240 GSM Premium White Oversized Streetwear T-shirt.',
                'selling_price' => 750.00,
                'cost_price' => 320.00,
                'initial_stock' => 4, // Low stock trigger!
                'min_stock_threshold' => 10,
            ],
            [
                'product_name' => 'ALAS Heavy Duty Boxy Hoodie - Slate Grey',
                'sku' => 'ALAS-HWT-GRY-XL',
                'category' => 'Hoodies',
                'color' => 'Slate Grey',
                'size' => 'XL',
                'description' => '400 GSM Fleece Lined Heavy Hoodie with minimal embroidery.',
                'selling_price' => 1650.00,
                'cost_price' => 780.00,
                'initial_stock' => 18,
                'min_stock_threshold' => 8,
            ],
            [
                'product_name' => 'ALAS Minimalist Washed Cap - Stealth Black',
                'sku' => 'ALAS-CAP-BLK-OS',
                'category' => 'Accessories',
                'color' => 'Black',
                'size' => 'One Size',
                'description' => 'Unstructured 6-panel washed cotton strapback cap.',
                'selling_price' => 450.00,
                'cost_price' => 180.00,
                'initial_stock' => 2, // Low stock trigger!
                'min_stock_threshold' => 10,
            ],
            [
                'product_name' => 'ALAS Cargo Utility Pants - Olive Green',
                'sku' => 'ALAS-CRG-OLV-M',
                'category' => 'Pants',
                'color' => 'Olive Green',
                'size' => 'M',
                'description' => 'Tactical relaxed-fit cargo pants with adjustable hem.',
                'selling_price' => 1250.00,
                'cost_price' => 520.00,
                'initial_stock' => 15,
                'min_stock_threshold' => 5,
            ]
        ];

        $createdProducts = [];
        foreach ($productsData as $pData) {
            $createdProducts[] = CreateProductAction::execute($pData, $manager);
        }

        // Seed Sample Orders
        if (count($createdProducts) >= 3) {
            // Order 1: Completed Shipping Order
            CreateOrderAction::execute(
                [
                    'customer_name' => 'Juan Dela Cruz',
                    'customer_phone' => '0917-888-9999',
                    'customer_email' => 'juan@gmail.com',
                    'delivery_method' => 'shipping',
                    'shipping_address' => '123 Bonifacio High Street, Taguig City, Metro Manila',
                    'order_status' => 'completed',
                    'payment_status' => 'paid',
                    'payment_method' => 'GCash',
                    'notes' => 'Deliver on weekday afternoon',
                ],
                [
                    ['product_id' => $createdProducts[0]->id, 'quantity' => 2, 'unit_price' => 750.00],
                    ['product_id' => $createdProducts[2]->id, 'quantity' => 1, 'unit_price' => 1650.00],
                ],
                $manager
            );

            // Order 2: Pending Meetup Order
            CreateOrderAction::execute(
                [
                    'customer_name' => 'Maria Clara',
                    'customer_phone' => '0918-123-4567',
                    'customer_email' => 'maria@yahoo.com',
                    'delivery_method' => 'meetup',
                    'meetup_date' => date('Y-m-d', strtotime('+1 day')),
                    'meetup_location' => 'SM Mall of Asia Main Entrance',
                    'order_status' => 'pending',
                    'payment_status' => 'pending',
                    'payment_method' => 'Cash on Meetup',
                    'notes' => 'Meetup scheduled at 3:00 PM',
                ],
                [
                    ['product_id' => $createdProducts[1]->id, 'quantity' => 1, 'unit_price' => 750.00],
                    ['product_id' => $createdProducts[3]->id, 'quantity' => 1, 'unit_price' => 450.00],
                ],
                $manager
            );

            // Order 3: Packed Shipping Order
            CreateOrderAction::execute(
                [
                    'customer_name' => 'Alex Santos',
                    'customer_phone' => '0920-555-1234',
                    'delivery_method' => 'shipping',
                    'shipping_address' => '45 Session Road, Baguio City',
                    'order_status' => 'packed',
                    'payment_status' => 'paid',
                    'payment_method' => 'Bank Transfer',
                ],
                [
                    ['product_id' => $createdProducts[0]->id, 'quantity' => 1, 'unit_price' => 750.00],
                ],
                $manager
            );
        }

        // Seed Sample Expenses
        $opsCat = ExpenseCategory::where('name', 'Operations')->first();
        $mktCat = ExpenseCategory::where('name', 'Marketing')->first();

        if ($opsCat) {
            RecordExpenseAction::execute([
                'expense_category_id' => $opsCat->id,
                'amount' => 1200.00,
                'expense_date' => date('Y-m-d'),
                'description' => 'Custom ALAS Polymailer bags and courier stickers pack',
            ], $manager);
        }

        if ($mktCat) {
            RecordExpenseAction::execute([
                'expense_category_id' => $mktCat->id,
                'amount' => 2500.00,
                'expense_date' => date('Y-m-d'),
                'description' => 'Meta & TikTok video ad campaign budget for Summer Drop',
            ], $owner);
        }

        // Seed Sample Owner Withdrawal
        RecordOwnerWithdrawalAction::execute([
            'amount' => 1500.00,
            'drawal_date' => date('Y-m-d'),
            'reason' => 'Owner monthly equity drawdown',
        ], $owner);
    }
}
