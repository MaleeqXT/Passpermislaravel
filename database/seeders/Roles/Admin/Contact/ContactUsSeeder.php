<?php

namespace database\seeders\Roles\Admin\Contact;

use App\Models\Roles\Admin\Contact\ContactUs;
use Illuminate\Database\Seeder;

class ContactUsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ContactUs::factory(10)->create();
    }
}
