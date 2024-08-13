<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = "notification";
    protected $fillable = [
        'content','post_type', 'owner_id','user_id','readed_on'
    ];
    
    public function user()
  {
    return $this->hasOne(Users::class, 'id', 'user_id');
  }
}
