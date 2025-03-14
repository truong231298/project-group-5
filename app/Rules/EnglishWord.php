<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class EnglishWord implements Rule
{
    public function passes($attribute, $value)
    {
        $url = "https://api.datamuse.com/words?sp=" . urlencode($value);
        $response = json_decode(file_get_contents($url), true);
        return !empty($response); // Return true if word exists
    }

    public function message()
    {
        return 'The :attribute must be a valid English word.';
    }
}
