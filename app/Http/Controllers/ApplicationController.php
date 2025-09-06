<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    /**
     * Show the application form.
     */
    public function index()
    {
        return view('application.index');
    }
    
    /**
     * Show the application success page.
     */
    public function success()
    {
        return view('application.success');
    }
}
