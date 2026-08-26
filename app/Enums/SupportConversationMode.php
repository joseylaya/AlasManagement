<?php

namespace App\Enums;

enum SupportConversationMode: string
{
    case AI_ACTIVE = 'AI_ACTIVE';
    case HUMAN_ACTIVE = 'HUMAN_ACTIVE';
    case AI_PAUSED = 'AI_PAUSED';
    case RESOLVED = 'RESOLVED';
}
