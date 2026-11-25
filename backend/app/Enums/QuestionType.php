<?php

namespace App\Enums;

enum QuestionType: string
{
    case PickBusiness = 'pick_business';
    case ResolveConflict = 'resolve_conflict';
    case MissingInfo = 'missing_info';
    case FlaggedContent = 'flagged_content';
}
