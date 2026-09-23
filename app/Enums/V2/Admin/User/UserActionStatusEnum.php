<?php

namespace App\Enums\V2\Admin\User;

enum UserActionStatusEnum: string
{
    case CREATED = 'create';
    case UPDATED = 'update';
    case DELETED = 'delete';
}
