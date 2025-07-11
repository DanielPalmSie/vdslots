<?php

namespace App\Controllers;

use Exception;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use SplFileObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Valitron\Validator;

class TranslateController implements ControllerProviderInterface
{
    private $deepL;

    public function __construct()
    {
        $this->deepL = phive('Localizer/DeepL/DeepL');
    }

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/', 'App\Controllers\TranslateController::index')
            ->bind('translate.index')
            ->before(function () use ($app) {
                if (!p('admin.translate.deepl')) {
                    $app->abort(403);
                }
            });

        $factory->post('/text/', 'App\Controllers\TranslateController::translate')
            ->bind('translate.text')
            ->before(function () use ($app) {
                if (!p('admin.translate.deepl')) {
                    $app->abort(403);
                }
            });

        $factory->post('/file/', 'App\Controllers\TranslateController::sendFileForTranslation')
            ->bind('translate.file')
            ->before(function () use ($app) {
                if (!p('admin.translate.deepl')) {
                    $app->abort(403);
                }
            });

        $factory->post('/file-status/', 'App\Controllers\TranslateController::checkDocumentTranslationStatus')
            ->bind('translate.file.status')
            ->before(function () use ($app) {
                if (!p('admin.translate.deepl')) {
                    $app->abort(403);
                }
            });

        $factory->get('/file-download/', 'App\Controllers\TranslateController::downloadTranslatedDocument')
            ->bind('translate.file.download')
            ->before(function () use ($app) {
                if (!p('admin.translate.deepl')) {
                    $app->abort(403);
                }
            });

        $factory->get('/characters-usage/', 'App\Controllers\TranslateController::getCharactersUsage')
            ->bind('translate.characters-usage')
            ->before(function () use ($app) {
                if (!p('admin.translate.deepl')) {
                    $app->abort(403);
                }
            });

        return $factory;
    }

    public function index(Application $app, Request $request): string
    {
        $sourceLangs = $this->deepL->getSourceLanguages();
        $sourceLangCodes = collect($sourceLangs)->map(fn($lang) => $lang->code)->toArray();

        $targetLangs = $this->deepL->getTargetLanguages();
        $targetLangCodes = collect($targetLangs)->map(fn($lang) => $lang->code)->toArray();

        $defaultSourceLangCode = 'EN';
        $defaultTargetLangCode = 'ES';

        $usedCharactersCount = $this->deepL->getUsedCharactersCount();
        $charactersLimit = $this->deepL->getCharactersLimit();

        return $app['blade']->view()->make(
            'admin.translate.index',
            compact(
                'app',
                'sourceLangs',
                'sourceLangCodes',
                'targetLangs',
                'targetLangCodes',
                'defaultSourceLangCode',
                'defaultTargetLangCode',
                'usedCharactersCount',
                'charactersLimit',
            )
        )->render();
    }

    public function translate(Application $app, Request $request): JsonResponse
    {
        $text = $request->get('text');
        $sourceLang = $request->get('sourceLang');
        $targetLang = $request->get('targetLang');
        $tagHandling = $request->get('tagHandling');

        $validator = new Validator($request->request->all());
        $validator
            ->rule('required', ['text', 'targetLang'])
            ->rule('in', [
                ['tagHandling', ['html', 'xml', '']],
            ]);

        if (!$validator->validate()) {
            $errors = $validator->errors();
            return $app->json([
                'error' => array_pop($errors)[0],
            ]);
        }

        $options = $tagHandling ? ['tag_handling' => $tagHandling] : [];
        $translation = $this->deepL->translate($text, $sourceLang, $targetLang, $options);

        if ($translation->error) {
            return $app->json([
                'error' => $translation->error,
            ]);
        }

        return $app->json([
            'text' => $translation->text,
        ]);
    }

    public function sendFileForTranslation(Application $app, Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        $sourceLang = $request->get('sourceLang');
        $targetLang = $request->get('targetLang');

        $validator = new Validator(array_merge($request->request->all(), $request->files->all()));
        $validator->rule('required', ['file', 'targetLang']);

        if (!$validator->validate()) {
            $errors = $validator->errors();
            return $app->json([
                'error' => array_pop($errors)[0],
            ]);
        }

        $filePath = $this->generateTempFilePath($file);
        move_uploaded_file($file, $filePath);

        try {
            $documentHandle = $this->deepL->uploadDocument($filePath, $sourceLang, $targetLang);
        } catch (Exception $exception) {
            return $app->json([
                'error' => $exception->getMessage(),
            ]);
        } finally {
            $this->deleteTempFile($filePath);
        }

        return $app->json([
            'documentId' => $documentHandle->documentId,
            'documentKey' => $documentHandle->documentKey,
        ]);
    }

    public function checkDocumentTranslationStatus(Application $app, Request $request): JsonResponse
    {
        $documentId = $request->get('documentId');
        $documentKey = $request->get('documentKey');

        $validator = new Validator($request->request->all());
        $validator->rule('required', ['documentId', 'documentKey']);

        if (!$validator->validate()) {
            $errors = $validator->errors();
            return $app->json([
                'error' => array_pop($errors)[0],
            ]);
        }

        $documentHandle = $this->deepL->createDocumentHandle($documentId, $documentKey);
        $documentStatus = $this->deepL->getDocumentStatus($documentHandle);

        return $app->json([
            'status' => $documentStatus->status,
            'secondsRemaining' => $documentStatus->secondsRemaining,
            'billedCharacters' => $documentStatus->billedCharacters,
            'errorMessage' => $documentStatus->errorMessage,
        ]);
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return StreamedResponse|JsonResponse
     */
    public function downloadTranslatedDocument(Application $app, Request $request)
    {
        $documentId = $request->get('documentId');
        $documentKey = $request->get('documentKey');
        $fileName = $request->get('fileName');

        $validator = new Validator($request->query->all());
        $validator->rule('required', ['documentId', 'documentKey', 'fileName']);

        if (!$validator->validate()) {
            $errors = $validator->errors();
            return $app->json([
                'error' => array_pop($errors)[0],
            ]);
        }

        $documentHandle = $this->deepL->createDocumentHandle($documentId, $documentKey);
        $outputFilePath = $this->generateTempFilePath($fileName);
        $this->deepL->downloadDocument($documentHandle, $outputFilePath);

        $response = $app->stream(function () use ($outputFilePath) {
            $file = new SplFileObject($outputFilePath);

            while (!$file->eof()) {
                echo $file->fgets();
            }

            $file = null;

            $this->deleteTempFile($outputFilePath);
        });

        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($outputFilePath)
        );
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    public function getCharactersUsage(Application $app): JsonResponse
    {
        $usedCharactersCount = $this->deepL->getUsedCharactersCount();
        $charactersLimit = $this->deepL->getCharactersLimit();

        return $app->json([
            'usedCharactersCount' => $usedCharactersCount,
            'charactersLimit' => $charactersLimit,
        ]);
    }

    /**
     * @param UploadedFile|string $file
     * @return string
     */
    private function generateTempFilePath($file): string
    {
        $fileName = is_string($file) ? $file : $file->getClientOriginalName();

        $fileDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('', true) . DIRECTORY_SEPARATOR;
        mkdir($fileDir);
        $filePath = $fileDir . basename($fileName);

        return $filePath;
    }

    private function deleteTempFile(string $path): void
    {
        unlink($path);
        rmdir(dirname($path));
    }
}
