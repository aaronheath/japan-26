<?php

namespace App\Enums;

enum PromptType: string
{
    case System = 'system';
    case Task = 'task';
    case Supplementary = 'supplementary';
}
