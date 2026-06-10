<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'radius',
        'allowed_ips',
    ];

    /**
     * Get the users assigned to this office.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
