<?php

namespace Alighorbani\PassportForMongo;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Imanghafoori\TokenAnalyzer\Refactor;

class LaravelPassportNormalizerForMongodb extends Command
{
    const TARGET_PASSPORT_FILES = ['AuthCode', 'PersonalAccessClient', 'Client', 'Token', 'RefreshToken'];

    protected $signature = 'passport:normalize';

    protected $description = 'Used to normalize laravel passport work with mongodb without any Eloquent Model Exception';

    public $passportPath;

    private $passportComposerJsonFile;

    public function __construct()
    {
        parent::__construct();

        $this->setPassportPaths();
    }

    public function handle()
    {
        $this->throwExceptionIfPassportDosenotExists();

        $this->handlePassportNormalization();

        $this->successfulMessage();
    }

    private function successfulMessage()
    {
        $this->line('fixed all the references on laravel passport source code!');
    }

    private function setPassportPaths()
    {
        $this->passportPath = base_path() . "/vendor/laravel/passport";

        $this->passportComposerJsonFile = $this->passportPath . '/' . 'composer.json';
    }

    private function throwExceptionIfPassportDosenotExists()
    {
        if (!file_exists($this->passportComposerJsonFile)) {
            $this->line("Passport Doesn't install in your machine in this path " . $this->passportPath);
            die;
        }
    }

    private function getTargetFileWithFullPath()
    {
        $fullPath = collect(self::TARGET_PASSPORT_FILES);

        return $fullPath->map(function ($item) {
            return $this->passportPath . '/src/' . $item . '.php';
        });
    }

    public function handlePassportNormalization()
    {
        $filesPath = $this->getTargetFileWithFullPath();

        foreach ($filesPath as $filePath) {

            $fileTokens = $this->getTokenByFilePath($filePath);

            $this->convertTokenToSupportMongoModel($filePath, $fileTokens);
        }
    }

    private function convertTokenToSupportMongoModel($filePath, $fileTokens)
    {
        $refactorTokens = false;

        foreach ($fileTokens as $index => $token) {

            if (!is_array($token)) {
                continue;
            }

            $tokenType = $token[0];

            $tokenContent = $token[1];

            if ($tokenType != T_NAME_QUALIFIED) {
                continue;
            }

            if (!Str::contains($tokenContent, 'Illuminate\Database\Eloquent\Model')) {
                continue;
            }

            if (trim($tokenContent) == 'Illuminate\Database\Eloquent\Model') {
                $fileTokens[$index][1] = 'Jenssegers\Mongodb\Eloquent\Model';
                $refactorTokens = true;
            }
        }

        if ($refactorTokens) {
            Refactor::saveTokens($filePath, $fileTokens);
        }
    }

    private function getTokenByFilePath($filePath)
    {
        return token_get_all(file_get_contents($filePath));
    }
}
