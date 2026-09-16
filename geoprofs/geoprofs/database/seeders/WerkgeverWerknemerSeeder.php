<?php

namespace Database\Seeders;

use App\Models\Werkgever;
use App\Models\Werknemer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WerkgeverWerknemerSeeder extends Seeder
{
    /**
     * Maak 1 werkgever en 1 werknemer aan om mee te testen.
     */
    public function run(): void
    {
        $werkgever = Werkgever::create([
            'bedrijfsnaam' => 'Geoprofs B.V.',
            'email' => 'werkgever@geoprofs.nl',
            'wachtwoord' => Hash::make('wachtwoord123'),
        ]);

        Werknemer::create([
            'werkgever_id' => $werkgever->id,
            'naam' => 'Jan Jansen',
            'email' => 'werknemer@geoprofs.nl',
            'wachtwoord' => Hash::make('wachtwoord123'),
            'functie' => 'Software Developer',
        ]);
    }
}
