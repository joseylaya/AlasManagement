<?php

namespace App\Livewire\Settings;

use App\Models\ExpenseCategory;
use App\Models\Setting;
use App\Services\ActivityLogService;
use Exception;
use Livewire\Component;

class Index extends Component
{
    public string $company_name = 'ALAS Clothing';
    public int $low_stock_threshold = 10;
    public string $contact_phone = '';
    public string $contact_email = '';
    public string $currency_symbol = '₱';

    // New Category Form
    public string $newCategoryName = '';
    public string $newCategoryDescription = '';

    public function mount(): void
    {
        $this->company_name = Setting::getByKey('company_name', 'ALAS Clothing');
        $this->low_stock_threshold = (int) Setting::getByKey('low_stock_threshold', 10);
        $this->contact_phone = Setting::getByKey('contact_phone', '+63 917 123 4567');
        $this->contact_email = Setting::getByKey('contact_email', 'contact@alasclothing.com');
        $this->currency_symbol = Setting::getByKey('currency_symbol', '₱');
    }

    public function saveSettings(): void
    {
        Setting::setKey('company_name', $this->company_name, 'string', 'company');
        Setting::setKey('low_stock_threshold', $this->low_stock_threshold, 'integer', 'inventory');
        Setting::setKey('contact_phone', $this->contact_phone, 'string', 'company');
        Setting::setKey('contact_email', $this->contact_email, 'string', 'company');
        Setting::setKey('currency_symbol', $this->currency_symbol, 'string', 'general');

        ActivityLogService::log(
            'Settings Updated',
            "Updated system business configuration and inventory low-stock threshold to {$this->low_stock_threshold}."
        );

        session()->flash('success', 'System settings updated successfully!');
    }

    public function addCategory(): void
    {
        $this->validate([
            'newCategoryName' => 'required|string|max:100|unique:expense_categories,name',
        ]);

        try {
            ExpenseCategory::create([
                'name' => $this->newCategoryName,
                'description' => $this->newCategoryDescription,
                'status' => 'active',
            ]);

            session()->flash('success', "Expense category '{$this->newCategoryName}' added successfully.");
            $this->newCategoryName = '';
            $this->newCategoryDescription = '';
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $categories = ExpenseCategory::all();

        return view('livewire.settings.index', [
            'categories' => $categories,
        ])->layout('layouts.app', ['pageHeader' => 'Business Settings']);
    }
}
