<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostReport extends Model
{
    use HasFactory;

    public function user(){
        return $this->belongsTo(Users::class,'reporting_user_id', 'id');
    }
    public function post() {
        return $this->belongsTo(Post::class, 'reported_post_id', 'id'); 
    }
}
