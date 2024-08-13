<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApllidJob extends Model
{
    use HasFactory;
    protected $table = "applied_job";
    protected $fillable = [
        'user_id', 'job_id'
    ];
   
    public function user()
  {
    return $this->hasOne(Users::class, 'id', 'user_id');
  }
  public function users()
  {
    
    return $this->belongsTo(Users::class,'user_id', 'id');
  }
  
}
