<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Product;
use App\Models\User;

class NotificationService
{
    public static function send(
        User|int $user,
        string $type,
        string $title,
        string $message,
        ?string $link = null
    ): Notification {
        $userId = $user instanceof User ? $user->id : $user;

        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => false,
        ]);
    }

    public static function notifyLowStock(Product $product, int $currentStock, int $minThreshold): void
    {
        $ownersAndManagers = User::whereIn('role', ['owner', 'manager'])->get();

        foreach ($ownersAndManagers as $user) {
            static::send(
                $user,
                'inventory.low_stock',
                'Low Stock Alert',
                "Product {$product->product_name} ({$product->sku}) is low on stock! Current stock: {$currentStock} (Minimum: {$minThreshold}).",
                '/inventory'
            );
        }
    }
}
