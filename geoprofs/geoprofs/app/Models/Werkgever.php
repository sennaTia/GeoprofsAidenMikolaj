<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Werkgever extends Model
{
    /**
     * De attributen die je mag invullen bij het aanmaken.
     *
     * @var list<string>
     */
    protected $fillable = [
        'bedrijfsnaam',
        'email',
        'wachtwoord',
    ];

    protected $hidden = [
        'wachtwoord',
    ];

    /**
     * Een werkgever heeft meerdere werknemers.
     */
    public function werknemers(): HasMany
    {
        return $this->hasMany(Werknemer::class);
    }
}
