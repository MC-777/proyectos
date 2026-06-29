<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LikeController extends Controller
{
    public function toggle($id)
    {
        $post = Post::find($id);
    
        if (!$post) {
            return response()->json([
                'status' => 'error',
                'message' => 'El post al que intentás dar like no existe.'
            ], 404); 
        }

        $userId = auth('api')->user()->id;

        $resultado = DB::transaction(function () use ($post, $userId) {
            
            $likeExistente = Like::where('user_id', $userId)
                                 ->where('post_id', $post->id)
                                 ->first();

            if ($likeExistente) { 
                $likeExistente->delete();
                return ['status' => 'removed', 'message' => 'Like quitado con éxito'];
            }

            try {
                Like::create([
                    'user_id' => $userId,
                    'post_id' => $post->id
                ]);
                return ['status' => 'added', 'message' => 'Like dado con éxito'];
            } catch (\Illuminate\Database\QueryException $e) {
                Like::where('user_id', $userId)->where('post_id', $post->id)->delete();
                return ['status' => 'removed', 'message' => 'Like quitado con éxito'];
            }
        });

        return response()->json([
            'message' => $resultado['message'],
            'status' => $resultado['status'],
            'likes_count' => $post->likes()->count()
        ], 200);
    }
}
