<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller as AppController;

class InfoController extends AppController
{
    public function about()
    {
        return view('info.about');
    }

    public function personal()
    {
        return view('info.personal');
    }

    public function terms()
    {
        return view('info.terms');
    }

    public function privacy()
    {
        return view('info.privacy');
    }
}
