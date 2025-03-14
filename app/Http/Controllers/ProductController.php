<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    function isEnglishWord($word)
    {
        $word = explode(" ", $word)[0]; // Only check the first word to avoid issues with multi-word phrases

        $url = "https://api.datamuse.com/words?sp=" . urlencode($word);
        $response = json_decode(file_get_contents($url), true);

        return !empty($response);
    }

}
