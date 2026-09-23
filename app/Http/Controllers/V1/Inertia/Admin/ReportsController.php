<?php

namespace App\Http\Controllers\V1\Inertia\Admin;

use App\Http\Controllers\Controller;
use App\Models\Roles\Student\User\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ActiveStudentsExport;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportsController extends Controller
{
    public function activeStudents(Request $request)
    {
        // Historically this endpoint returned students having a confirmed
        // reservation (training proposal with status=3).  Requirements have since
        // changed – we now show **only active users who possess at least one
        // training record**.  In addition we support text search on student name
        // or email and the existing date filters.

        $perPage = $request->input('per_page', 25); // default page size can be overridden
        $dateStart = $request->input('date_start');
        $dateEnd = $request->input('date_end');
        $search = $request->input('search');

        $studentsQuery = Student::with([
            'user',
            'trainings.reservation',
            // load all sales and let the transformation decide what counts
            'sales',
        ])
        // we only care about **active** users and those that actually have at
        // least one training record (the moment the reservation is stored).
        ->whereHas('user', function ($q) {
            $q->where('status', 1);
        })
        ->whereHas('trainings')
        ->withCount('trainings');

        // Apply text search if given (name or email, case‑insensitive)
        if ($search) {
            $studentsQuery->whereHas('user', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        // Apply date filtering if provided
        if ($dateStart || $dateEnd) {
            $studentsQuery->whereHas('wallets', function ($q) use ($dateStart, $dateEnd) {
                if ($dateStart) {
                    $q->whereDate('created_at', '>=', $dateStart);
                }
                if ($dateEnd) {
                    $q->whereDate('created_at', '<=', $dateEnd);
                }
            });
        }

            $activeStudents = $studentsQuery->paginate($perPage)->through(function ($student) {
            // sum only completed/paid sales; accept both string codes and a
            // numeric status of 2 (system represents "paid").
            // filter and sum as before
            $paidSales = $student->sales->filter(function ($sale) {
                $raw = $sale->payment_status;
                if (is_numeric($raw) && (int) $raw === 2) {
                    return true;
                }
                $status = strtolower((string) ($raw ?? ''));
                return in_array($status, ['paid', 'success', 'succeeded', 'completed'], true);
            });

            $totalPaid = $paidSales->sum(function ($sale) {
                return (float) ($sale->amount ?? 0);
            });

            // also keep per-sale details so the view can display ids if needed
            $salesDetails = $paidSales->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'amount' => (float) ($sale->amount ?? 0),
                    'status' => $sale->payment_status,
                ];
            })->values();

            // id of the first paid sale, used to build invoice button in view
            $firstSaleId = $paidSales->first()?->id;

            // gather the reservation info from the training entries
            $reservations = $student->trainings
                ->map(function ($training) {
                    return [
                        'id' => $training->reservation->id ?? null,
                        'date' => $training->reservation->date ?? null,
                        'start_at' => $training->reservation->start_at ?? null,
                        'end_at' => $training->reservation->end_at ?? null,
                        'training_id' => $training->id,
                    ];
                })
                ->filter()
                ->values();

            return [
            'id' => $student->id,
'user_id' => $student->user->id,
                'name' => $student->user->name,
                'email' => $student->user->email,
                'phone' => $student->user->phone,
                'status' => $student->user->status,
                'ville' => $student->user->ville,
                'reservations_count' => $student->trainings_count,
                'reservations' => $reservations,
                'total_paid' => $totalPaid,
                'sales' => $salesDetails,
                'invoice_sale_id' => $firstSaleId,
            ];
        });

        // For debugging/labeling, count only the students we are actually
        // displaying: active users that have at least one training record.
        $totalStudentsCount = Student::whereHas('user', function ($q) use ($search) {
            $q->where('status', 1);
            if ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                });
            }
        })->whereHas('trainings');

        // Apply same date filter to count if provided
        if ($dateStart || $dateEnd) {
            $totalStudentsCount->whereHas('wallets', function ($q) use ($dateStart, $dateEnd) {
                if ($dateStart) {
                    $q->whereDate('created_at', '>=', $dateStart);
                }
                if ($dateEnd) {
                    $q->whereDate('created_at', '<=', $dateEnd);
                }
            });
        }

        $totalStudents = $totalStudentsCount->count();

        return Inertia::render('features/reports/ActiveStudents', [
            'activeStudents' => $activeStudents,
            'totalStudents' => $totalStudents,
        ]);

    }

    public function exportActiveStudents(Request $request)
    {
        $dateStart = $request->input('date_start');
        $dateEnd = $request->input('date_end');
        $search = $request->input('search');

        $fileName = 'active_students_report.xlsx';
        return Excel::download(new ActiveStudentsExport($dateStart, $dateEnd, $search), $fileName);
    }

    /**
     * Download a single student's report as PDF using the same design as the
     * normal invoice template but showing reservations, payments and totals.
     */
    public function downloadStudentReport(Student $student)
    {
        // reload relationships required for the PDF
        $student->load([
            'user',
            'trainings.reservation',
            'sales',
            'wallets.offer', // load offers through wallets
        ]);

        // compute the data exactly like the index transform so view can be
        // simple
        $paidSales = $student->sales->filter(function ($sale) {
            $raw = $sale->payment_status;
            if (is_numeric($raw) && (int) $raw === 2) {
                return true;
            }
            $status = strtolower((string) ($raw ?? ''));
            return in_array($status, ['paid', 'success', 'succeeded', 'completed'], true);
        });

        $totalPaid = $paidSales->sum(function ($sale) {
            return (float) ($sale->amount ?? 0);
        });

        $reservations = $student->trainings->map(function ($training) {
            return [
                'date' => $training->reservation->date ?? null,
                'start_at' => $training->reservation->start_at ?? null,
                'end_at' => $training->reservation->end_at ?? null,
                'training_id' => $training->id,
            ];
        })->filter()->values();

        // Get offers with balance information
        $offers = $student->wallets->map(function ($wallet) {
            $offer = $wallet->offer;
            $agencyPricing = [];
            $balance = null;

            // Handle both JSON string and array (Laravel may auto-cast)
            if (is_string($offer->agency_pricing)) {
                $agencyPricing = json_decode($offer->agency_pricing, true) ?? [];
            } elseif (is_array($offer->agency_pricing)) {
                $agencyPricing = $offer->agency_pricing;
            }
            if (is_array($agencyPricing) && count($agencyPricing) > 0) {
                // Get balance from first agency pricing entry
                $balance = $agencyPricing[0]['balance'] ?? null;
            }

            return [
                'id' => $offer->id,
                'name' => $offer->name,
                'price_ht' => $offer->price_ht,
                'total_payment' => $offer->total_payment,
                'balance' => $balance,
                'created_at' => $wallet->created_at,
            ];
        });

        $data = [
            'student' => $student->toArray(),
            'reservations' => $reservations->toArray(),
            'offers' => $offers->toArray(),
            'total_paid' => $totalPaid,
            'sales' => $paidSales->map(fn($s)=>['id'=>$s->id,'amount'=>(float)$s->amount])->values()->toArray(),
            'reference' => 'REPORT-'.$student->id.'-'.now()->format('Ymd'),
            'created_at' => now()->toDateTimeString(),
        ];

        $pdf = Pdf::loadView('pdf.normal.active-student-report', $data);
        return $pdf->stream('student_report_'.$student->id.'.pdf');
    }
}

