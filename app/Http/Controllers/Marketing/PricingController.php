<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;

class PricingController extends Controller
{
    public function index()
    {
        return view('marketing.pricing');
    }
}
