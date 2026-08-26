<?php

namespace App\Contracts;

interface AiProvider
{
    public function generate(string $systemInstruction, array $messages, int $maxOutputTokens): array;
    public function embed(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array;
    public function name(): string;
}
