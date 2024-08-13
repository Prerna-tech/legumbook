<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class UserProfile extends Model
{
    use HasFactory;
    protected $table = "user_profile";
    protected $fillable = [
        'user_id', 'designation', 'purpose_to_use_app',
        'dob','twitter','linkedin','facebook','instagram', 'addres1', 'addres2', 'city', 'state',
        'zip', 'country', 'bio','longitude','latitude'
    ];

    public function education()
    {
        return $this->hasMany(Education::class, 'user_id', 'user_id');
    }
    public function Users()
    {
        return $this->belongsTo(Users::class, 'id');
    }
}
