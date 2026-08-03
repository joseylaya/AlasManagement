<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Feature tests exercise authenticated JSON endpoints directly; browsers retain CSRF protection.
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }
}
