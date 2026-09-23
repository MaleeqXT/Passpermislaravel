<?php

namespace App\Http\Controllers\Backend\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Contact\StoreOrUpdateContactRequest;
use App\Notifications\V1\System\AboutUs\SendAboutUsNotification;
use App\Notifications\V1\System\AboutUs\GetAboutUsNotification;
use App\Repository\V2\Admin\Contact\StoreContactRepo;
use App\Models\Roles\Admin\Contact\ContactUs;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    /** Store a message sent by the React public website. */
    public function apiStore(StoreOrUpdateContactRequest $request): JsonResponse
    {
        $contact = StoreContactRepo::run($request->validated());

        if (!$contact) {
            return response()->json(['message' => "Impossible d'envoyer le message."], 500);
        }

        return response()->json([
            'message' => 'Votre message a été envoyé avec succès.',
            'data' => $contact,
        ], 201);
    }

    /** Messages displayed in the React admin "Messages clients" screen. */
    public function apiIndex(): JsonResponse
    {
        return response()->json([
            'data' => ContactUs::query()->latest('created_at')->get(),
        ]);
    }

    /**
     * @return Response
     */
    public function index(): Response
    {
        Inertia::setRootView('espace-client');
        return Inertia::render('features/contact/ContactPage');
    }

    /**
     * @throws Exception
     */
    public function send(StoreOrUpdateContactRequest $request)
    {
        DB::beginTransaction();
        try {
            // store contact
            StoreContactRepo::run($request->validated());

            Notification::route('mail', $request->get('email'))
                ->notify(new SendAboutUsNotification());

            Notification::route('mail', env('MAIL_PRIMARY', 'autopasspermisfacile@gmail.com'))
                ->notify(new GetAboutUsNotification($request->validated()));
            session()->flash('success', 'Votre message a été envoyé avec succès');
            DB::commit();
            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', "Une erreur est survenue lors de l'envoi du message");
            throw $e;
        }
    }
}
