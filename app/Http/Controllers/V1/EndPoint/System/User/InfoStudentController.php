<?php

namespace App\Http\Controllers\V1\EndPoint\System\User;

use App\Http\Controllers\Controller;

class InfoStudentController extends Controller
{


    public function getInfo()
    {
        return response()->json([
            'user' => auth()->user()->load('student'),
        ]);
    }
}
