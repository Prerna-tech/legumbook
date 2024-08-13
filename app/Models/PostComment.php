<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostComment extends Model
{
    use HasFactory;
    protected $table = "post_comment";
    protected $fillable = [
        'user_id', 'post_id','parent_id','comment' 
    ];
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\Users')->select(['id', 'name','image']);
    }

    public function replies()
    {
        return $this->hasMany(PostComment::class, 'parent_id');
    }

}
