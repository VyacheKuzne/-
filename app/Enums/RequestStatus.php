<?php

namespace App\Enums;

enum RequestStatus: string
{
    case NEW = 'new';
    case ASSIGNED = 'assigned';
    case IN_PROGRESS = 'in_progress';
    case DONE = 'done';
    case CANCELED = 'canceled';
}
