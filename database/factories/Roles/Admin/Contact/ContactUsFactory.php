<?php

namespace database\factories\Roles\Admin\Contact;

use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Admin\Contact\ContactUs;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactUs>
 */
class ContactUsFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ContactUs::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => $this->faker->firstName,
            'prenom' => $this->faker->lastName,
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->email,
            'message' => $this->faker->text,
            'subject' => $this->faker->name,
        ];
    }
}
