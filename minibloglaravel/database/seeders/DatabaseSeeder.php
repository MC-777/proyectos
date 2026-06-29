<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $usuarios = \App\Models\User::factory(10)->create();

        $posts = \App\Models\Post::factory(20)->recycle($usuarios)->create();

        \App\Models\Comentario::factory(50)->recycle($usuarios)->recycle($posts)->create();

        for ($i = 0; $i < 40; $i++) {
            try {
                \App\Models\Like::factory()->create([
                    'user_id' => $usuarios->random()->id,
                    'post_id' => $posts->random()->id,
                ]);
            } catch (\Exception $e) {
                continue;
            }
        }
    }

}
