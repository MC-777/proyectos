<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function updateOrDelete(User $user, Post $post): bool
    {
        // Retorna verdadero solo si el ID del usuario autenticado coincide con el usuario creador del post
        return $user->id === $post->user_id;
    }
}
