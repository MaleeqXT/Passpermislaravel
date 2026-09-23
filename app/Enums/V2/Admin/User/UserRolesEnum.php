<?php

namespace App\Enums\V2\Admin\User;

enum UserRolesEnum: int
{
    case ADMIN = 1;
    case STUDENT = 2;
    case MONITOR = 3;

    case SECRETARY = 4;
}
