<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Imga extends Model
{
    protected $table="imga";
    protected $primaryKey="img_id";
    public $timestamps=false;
    protected $guarded=[];
}
