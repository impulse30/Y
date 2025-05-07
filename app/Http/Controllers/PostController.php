<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with('user', 'likes')->latest()->get()->map(function ($post) {
            return [
                'id' => $post->id,
                'content' => $post->content,
                'created_at' => $post->created_at,
                'likes_count' => $post->likes->count(),
                'is_liked_by_current_user' => $post->likes->contains('user_id', auth()->id()),
                'user' => [
                    'id' => $post->user->id,
                    'name' => $post->user->name,
                    'email' => $post->user->email
                ],
            ];
        });

        return response()->json(['data' => $posts]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'content' => 'required|string',
            ]);

            $post = Post::create([
                'content' => $validated['content'],
                'user_id' => $request->user()->id, // Plus propre que auth()->user()->id
            ]);

            return response()->json(['data' => $post], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la création du post.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function like($id)
{
    $post = Post::findOrFail($id);
    $user = auth()->user();

    if ($post->likes()->where('user_id', $user->id)->exists()) {
        return response()->json(['message' => 'Already liked'], 400);
    }

    $post->likes()->create([
        'user_id' => $user->id,
    ]);

    return response()->json(['message' => 'Post liked']);
}

public function unlike($id)
{
    $post = Post::findOrFail($id);
    $user = auth()->user();

    $like = $post->likes()->where('user_id', $user->id)->first();

    if (!$like) {
        return response()->json(['message' => 'Not liked yet'], 400);
    }

    $like->delete();

    return response()->json(['message' => 'Post unliked']);
}


}
