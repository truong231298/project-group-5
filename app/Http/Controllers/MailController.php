<?php

namespace App\Http\Controllers;

use App\Mail\SampleMail;
use Illuminate\Http\Request;
use Mail;

class MailController extends Controller
{
    public function sendMail(){
        $details = [
            'title' => "day la title",
            'body' => "day la body"
        ];

        Mail::to('banhnhatquang@gmail.com')->send(new SampleMail($details));
        return "Email da dc gui";
    }
}
