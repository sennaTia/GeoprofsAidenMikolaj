<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Werknemer extends Model
{
    /**
     * De attributen die je mag invullen bij het aanmaken.
     *
     * @var list<string>
     */
    protected $fillable = [
        'werkgever_id',
        'naam',
        'email',
        'wachtwoord',
        'functie',
    ];

    protected $hidden = [
        'wachtwoord',
    ];

    /**
     * Een werknemer hoort bij één werkgever.
     */
    public function werkgever(): BelongsTo
    {
        return $this->belongsTo(Werkgever::class);
    }
}
