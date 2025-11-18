<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;

class LegalController extends Controller
{
    public function terms()
    {
        return view('marketing.legal.terms');
    }

    public function privacy()
    {
        return view('marketing.legal.privacy');
    }
}

