<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Roles\Student\Schedule\TrainingProposal;

class TrainingProposalController extends Controller
{
    //

    // app/Http/Controllers/TrainingProposalController.php
public function index(Request $request)
{
    $query = TrainingProposal::query();

    if ($request->has('reservation_id')) {
        $query->where('reservation_id', $request->reservation_id);
    }

    $proposals = $query->orderBy('created_at', 'desc')->get();

    return response()->json([
        'data' => $proposals
    ]);
}

}
