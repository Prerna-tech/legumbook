<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;






class Jobs extends Model
{
  use HasFactory;
  protected $table = "jobs";
  protected $fillable = [
    'role', 'titel', 'job_description'
  ];
  public function JobFeatures()
  {
    return $this->hasMany(JobFeatures::class, 'job_id', 'id');
  }

  public function jobfeature()
  {
    return $this->belongsToMany(Jobs::class, 'job_features', 'job_id', 'features');
  }

  public function user()
  {
    return $this->hasOne(Users::class, 'id', 'user_id');
  }
  public function ApllidJob()
  {
    return $this->belongsTo(ApllidJob::class, 'id', 'job_id');
  }
  public function work()
  {
    return $this->hasOne(Work::class, 'id', 'user_id');
  }
  public function applicant()
  {
    return $this->hasMany(ApllidJob::class, 'job_id');
  }
 
 
  public function bookmark(){
    return $this->hasOne(bookmark::class,'job_id')->where('user_id','=',Auth::id());
    
  }

  public function users()
  {
    
    return $this->belongsTo(Users::class,'user_id', 'id');
  }

  public function getExperienceAttribute()
  {
    return $this->JobFeatures->pluck('features')->toArray();
    // return $this->jobfeature ? $this->jobfeature->pluck('features')->toArray() : [];

  }
 
  

}
