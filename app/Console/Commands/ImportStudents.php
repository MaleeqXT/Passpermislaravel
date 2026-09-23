<?php

namespace App\Console\Commands;

use App\Enums\V2\Admin\User\UserRolesEnum;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportStudents extends Command
{
    protected $signature = 'import:students';
    protected $description = 'Import students from a CSV file';

    public function handle()
    {
        DB::beginTransaction();
        try {

            $path = storage_path('app/ElevePassPermisFacileCreil.csv');
            if (!file_exists($path)) {
                $this->error("CSV file not found at: $path");
                return;
            }

            $file = fopen($path, 'r');
            $header = fgetcsv($file); // assuming the first row is headers

            while (($row = fgetcsv($file)) !== false) {
                $data = array_combine($header, $row);

                // You can adjust these fields based on your CSV headers
                $firstName = $data['nom'] ?? '';
                $lastName = $data['prenom'] ?? '';
                $email = $data['email'];
                $phone = $data['phone'];
                $date_naissance = Carbon::parse($data['dateNaissance']);
                $neph = $data['neph'];

                $user = User::query()->updateOrCreate(
                    [  'email' => $email,],
                    [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'name' => $firstName . ' ' . $lastName,
                    'phone' => $phone,
                    'date_naissance' => $date_naissance,
                    'password' => Hash::make($firstName . '-' . $lastName),
                    'media' => 'https://robohash.org/' . Str::uuid() . '?size=200x200',
                ]);

                $user->student()->create([
                    'neph' => (int)$neph,
                ]);
                $user->assignRole(UserRolesEnum::STUDENT->value);

                $this->info("Created user: $email");
            }

            fclose($file);
            $this->info('Student import finished!');

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', flashMessage('error'));
            dd($e);
        }
    }
}
