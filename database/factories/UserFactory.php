<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = User::class;


    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $list = [
            'https://gravatar.com/avatar/9f0a58b1b251c5be168510d4c34d0ca5?s=400&d=robohash&r=x',
            'https://gravatar.com/avatar/9f0a58b1b251c5be168510d4c34d0ca5?s=400&d=retro&r=x',
            'https://gravatar.com/avatar/9f0a58b1b251c5be168510d4c34d0ca5?s=400&d=mp&r=pg',
            'https://gravatar.com/avatar/9f0a58b1b251c5be168510d4c34d0ca5?s=400&d=identicon&r=pg',
            'https://gravatar.com/avatar/9f0a58b1b251c5be168510d4c34d0ca5?s=400&d=monsterid&r=pg',
            'https://gravatar.com/avatar/9f0a58b1b251c5be168510d4c34d0ca5?s=400&d=wavatar&r=pg',
            'https://robohash.org/9f0a58b1b251c5be168510d4c34d0ca5?set=set4&bgset=&size=400x400',
        ];
        return [
            'first_name' => fake()->firstName,
            'last_name' => fake()->lastName,
            'email' => fake()->safeEmail,
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'remember_token' => Str::random(10),
            'media' => fake()->randomElement($list),
            // 'profile_photo_path' => null,
            'current_team_id' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }

    /**
     * Indicate that the user should have a personal team.
     */
    public function withPersonalTeam(callable $callback = null): static
    {
        if (!Features::hasTeamFeatures()) {
            return $this->state([]);
        }

        return $this->has(
            Team::factory()
                ->state(fn(array $attributes, User $user) => [
                    'first_name' => $user->first_name . '\'s Team',
                    'lest_name' => $user->lest_name . '\'s Team',
                    'user_id' => $user->id,
                    'personal_team' => true,
                ])
                ->when(is_callable($callback), $callback),
            'ownedTeams'
        );
    }
}
