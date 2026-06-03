<?php

namespace App\Enum;

enum ProjectStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
