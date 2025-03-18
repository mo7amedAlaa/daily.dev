<?php

namespace App\Http\Controllers\API;

use App\Events\PostBroadCastEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\PostRequest;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Log;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */ public function index(Request $request)
    {
        $sortBy = $request->input('sort_by', 'newest');
        $searchQuery = $request->input('query', '');


        $query = Post::select("id", "user_id", "title", "image_url", "url", "created_at", "vote", "comment_count", "description")
            ->with('user')
            ->withCount([
                'votes as up_votes' => fn($query) => $query->where('vote_type', 'up'),
                'votes as down_votes' => fn($query) => $query->where('vote_type', 'down')
            ]);

        // Apply search query if provided
        if ($searchQuery) {
            $query->where(function ($query) use ($searchQuery) {
                $query->where('title', 'like', '%' . $searchQuery . '%')
                    ->orWhere('description', 'like', '%' . $searchQuery . '%');
            });
        }

        // Apply sorting
        if ($sortBy === 'upvotes') {
            $query->orderByDesc('up_votes');
        } elseif ($sortBy === 'downvotes') {
            $query->orderByDesc('down_votes');
        } else {
            $query->orderByDesc('created_at');
        }

        // Fetch paginated results
        $posts = $query->cursorPaginate(12);

        return response()->json($posts);
    }







    /**
     * Store a newly created resource in storage.
     */
    public function store(PostRequest $request)
    {
        $payload = $request->validated();
        try {
            $user = $request->user();
            $payload["user_id"] = $user->id;
            $post = Post::create($payload)->with("user")->orderByDesc("id")->first();
            PostBroadCastEvent::dispatch($post);
            return response()->json(["message" => "Post created successfully!", "post" => $post]);
        } catch (\Exception $err) {
            Log::info("post-error => " . $err->getMessage());
            return response()->json(["message" => "something went wrong.please try again!"], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //

    }
    public function vote(Request $request, string $id)
    {
        $post = Post::find($id);
        $payload = $request->validate([
            'vote' => 'required|in:-1,0,1'
        ]);
        if ($post) {
            $user = Auth::user();
            if ($user && $user->id != $post->user_id) {
                if ($payload['vote'] == 1) {
                    $post->increment('vote', 1);
                } else if ($payload['vote'] == -1) {
                    $post->decrement('vote', 1);
                }
                $post->save();
                return response()->json(['message' => 'Vote updated successfully!', 'vote' => $post->vote]);
            } else {
                return response()->json(['message' => 'You cannot vote for your own post!'], 403);
            }
        } else {
            return response()->json(['message' => 'Post not found!'], 404);
        }
    }
}
