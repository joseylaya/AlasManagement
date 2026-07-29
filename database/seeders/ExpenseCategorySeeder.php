<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Operations', 'description' => 'Daily operational costs, utilities, packaging'],
            ['name' => 'Marketing', 'description' => 'Social media ads, promotions, photoshoot'],
            ['name' => 'Supplies', 'description' => 'Office supplies, hangers, tags, packaging bags'],
            ['name' => 'Transportation', 'description' => 'Courier delivery, gas, meetup transit'],
            ['name' => 'Salary', 'description' => 'Staff and worker payroll'],
            ['name' => 'Supplier Payment', 'description' => 'Fabric, printing, and manufacturing costs'],
            ['name' => 'Other', 'description' => 'Miscellaneous business expenses'],
        ];

        foreach ($categories as $cat) {
            ExpenseCategory::firstOrCreate(
                ['name' => $cat['name']],
                [
                    'description' => $cat['description'],
                    'status' => 'active',
                ]
            );
        }
    }
}
