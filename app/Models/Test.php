<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    protected $table="text";
    protected $primaryKey="id";
    public $timestamps=false;
    protected $guarded=[];
}
