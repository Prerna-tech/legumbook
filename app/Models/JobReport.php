<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobReport extends Model
{
    use HasFactory;

    public function user(){
        return $this->belongsTo(Users::class,'reporting_user_id', 'id');
    }

    public function job(){
        return $this->belongsTo(Jobs::class,'reported_job_id', 'id');
    }
}
