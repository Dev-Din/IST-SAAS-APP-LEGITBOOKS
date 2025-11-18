<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;

class FeaturesController extends Controller
{
    public function index()
    {
        return view('marketing.features');
    }
}

