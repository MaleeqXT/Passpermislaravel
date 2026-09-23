<?php

namespace App\Http\Middleware;

use App\Repository\V2\Monitor\Evaluation\FetchDocumentEvaluationRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Competency\CountCompetencyRepo;
use App\Repository\V2\Shared\Schedule\GetFirstReservation;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'espace-client';

    protected $externalRootViewList = [
        'espace-client',
        'espace-admin',
        'espace-student',
        'espace-monitor',
        'espace-secretary',
    ];

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        // React chat returns JSON only. Do not run training/progression queries
        // and prepare Inertia page props on every message, poll or socket auth.
        if ($request->is('api/chat/*', 'api/conversations', 'api/conversations/*', 'api/broadcasting/auth')) {
            return [];
        }
        
        $auth = auth()->user();
        $docs = null;

        if ($auth) {
            // 🔹 Secretary (priority check before student)
            if ($auth->hasRole('secretary')) {
                $this->rootView = 'espace-secretary';

                $docs = [
                    'message' => 'Welcome Secretary!',
                    'sec_id'  => $auth->secretary?->id, // safe access
                ];
            }
            // 🔹 Admin
            elseif ($auth->hasRole('admin')) {
                $this->rootView = 'espace-admin';
            }
            // 🔹 Monitor
            elseif ($auth->hasRole('monitor')) {
                $this->rootView = 'espace-monitor';
            }
            // 🔹 Student
            elseif ($auth->hasRole('student')) {
                $this->rootView = 'espace-student';

                if ($auth->student) {   //  only if relation exists
                    $fr  = GetFirstReservation::run(['student_id' => $auth->student->id]);
                    $eva = FetchDocumentEvaluationRepo::run(['student_id' => $auth->student->id]);

                    if ($fr || $eva) {
                        $docs = [
                            'fr'  => $fr,
                            'eva' => $eva,
                        ];
                    }
                }
            }
            // 🔹 Default (client)
            else {
                $this->rootView = 'espace-client';
            }
        }

        return array_merge(parent::share($request), [
            'flash' => [
                'error'   => fn () => $request->session()->get('error'),
                'success' => fn () => $request->session()->get('success'),
            ],
            'adminUser'     => fn () => $request->session()->get('adminUser'),
            'progressTotal' => CountCompetencyRepo::run(),
            'docs'          => fn () => $docs,
        ]);
    }
}
