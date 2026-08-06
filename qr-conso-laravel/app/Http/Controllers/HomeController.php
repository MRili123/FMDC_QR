<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    public function index(string $locale)
    {
        return view('public.home', ['locale' => $locale]);
    }
}
