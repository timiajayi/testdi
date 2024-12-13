<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IDCard extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'id_number',
        'department',
        'front_image_path',
        'back_image_path'
    ];
}
