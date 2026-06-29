<?php

namespace Database\Factories;

use App\Models\Comentario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comentario>
 */
class ComentarioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::all()->random()->id ?? \App\Models\User::factory(),
            'post_id' => \App\Models\Post::all()->random()->id ?? \App\Models\Post::factory(),
            'contenido' => $this->faker->sentence(),
        ];
    }

}
