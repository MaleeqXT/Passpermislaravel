<?php

namespace App\Http\Controllers\Backend\Front;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class TermsAndConditionController extends Controller
{


    /**
     * Mentions légales page
     * 
     * @return Response
     */
    public function legalMentions(): Response
    {
        Inertia::setRootView('espace-client');
        return Inertia::render('features/privacy/LegalMentionsPage');
    }

    /**
     * Politique de confidentialité & cookies page
     * 
     * @return Response
     */
    public function privacyPolicy(): Response
    {
        Inertia::setRootView('espace-client');
        return Inertia::render('features/privacy/PrivacyPolicyPage');
    }

    /**
     * Conditions d’utilisation page
     * 
     * @return Response
     */
    public function termsOfUse(): Response
    {
        Inertia::setRootView('espace-client');
        return Inertia::render('features/privacy/TermsOfUsePage');
    }

    /**
     * Conditions de vente page
     * 
     * @return Response
     */
    public function termsOfSale(): Response
    {
        Inertia::setRootView('espace-client');
        return Inertia::render('features/privacy/TermsOfSalePage');
    }
}
