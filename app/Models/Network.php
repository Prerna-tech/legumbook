<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Network extends Model
{
    use HasFactory;
    protected $table = "network";
    protected $fillable = [
        'sender_id', 'receiver_id', 'requested_at', 'accepted_at'
    ];

    public function user()
    {
         return $this->hasMany(Users::class,'id','receiver_id')->where('id','!=',Auth::id());
    }
    public function sender(){
        return $this->hasMany(Users::class,'id','sender_id')->where('id','!=',Auth::id());
    }

    public function messageUser(){
        $receiver= $this->hasOne(Users::class,'id','receiver_id')->where('id','!=',Auth::id());
        $sender= $this->hasOne(Users::class,'id','sender_id')->where('id','!=',Auth::id());
        
        if (is_null($sender)) {
        //   dd($sender) ;
        } else{
            return $receiver;
        }
        
    }
    public function UserProfile(){
        return $this->hasOne(UserProfile::class,'user_id', 'sender_id');
    }
    public function jobs() {
        return $this->belongsTo(Jobs::class, 'user_id', 'id'); 
    }
    public function education()
    {
        return $this->hasMany(Education::class, 'user_id', 'id');
    }

  // Network.php
public function lastMessage(){
    return $this->hasOne(Message::class, 'network_id', 'id')->orderBy('id', 'DESC');
}


public function unreadMessage() {
    return $this->hasMany(Message::class, 'network_id', 'id')->whereNull('readed_on')->where('sender_id','!=',Auth::id());
}

    
   
}
