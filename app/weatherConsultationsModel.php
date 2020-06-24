<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class weatherConsultations extends Model
{
    protected $table = "weather_consultations";
    protected $fillable = ["id","city","key","created_at","updated_at"];
}

