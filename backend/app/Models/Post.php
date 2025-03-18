<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    //
    protected $fillable = ["user_id", "title", "url", "image_url", "description", "vote", "comment_count"];

    public function user()
    {
        return $this->belongsTo(User::class, "user_id", "id")->select("id", "profile_image", 'username');
    }
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    public function votes()
    {
        return $this->hasMany(Vote::class);
    }
    public function getVotesCount($voteType)
    {
        return $this->votes()->where('vote_type', $voteType)->count();
    }
}
