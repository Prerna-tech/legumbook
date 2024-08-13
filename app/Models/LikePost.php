<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LikePost extends Model
{
    use HasFactory;
    protected $table = "post_like";
    protected $fillable = [
        'post_id', 'user_id'
    ];
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id', 'id');
    }
}
