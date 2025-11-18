<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index()
    {
        return view('marketing.home');
    }
}

