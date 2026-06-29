<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Comentario;
use Illuminate\Http\Request;

class ComentarioController extends Controller
{
    public function index($id)
    {
        $post = Post::find($id);
    
        if (!$post) {
            return response()->json([
                'status' => 'error',
                'message' => 'El post que intentás ver los comentarios no existe.'
            ], 404); 
        }

        $comentarios = $post->comentarios()->with('user:id,name')->latest()->get();
        if ($comentarios->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'El post existe pero no posee comentarios aún.',
                'data' => []
            ], 200); 
        }
        return response()->json($comentarios, 200);
    }

    public function store(Request $request, $id)
    {
        $post = Post::find($id);
    
        if (!$post) {
            return response()->json([
                'status' => 'error',
                'message' => 'El post al que intentás agregar un comentario no existe.'
            ], 404); 
        }

        $fields = $request->validate([
            'contenido' => 'required|string|max:1000',
        ]);

        $comentario = Comentario::create([
            'contenido' => $fields['contenido'],
            'post_id' => $post->id,
            'user_id' => auth('api')->user()->id
        ]);

        return response()->json([
            'message' => 'Comentario publicado con éxito',
            'comentario' => $comentario->load('user:id,name') 
        ], 201);
    }
}
