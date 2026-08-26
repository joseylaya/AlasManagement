<?php

namespace App\Services\Support;

use App\Models\SupportConversation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class SupportCustomerAuthenticator
{
    public function authorize(Request $request, SupportConversation $conversation): void
    {
        $token = $request->bearerToken() ?: $request->header('X-Support-Token');
        $expected = $conversation->customer->access_token_hash;

        if (! is_string($token) || ! is_string($expected) || ! hash_equals($expected, hash('sha256', $token))) {
            throw new AuthorizationException('This support conversation is not available to this visitor.');
        }
    }
}
