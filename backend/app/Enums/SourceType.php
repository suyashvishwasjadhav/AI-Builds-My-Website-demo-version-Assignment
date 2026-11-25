<?php

namespace App\Enums;

enum SourceType: string
{
    case File = 'file';
    case Link = 'link';
    case Search = 'search';
    case User = 'user';
}
