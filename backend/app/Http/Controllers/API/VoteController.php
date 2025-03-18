<?php

namespace App\Http\Controllers\API;

use App\Events\VoteEvent;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Vote;
use Illuminate\Http\Request;

class VoteController extends Controller
{
    public function vote(Request $request, $postId)
    {
        $request->validate([
            'vote_type' => 'required|in:up,down',
        ]);

        $post = Post::with('user')
            ->withCount([
                'votes as up_votes' => function ($query) {
                    $query->where('vote_type', 'up');
                },
                'votes as down_votes' => function ($query) {
                    $query->where('vote_type', 'down');
                }
            ])
            ->findOrFail($postId);

        $existingVote = Vote::where('post_id', $postId)
            ->where('user_id', auth()->id())
            ->first();

        if ($existingVote) {
            if ($existingVote->vote_type == $request->vote_type) {
                return response()->json([
                    'message' => 'You have already voted with the same type.'
                ], 400);
            }

            // Update existing vote
            $existingVote->vote_type = $request->vote_type;
            $existingVote->save();

            // Dispatch event after vote update
            VoteEvent::dispatch($post);

            return response()->json([
                'message' => 'Your vote has been updated successfully.',
                'up_votes' => $post->up_votes,   // Updated count
                'down_votes' => $post->down_votes // Updated count
            ]);
        } else {
            // Create a new vote
            $vote = new Vote();
            $vote->post_id = $postId;
            $vote->vote_type = $request->vote_type;
            $vote->user_id = auth()->id();
            $vote->save();

            // Dispatch event after new vote
            VoteEvent::dispatch($post);

            return response()->json([
                'message' => 'Your vote has been recorded successfully.',
                'up_votes' => $post->up_votes,
                'down_votes' => $post->down_votes
            ]);
        }
    }

    public function votesCount($postId)
    {
        $post = Post::findOrFail($postId);
        return response()->json([
            'up_votes' => $post->getVotesCount('up'),
            'down_votes' => $post->getVotesCount('down')
        ]);
    }
}
