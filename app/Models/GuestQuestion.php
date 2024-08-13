<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestQuestion extends Model
{
    use HasFactory;
    protected $table ="guest_question";
    protected $fillable = [
        'name', 'email', 'question', 'reply','replied_at'
    ];
    
}
