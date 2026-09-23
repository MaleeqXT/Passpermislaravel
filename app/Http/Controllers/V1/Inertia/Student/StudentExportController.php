<?php

namespace App\Http\Controllers\V1\Inertia\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentSalesExport;
use App\Exports\StudentsSalesExport;
use App\Models\Roles\Student\User\Student;

class StudentExportController extends Controller
{
    public function exportAll()
    {
        $fileName = 'students_sales_all.xlsx';
        return Excel::download(new StudentsSalesExport(), $fileName);
    }

    public function exportStudent(Student $student)
    {
        $fileName = 'student_' . $student->id . '_sales.xlsx';
        return Excel::download(new StudentSalesExport($student), $fileName);
    }
}
