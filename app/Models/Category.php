<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';

    public $timestamps = false;

    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(Item::class, 'category_id');
    }
}
