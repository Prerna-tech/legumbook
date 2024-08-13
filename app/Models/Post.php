<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\PostController;

class Post extends Model
{
  use HasFactory;
  protected $table = "post";
  protected $fillable = [
    'user_id', 'image', 'post_description','event_end_at','event_title','event_start_at','library_title','library_link'
  ];
  
  public function user()
  {
    return $this->hasOne(Users::class, 'id', 'user_id');
  }

  public function hashtags(): BelongsToMany
  {
    return $this->belongsToMany(HashTag::class,'hashtag_posts','post_id','hashtag_id');
  }

  public function like()
  {
    return $this->hasMany(LikePost::class,'post_id');
  }

  public function interested()
  {
    return $this->hasMany(Interested::class,'post_id');
  }
  public function comment()
  {
    return $this->hasMany(PostComment::class, 'post_id', 'id');
  }
  public function PostImage()
  {
    return $this->hasMany(PostImage::class,  'post_id','id');
  }
  public function likedBy()
  {
      return $this->hasMany(LikePost::class, 'post_id')->where('user_id',Auth::id());
  }
  public function UserProfile(){
    return $this->hasOne(UserProfile::class,'user_id', 'user_id');
  }

  public function is_interested(){
    return $this->hasOne(Interested::class,'post_id')->where('user_id','=',Auth::id());
    
  }
  public function users()
  {
     return $this->belongsTo(Users::class,'user_id', 'id');
  }
  public function likedBys()
  {
      return $this->hasMany(LikePost::class, 'post_id','id');
  }

 
    public function getLinkAttribute($value)
    {
      if(!$value){
        return null;
      }
      $post=new PostController();
        return $post->linkToData($value);
    }

}
