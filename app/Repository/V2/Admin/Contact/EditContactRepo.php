<?php

namespace App\Repository\V2\Admin\Contact;

use App\Models\Roles\Admin\Contact\ContactUs;

class EditContactRepo
{
    /**
     * @param ContactUs $contactUs
     * @param array $attributes
     * @return bool
     */
    public static function run(ContactUs $contactUs, array $attributes): bool
    {
        try {
            return $contactUs->update($attributes);
        } catch (\Exception $e) {
            // Log the error or handle it as needed
            info('Failed to update ContactUs: ' . $e->getMessage());
            return false;
        }
    }
}
