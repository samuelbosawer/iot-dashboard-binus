<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Display the dashboard placeholder for the authenticated user.
     */
    public function index(): View
    {
        return view('dashboard');
    }
}
