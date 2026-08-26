<?php

namespace App\Services\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupportRealtimeService
{
    public function changed(string $conversationId, string $event = 'changed'): void
    {
        $url = config('services.supabase.url');
        $key = config('services.supabase.service_role_key');
        if (! filled($url) || ! filled($key)) return;
        try {
            Http::withToken($key)->withHeaders(['apikey' => $key])->timeout(3)->post(rtrim($url, '/').'/realtime/v1/api/broadcast', ['messages' => [['topic' => 'support:'.$conversationId, 'event' => $event, 'payload' => ['conversation_id' => $conversationId], 'private' => false]]]);
        } catch (\Throwable $exception) {
            Log::warning('Support realtime broadcast failed.', ['conversation_id' => $conversationId, 'error' => $exception->getMessage()]);
        }
    }
}
