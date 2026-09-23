<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Factories\Roles\Student\Schedule\TrainingProposalFactory;
use Database\Seeders\Media\StorageMediaSeeder;
use Database\Seeders\Roles\Admin\Area\LieuSeeder;
use Database\Seeders\Roles\Admin\Area\ZipSeeder;
use Database\Seeders\Roles\Admin\Area\ZoneSeeder;
use Database\Seeders\Roles\Admin\Contact\ContactUsSeeder;
use Database\Seeders\Roles\Admin\Offer\OfferSeeder;
use Database\Seeders\Roles\Admin\Promo\PromoSeeder;
use Database\Seeders\Roles\Monitor\Schedule\ReservationSeeder;
use Database\Seeders\Roles\Monitor\Schedule\ReviewMonitorSeeder;
use Database\Seeders\Roles\Monitor\User\Informations\BillingSeeder;
use Database\Seeders\Roles\Monitor\User\Informations\Instructor\Car\CarAssuranceSeeder;
use Database\Seeders\Roles\Monitor\User\Informations\Instructor\Car\CarSeeder;
use Database\Seeders\Roles\Monitor\User\Informations\Instructor\Car\GrayCarCartSeeder;
use Database\Seeders\Roles\Monitor\User\Informations\Instructor\DriverLicenseSeeder;
use Database\Seeders\Roles\Monitor\User\Informations\Instructor\IdentityRecordSeeder;
use Database\Seeders\Roles\Monitor\User\Informations\Instructor\InstructorAccountSeeder;
use Database\Seeders\Roles\Monitor\User\Informations\Instructor\InstructorCertificationsSeeder;
use Database\Seeders\Roles\Monitor\User\Informations\Instructor\InstructorDocumentSeeder;
use Database\Seeders\Roles\Monitor\User\Informations\Instructor\InstructorPermissionSeeder;
use Database\Seeders\Roles\Student\Cpf\CpfSeeder;
use Database\Seeders\Roles\Student\Exam\StudentExamSeeder;
use Database\Seeders\Roles\Student\Schedule\CancellationSeeder;
use Database\Seeders\Roles\Student\Schedule\RatingSeeder;
use Database\Seeders\Roles\Student\Schedule\StudentAvailabilitySeeder;
use Database\Seeders\Roles\Student\Schedule\TrainingProposalSeeder;
use Database\Seeders\Roles\Student\Schedule\TrainingSeeder;
use Database\Seeders\Roles\Student\User\Competency\CompetencySeeder;
use Database\Seeders\Roles\Student\User\Competency\MainCompetencySeeder;
use Database\Seeders\Roles\Student\User\StudentSeeder;
use Database\Seeders\Roles\Student\User\WalletSeeder;
use Database\Seeders\User\UserSeeder;
use Database\Seeders\User\UsersRolesTableSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UsersRolesTableSeeder::class,
            UserSeeder::class,
          //  StudentSeeder::class,
           // OfferSeeder::class,
       //     StorageMediaSeeder::class,
          //  ZoneSeeder::class,
        //    LieuSeeder::class,
         //   ZipSeeder::class,

            // EleveCommentSeeder::class,
            MainCompetencySeeder::class,
            CompetencySeeder::class,
            SatisfactionSeeder::class,

        ]);

        // check if the app is in local environment
        if (app()->environment('local')) {
            // call the seeder class
            $this->call([
                // WalletSeeder::class,

                // ReservationSeeder::class,
                // TrainingSeeder::class,
                // RatingSeeder::class,
                // StudentAvailabilitySeeder::class,
                // TrainingProposalSeeder::class,
                // CancellationSeeder::class,
                // CpfSeeder::class,
                // StudentExamSeeder::class,
                // ContactUsSeeder::class,
                // BillingSeeder::class,


                CarSeeder::class,
                GrayCarCartSeeder::class,
                CarAssuranceSeeder::class,
                IdentityRecordSeeder::class,
                DriverLicenseSeeder::class,
                InstructorCertificationsSeeder::class,
                InstructorDocumentSeeder::class,
                InstructorPermissionSeeder::class,
                InstructorAccountSeeder::class,
                //  SettingPlanningSeeder::class,
                PromoSeeder::class,
            ]);
        }
    }
}
