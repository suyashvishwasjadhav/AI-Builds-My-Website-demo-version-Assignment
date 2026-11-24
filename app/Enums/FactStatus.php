<?php

namespace App\Enums;

enum FactStatus: string
{
    case Candidate = 'candidate';
    case Confirmed = 'confirmed';
    case Conflict = 'conflict';
    case Rejected = 'rejected';
    case UserProvided = 'user_provided';
}
