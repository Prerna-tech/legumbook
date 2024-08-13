<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobFeatures extends Model
{
    use HasFactory;
    protected $table ="job_features";
    protected $fillable = [
        'job_id', 'features'
    ];

    public function jobs() {
        return $this->belongsTo(Jobs::class, 'job_id', 'id'); 
    }
}
