<?php

namespace App\Http\Controllers\Backend\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    /**
     * @return Response
     */
    public function index()
    {
        if ((auth()->user()) == null) {
            return redirect()->route('login', ['redirect_to' => '/checkout']);
        }
        Inertia::setRootView('espace-client');
        return Inertia::render('features/checkout/ChekoutPage');
    }
}
