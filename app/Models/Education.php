<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    use HasFactory;
    protected $table = "education";
    protected $fillable = [
        'user_id', 'institution', 'degree',
        'start_date', 'end_date', 'education_description'
    ];
    public function jobs()
    {
        return $this->belongsTo(Jobs::class, 'user_id', 'user_id');
    }
}
