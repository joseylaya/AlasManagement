<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'company_name', 'value' => 'ALAS Clothing', 'type' => 'string', 'group' => 'company'],
            ['key' => 'low_stock_threshold', 'value' => '10', 'type' => 'integer', 'group' => 'inventory'],
            ['key' => 'currency_symbol', 'value' => '₱', 'type' => 'string', 'group' => 'general'],
            ['key' => 'contact_phone', 'value' => '+63 917 123 4567', 'type' => 'string', 'group' => 'company'],
            ['key' => 'contact_email', 'value' => 'contact@alasclothing.com', 'type' => 'string', 'group' => 'company'],
        ];

        foreach ($settings as $s) {
            Setting::setKey($s['key'], $s['value'], $s['type'], $s['group']);
        }
    }
}
