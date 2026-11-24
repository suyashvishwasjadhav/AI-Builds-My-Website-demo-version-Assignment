<?php

namespace App\Enums;

enum SourceStatus: string
{
    case Pending = 'pending';
    case Read = 'read';
    case Blocked = 'blocked';
    case Failed = 'failed';
}
