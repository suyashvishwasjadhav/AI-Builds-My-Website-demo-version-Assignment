<?php

namespace App\Enums;

enum SiteStatus: string
{
    case Draft = 'draft';
    case Ready = 'ready';
    case Published = 'published';
}
