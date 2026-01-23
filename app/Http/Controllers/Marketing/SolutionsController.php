<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;

class SolutionsController extends Controller
{
    public function index()
    {
        return view('marketing.solutions');
    }
}
