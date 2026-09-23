<?php

namespace App\Http\Controllers\Backend\Eleve;

use App\Http\Controllers\Controller;
use App\Models\Roles\Student\User\Student;

class ContractFormationController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function index()
    {
        $student = auth()->user()?->student;

        if (! $student) {
            abort(403, 'Contrat non trouve.');
        }

        $student->load('user');

        if (! $student->canViewContract()) {
            abort(403, 'Le contrat sera disponible apres votre premiere heure de formation.');
        }

        return view('pdf.normal.contract-formation-html', [
            'student' => $student,
            'isAdmin' => false,
        ]);
    }

    /** Show a selected student's contract for an authorized staff dashboard. */
    public function staff(Student $student)
    {
        $user = auth()->user();
        abort_unless($user?->hasAnyRole(['admin', 'super-admin', 'secretary']), 403, 'User does not have the right roles.');

        $student->load('user');
        abort_unless($student->canViewContract(), 403, 'Le contrat sera disponible apres votre premiere heure de formation.');

        return view('pdf.normal.contract-formation-html', [
            'student' => $student,
            'isAdmin' => true,
        ]);
    }
}
