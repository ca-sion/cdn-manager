<?php

namespace App\Http\Controllers;

class MessageController extends Controller
{
    public function success()
    {
        return 'Youpi !';
    }

    public function error()
    {
        return 'Error !';
    }
}
