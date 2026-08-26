<?php

namespace App\Enums;

enum SupportConversationStatus: string
{
    case OPEN = 'OPEN';
    case NEEDS_ATTENTION = 'NEEDS_ATTENTION';
    case RESOLVED = 'RESOLVED';
    case ARCHIVED = 'ARCHIVED';
}
