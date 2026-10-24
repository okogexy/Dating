<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['birthday' => 'date', 'interests' => 'array', 'photos' => 'array', 'hidden' => 'boolean', 'demo' => 'boolean'];
    }

    public function user() { return $this->belongsTo(User::class); }
}
