<?php

namespace App\Controllers;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Extensions\Database\FManager as DB;

class AwardUploadController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $c = $app['controllers_factory'];

        $c->match('/award/upload/', [$this,'uploadAwardCsv'])
            ->method('GET|POST')
            ->before(function () use ($app) {
                if (!p('awards.upload')) {
                    $app->abort(403);
                }
            })
            ->bind('award.upload');

        return $c;
    }

    public function uploadAwardCsv(Application $app, Request $request)
    {
        if (!$request->isMethod('POST')) {
            return $this->renderForm($app);
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('award_csv');

        if (!$this->validateUploadedFile($file, $app)) {
            return $this->redirectBack($app);
        }

        $rows = $this->parseCsvFile($file, $app);
        if ($rows === null) {
            return $this->redirectBack($app);
        }

        $this->insertRows($rows, $app);

        return $this->redirectBack($app);
    }

    private function renderForm(Application $app)
    {
        return $app['blade']->view()
            ->make('admin.gamification.award.upload', [
                'uploadUrl' => $app['url_generator']->generate('award.upload'),
                'app'       => $app,
            ])
            ->render();
    }

    private function redirectBack(Application $app)
    {
        return $app->redirect($app['url_generator']->generate('award.upload'));
    }

    /** Check that file exists, valid and is CSV */
    private function validateUploadedFile(?UploadedFile $file, Application $app): bool
    {
        if (!$file || !$file->isValid()) {
            $app['session']->getFlashBag()->add('error', 'File is not selected or is corrupted.');
            return false;
        }

        $allowedExt  = ['csv'];
        $allowedMime = ['text/csv', 'text/plain', 'application/vnd.ms-excel'];

        $ext  = strtolower($file->getClientOriginalExtension() ?: '');
        $mime = $file->getMimeType() ?: '';

        if (!in_array($ext, $allowedExt, true) || !in_array($mime, $allowedMime, true)) {
            $app['session']->getFlashBag()->add('error', 'Invalid file format. Please upload a CSV file.');
            return false;
        }

        return true;
    }

    /**
     * Read CSV and return array of rows ready for insert.
     * On error — flashes message and returns null.
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function parseCsvFile(UploadedFile $file, Application $app): ?array
    {
        $handle = @fopen($file->getRealPath(), 'r');
        if (!$handle) {
            $app['session']->getFlashBag()->add('error', 'Unable to open the CSV file.');
            return null;
        }

        $header = fgetcsv($handle, 0, ',');
        if (!$header) {
            fclose($handle);
            $app['session']->getFlashBag()->add('error', 'The file is empty or corrupted.');
            return null;
        }

        $idxUser   = array_search('user_id',  $header, true);
        $idxAward  = array_search('award_id', $header, true);
        $idxAmount = array_search('amount',   $header, true);

        if ($idxUser === false || $idxAward === false || $idxAmount === false) {
            fclose($handle);
            $app['session']->getFlashBag()->add(
                'error',
                'CSV header must contain user_id, award_id and amount columns.'
            );
            return null;
        }

        $rows = [];
        $now  = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $userId  = isset($row[$idxUser])   ? (int)$row[$idxUser]   : 0;
            $awardId = isset($row[$idxAward])  ? (int)$row[$idxAward]  : 0;
            $amount  = max(1, (int)($row[$idxAmount] ?? 1));

            if (!$userId || !$awardId) {
                continue;
            }

            for ($i = 0; $i < $amount; $i++) {
                $rows[] = [
                    'award_id'   => $awardId,
                    'user_id'    => $userId,
                    'status'     => 0,
                    'created_at' => $now,
                ];
            }
        }
        fclose($handle);

        if (!$rows) {
            $app['session']->getFlashBag()->add('error', 'No valid rows found in the file.');
            return null;
        }

        return $rows;
    }

    /** Insert rows under transaction */
    private function insertRows(array $rows, Application $app): void
    {
        DB::beginTransaction();
        try {
            DB::table('trophy_award_ownership')->insert($rows);
            DB::commit();

            $app['session']->getFlashBag()->add(
                'success',
                'CSV imported successfully. Records added: ' . count($rows)
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            $app['session']->getFlashBag()->add(
                'error',
                'Import error: ' . $e->getMessage()
            );
        }
    }
}
