<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use App\Models\Like;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_un_usuario_autenticado_puede_crear_un_post_con_datos_validos()
    {
        $usuario = User::factory()->create();

        $respuesta = $this->actingAs($usuario, 'api')
            ->postJson('/api/posts', [
                'titulo' => 'Mi primer post de prueba automatizado',
                'contenido' => 'Este es el contenido completo del post para el test.'
            ]);

        $respuesta->assertStatus(201);
        $respuesta->assertJsonStructure([
            'message',
            'post' => ['id', 'titulo', 'contenido', 'user_id']
        ]);
        
        $this->assertDatabaseHas('posts', [
            'titulo' => 'Mi primer post de prueba automatizado',
            'user_id' => $usuario->id
        ]);
    }

    /** @test */
    public function test_un_usuario_no_puede_eliminar_un_post_que_no_le_pertenece()
    {
        $autorDelPost = User::factory()->create();
        $usuarioIntruso = User::factory()->create();

        $post = Post::factory()->create(['user_id' => $autorDelPost->id]);

        $respuesta = $this->actingAs($usuarioIntruso, 'api')
            ->deleteJson("/api/posts/{$post->id}");

        $respuesta->assertStatus(403); 
        $respuesta->assertJsonFragment([
            'message' => 'No podés eliminar un post que no creaste.'
        ]);

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    /** @test */
    public function test_un_usuario_no_puede_likear_dos_veces_el_mismo_post_comportamiento_toggle()
    {
        $usuario = User::factory()->create();
        $post = Post::factory()->create();

        $primerLike = $this->actingAs($usuario, 'api')
            ->postJson("/api/posts/{$post->id}/like");

        $primerLike->assertStatus(200);
        $primerLike->assertJson([
            'status' => 'added',
            'likes_count' => 1
        ]);
        $this->assertDatabaseHas('likes', ['user_id' => $usuario->id, 'post_id' => $post->id]);

        $segundoLike = $this->actingAs($usuario, 'api')
            ->postJson("/api/posts/{$post->id}/like");

        $segundoLike->assertStatus(200);
        $segundoLike->assertJson([
            'status' => 'removed',
            'likes_count' => 0
        ]);
        
        $this->assertDatabaseMissing('likes', ['user_id' => $usuario->id, 'post_id' => $post->id]);
    }
}
