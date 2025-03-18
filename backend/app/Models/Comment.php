<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    //
    protected $fillable = ["user_id", "comment", "post_id"];

    public function user()
    {
        return $this->belongsTo(User::class, "user_id", "id")->select("id", "profile_image", "username", "name");
    }
    public function post()
    {
        return $this->belongsTo(Post::class, "post_id", "id");
    }
}
