<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $table = 'students';
    const UPDATED_AT = null;

    protected $fillable = ['npm', 'name', 'photo'];

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }
}
