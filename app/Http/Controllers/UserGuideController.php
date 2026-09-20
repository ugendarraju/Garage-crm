<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class UserGuideController extends Controller
{
    /**
     * Display the interactive User Guide & Knowledge Base.
     */
    public function index()
    {
        return Inertia::render('UserGuide/Index');
    }
}
