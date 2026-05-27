<?php

namespace App\Enum;

enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Member = 'member';
    case Guest = 'guest';
}
