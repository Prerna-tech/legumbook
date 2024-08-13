<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;
    protected $table = "messages";
    protected $fillable = [
        'sender_id', 'receiver_id', 'message', 'readed_on',
    ];

    public function user()
  {
    return $this->hasOne(Users::class, 'id', 'receiver_id');
  }

  

  // public function unreadMessage(){
  //     return $this->hasMany(Message::whereNotNull('readed_on')
  //     ->where('sender_id', Auth::id()));
  // }

}
