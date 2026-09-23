<?php

namespace App\Http\Controllers\Backend\Front;

use App\Repository\Blog\Back\Articles\GetAllArticles;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AgenciesController extends Controller
{
    /**
     * @param GetAllArticles $articlesAction
     * @return Response
     */
    public function index(): Response
    {
        Inertia::setRootView('espace-client');
        return Inertia::render('features/agencies/AgenciesPage');
    }
}
