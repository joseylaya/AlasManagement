<?php

namespace App\Services\Ai;

use App\Models\StorefrontProduct;
use App\Models\SupportConversation;

class SupportProductRecommendationService
{
    public function recommend(SupportConversation $conversation, string $message): array
    {
        if (! preg_match('/\b(shirt|tee|tshirt|t-shirt|oversized|product|recommend|nindot|nice|available|naa|show|tan-?aw)\b/ui', $message)) return [];
        $terms = collect(preg_split('/\s+/u', mb_strtolower($message)))->filter(fn ($term) => mb_strlen($term) >= 3)->take(5);
        $query = StorefrontProduct::query()->where('status', 'active')->whereHas('variants', fn ($variants) => $variants->where('status', 'active')->whereHas('inventory', fn ($inventory) => $inventory->where('current_stock', '>', 0)));
        if ($terms->isNotEmpty()) {
            $query->where(function ($products) use ($terms) {
                foreach ($terms as $term) $products->orWhere('name', 'like', '%'.$term.'%')->orWhere('description', 'like', '%'.$term.'%')->orWhereHas('variants', fn ($variants) => $variants->where('category', 'like', '%'.$term.'%')->orWhere('color', 'like', '%'.$term.'%'));
            });
        }
        $products = $query->with(['variants' => fn ($variants) => $variants->where('status', 'active')->with('inventory')])->orderByDesc('is_featured')->orderBy('sort_order')->limit(3)->get();
        if ($products->isEmpty() && $terms->isNotEmpty()) {
            $products = StorefrontProduct::query()->where('status', 'active')->whereHas('variants', fn ($variants) => $variants->where('status', 'active')->whereHas('inventory', fn ($inventory) => $inventory->where('current_stock', '>', 0)))->with(['variants' => fn ($variants) => $variants->where('status', 'active')->with('inventory')])->orderByDesc('is_featured')->orderBy('sort_order')->limit(3)->get();
        }
        return $products->map(fn ($product) => ['product_id' => (string) $product->id, 'variant_id' => (string) optional($product->variants->first(fn ($variant) => ($variant->inventory?->current_stock ?? 0) > 0))->id])->filter(fn ($item) => filled($item['variant_id']))->values()->all();
    }
}
