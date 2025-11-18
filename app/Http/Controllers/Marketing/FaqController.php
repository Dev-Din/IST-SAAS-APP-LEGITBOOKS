<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;

class FaqController extends Controller
{
    public function index()
    {
        return view('marketing.faq');
    }
}

