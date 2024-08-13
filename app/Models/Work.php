<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Work extends Model
{
    use HasFactory;
    protected $table ="work";
    protected $fillable = [
        'user_id', 'title', 'type', 'company_name', 'location', 
        'employment_mode', 'start_date', 'current_working', 'end_date', 'work_description'
    ];
    public $timestamps = false;

    public function Users()
    {
        return $this->belongsTo(Users::class, 'id', 'user_id');
    }

    
    public function jobs() {
        return $this->belongsTo(Jobs::class, 'user_id', 'id'); 
    }

}
