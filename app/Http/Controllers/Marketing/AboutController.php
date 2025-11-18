<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;

class AboutController extends Controller
{
    public function index()
    {
        return view('marketing.about');
    }
}

