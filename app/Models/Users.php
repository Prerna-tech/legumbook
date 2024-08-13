<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; 

class Users extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table ="users";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'gender',
        'password',
        'image',
        'google_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function post() {
        return $this->belongsTo(Post::class, 'user_id', 'id'); 
    }

    public function UserProfile(){
        return $this->hasOne(UserProfile::class,'user_id', 'id');
    }
    public function jobs() {
        return $this->belongsTo(Jobs::class, 'user_id', 'id'); 
    }
    public function education()
    {
        return $this->hasMany(Education::class, 'user_id', 'id');
    }
    public function work()
    {
        return $this->hasMany(Work::class, 'user_id', 'id');
    }
    public function postCount() {
        return $this->belongsTo(Post::class,'id','user_id'); 
    }
    public function followerCount() {
        return $this->belongsTo(Network::class,'id','receiver_id'); 
    }
    public function followingCount() {
        return $this->belongsTo(Network::class,'id','sender_id'); 
    }
    
    public function isFriendWith(Users $user) {
        return $this->network->contains($user);
    }

    public function UserProfiles(){
        return $this->hasMany(UserProfile::class,'user_id', 'id');
    }
    public function certifications()
    {
        return $this->hasMany(Certification::class, 'user_id', 'id');
    }


    public function ExtraActivity()
    {
        return $this->hasMany(ExtraCurricular::class, 'user_id', 'id');
    }

    public function hashtags()
    {
      return $this->hasMany(HashTag::class, 'post_id', 'id');
    }

//     public function friends()
    // {
    //     return $this->belongsToMany(User::class, 'user_friend', 'user_id', 'friend_id');
    // }
    
}
