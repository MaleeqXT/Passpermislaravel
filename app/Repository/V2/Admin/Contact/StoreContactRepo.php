<?php

namespace App\Repository\V2\Admin\Contact;

use App\Models\Roles\Admin\Contact\ContactUs;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StoreContactRepo
{
    /**
     * @param array $attributes
     * @return Model|Builder| ContactUs
     */
    public static function run(array $attributes): Model|null|ContactUs
    {
        try {
            return ContactUs::query()->create($attributes);
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to Create ContactUs: ' . $e->getMessage());
            return null;
        }
    }
}
