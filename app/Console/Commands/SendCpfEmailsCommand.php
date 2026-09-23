<?php

namespace App\Console\Commands;

use App\Enums\V2\Student\Cpf\DocumentCpfEtatEnum;
use App\Models\Roles\Student\Cpf\Cpf;
use App\Notifications\V1\Student\Cpf\AttestationQuestionnaireSatisfactionCpfNotification;
use App\Notifications\V1\Student\Cpf\SuiviProNotification;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SendCpfEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cpf-email-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch CPF Emails';

    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle()
    {
        DB::beginTransaction();

        try {

//            $this->processCpfDocumentsForOneMonth();
//            $this->processCpfDocumentsForThreeMonths();

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $this->handleError($e);
        }
    }

    /**
     * Process CPF documents for those that ended more than 1 month ago.
     *
     * @return void
     */
    protected function processCpfDocumentsForOneMonth()
    {
        Cpf::query()
            ->where('end_at', '<=', now()->subMonth()->format('Y-m-d'))
            ->whereDoesntHave('documents', function ($query) {
                $query->where('document', DocumentCpfEtatEnum::suivi_pro->value);
            })
            ->each(function ($cpf) {
                $this->sendNotificationForCpf($cpf, DocumentCpfEtatEnum::suivi_pro->value);
            });
    }

    /**
     * Process CPF documents for those that ended more than 3 months ago.
     *
     * @return void
     */
    protected function processCpfDocumentsForThreeMonths()
    {
        Cpf::query()
            ->where('end_at', '<=', now()->subMonths(3)->format('Y-m-d'))
            ->whereDoesntHave('documents', function ($query) {
                $query->where('document', DocumentCpfEtatEnum::suivi_pro2->value);
            })
            ->each(function ($cpf) {
                $this->sendNotificationForCpf($cpf, DocumentCpfEtatEnum::suivi_pro2->value);
            });
    }

    /**
     * Send the appropriate notification and create the document.
     *
     * @param Cpf $cpf
     * @param string $document
     * @return void
     */
    protected function sendNotificationForCpf(Cpf $cpf, string $document)
    {
        $user = $cpf->student->user;

        // Send the correct notification
        $notification = $this->getNotificationForDocument($document, $user);
        Notification::route('mail', $user->email)->notify($notification);

        // Create the document entry
        $cpf->documents()->create([
            'document' => $document,
            'data' => ['user' => $cpf->user],
        ]);
    }

    /**
     * Get the correct notification instance based on the document type.
     *
     * @param string $document
     * @param User $user
     * @return \Illuminate\Notifications\Notification
     * @throws Exception
     */
    protected function getNotificationForDocument(string $document, $user)
    {
        if ($document === DocumentCpfEtatEnum::suivi_pro->value) {
            return new AttestationQuestionnaireSatisfactionCpfNotification($user);
        } elseif ($document === DocumentCpfEtatEnum::suivi_pro2->value) {
            return new SuiviProNotification($user);
        }

        throw new Exception("Notification type for document {$document} not found.");
    }

    /**
     * Handle error during the process.
     *
     * @param Exception $e
     * @return void
     * @throws Exception
     */
    protected function handleError(Exception $e)
    {
        $this->error('Error Saving CPF Documents: ' . $e->getMessage());
        throw $e;
    }
}
