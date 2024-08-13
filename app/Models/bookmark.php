<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class bookmark extends Model
{
    use HasFactory;
    

    public function job()
    {
        return $this->belongsTo(Jobs::class, 'job_id', 'id');
    }
    public function user()
  {
    return $this->hasOne(Users::class, 'id','user_id');
  }
  public function JobFeatures()
  {
    return $this->hasMany(JobFeatures::class, 'job_id' ,'job_id');
    
  }
  
  
}
