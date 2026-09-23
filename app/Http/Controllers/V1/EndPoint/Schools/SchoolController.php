<?php

namespace App\Http\Controllers\V1\EndPoint\Schools;

use App\Http\Controllers\Controller;
use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Admin\School\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;


class SchoolController extends Controller
{
    //

      public function index()
    {
        $schools = Zone::all();
          $selectedSchoolId = auth()->user()->zone_id;
        return response()->json([
            'schools'=>$schools,
            'selected_school_id' => $selectedSchoolId,
             'stats'   => [
                'total_schools'      => $schools->count(),
                'total_students'     => 0, // baad mein relationship se aayega
                'total_reservations' => 0,
             ]
             ]);
        //refresh hony py bhi school id selected
        //  $selectedSchoolId = auth()->user()->school_id;
        // return response()->json([
        //     'schools' => $schools,
        //     'selected_school_id' => $selectedSchoolId,
        //     'stats'   => [
        //         'total_schools'      => $schools->count(),
        //         'total_students'     => 0, // baad mein relationship se aayega
        //         'total_reservations' => 0,
        //     ]
        // ]);
    }

       public function store(Request $request)
    {
        $request->validate([
            'name'                       => 'required|string',
            'straight'                   => 'required|string',
            'prefectural_approval_number'=> 'required|string',
            'phone'                      => 'required|string',
            // 'address'                    => 'required|string',
            'postal_code'                => 'required|string',
        ]);

        // Pehle school banao
        $school = Zone::create($request->all());

        // Link banao
        $link = url("/school/" . $school->id);

        // QR code generate karo
        $qrCode = new QrCode($link);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        // Storage mein save karo
        $filename = 'qrcodes/school_' . $school->id . '.png';
        Storage::disk('public')->put($filename, $result->getString());

        // Database update karo
        $school->update([
            'link'   => $link,
            'qrcode' => $filename,
        ]);

        return response()->json([
            'message' => 'School created successfully',
            'school'  => $school,
        ], 201);
    }

 public function show(School $school)
    {
        return response()->json([
            'school'  => $school,
            'stats'   => [
                'total_students'     => 0, // baad mein
                'total_reservations' => 0,
            ]
        ]);
    }


public function selectSchool($id)
{
    // Logged in user ki school_id update karo
    auth()->user()->update([
        'zone_id' => $id
    ]);

    // return auth()->user();

    return response()->json([
        'message'   => 'School selected successfully',
        'zone_id' => $id,
    ]);
}
}
