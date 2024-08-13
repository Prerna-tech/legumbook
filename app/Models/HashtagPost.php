<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HashtagPost extends Model
{
    use HasFactory;
    protected $table = "hashtag_posts";
    protected $fillable = [
        'post_id','hashtag_id'
    ];
  
}
