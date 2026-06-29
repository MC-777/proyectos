<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Services\LlmService;

class PostController extends Controller
{
    public function index()
    {

        // Carga el usuario de inmediato para evitar N+1
        // Cuenta los comentarios y likes agregando campos virtuales automáticamente
        $posts = Post::with('user:id,name')
            ->withCount(['comentarios', 'likes'])
            ->latest()
            ->paginate(10);
        
        if ($posts->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No hay posts publicados por el momento.',
                'data' => []
            ], 200); 
        }

        return response()->json($posts, 200);
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'titulo' => 'required|string|max:255',
            'contenido' => 'required|string',
        ]);

        $post = auth('api')->user()->posts()->create($fields);

        return response()->json([
            'message' => 'Post creado con éxito',
            'post' => $post
        ], 201);
    }
 
    public function show($id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'status' => 'error',
                'message' => 'El post que buscas no existe.'
             ], 404);
        }

        // Carga el autor del post, los comentarios y para evitar N+1 precarga el autor de cada comentario.
        // El campo virtual 'likes_count' mediante subconsulta.
        $post->load([
            'user:id,name', 
            'comentarios.user:id,name'
        ])->loadCount('likes');

        return response()->json($post, 200);
    }

    public function update(Request $request, $id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'status' => 'error',
                'message' => 'El post que intentás editar no existe.'
             ], 404);
        }

        if (Gate::denies('updateOrDelete', $post)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No podés editar un post que no creaste.'
                ], 403); 
            }

        $fields = $request->validate([
            'titulo' => 'required|string|max:255',
            'contenido' => 'required|string',
        ]);

        $post->update($fields);
        return response()->json([
            'message' => 'Post actualizado con éxito',
            'post' => $post
        ], 200);
    }

    public function destroy($id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'status' => 'error',
                'message' => 'El post que intentás eliminar no existe.'
             ], 404);
        }

        if (Gate::denies('updateOrDelete', $post)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No podés eliminar un post que no creaste.'
                ], 403); 
            }

        $post->delete();
        return response()->json(['message' => 'Post eliminado con éxito'], 200);
    }


public function summary($id, LlmService $llmService)
{
    $post = Post::find($id);
    
    if (!$post) {
        return response()->json([
            'status' => 'error',
            'message' => 'El post que intentás resumir no existe.'
        ], 404); 
    }

    $post->load('comentarios:id,post_id,contenido');

    $resultadoLlm = $llmService->generarResumen(
        $post->titulo,
        $post->contenido,
        $post->comentarios->toArray()
    );

    $jsonDecodificado = json_decode($resultadoLlm, true);

    return response()->json([
        'post_id' => $post->id,
        'llm_used' => config('services.llm.provider'),
        'analysis' => $jsonDecodificado ?? $resultadoLlm
    ], 200);
}

}
