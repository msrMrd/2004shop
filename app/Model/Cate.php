<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Cate extends Model
{
    protected $table = 'shop_cate';
    protected $primaryKey = 'cate_id';
    public $timestamps = false;
}
