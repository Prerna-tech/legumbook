<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HashTag extends Model
{
    use HasFactory;
    protected $table = "hashtag";
    protected $fillable = ['tag'];
    // public function posts()
    // {
    //     return $this->belongsToMany(Post::class,"hashtag",'id','post_id');
    // }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'hashtag_posts','hashtag_id', 'post_id');
    }
        
}
