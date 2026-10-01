<?php
/*
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Alexey Portnov, 10 2020
 */
namespace Modules\ModuleRHVoice\bin;
require_once 'Globals.php';

use MikoPBX\Core\Asterisk\AsteriskManager;
use MikoPBX\Core\System\Processes;
use MikoPBX\Core\Workers\WorkerBase;
use MikoPBX\Core\System\Util;
use Modules\ModuleRHVoice\Models\ModuleRHVoice;

class AmiConfClient extends WorkerBase
{
    /** Встроенный голос, поставляется в комплекте модуля; используется как дефолт и фолбэк. */
    public const DEFAULT_VOICE = 'tatiana';

    /** @var AsteriskManager $am */
    protected AsteriskManager $am;

    /**
     * Старт работы листнера.
     *
     * @param $argv
     */
    public function start($argv):void
    {
        $this->am     = Util::getAstManager();
        $this->setFilter();
        $this->am->addEventHandler("ConfbridgeJoin", [$this, "callback"]);
        $this->am->addEventHandler("userevent",      [$this, "userEvent"]);
        while (true) {
            $result = $this->am->waitUserEvent(true);
            if (!$result) {
                usleep(100000);
                $this->am = Util::getAstManager();
                $this->setFilter();
            }
        }
    }

    /**
     * Установка фильтра
     *
     */
    private function setFilter():void
    {
        $pingTube = $this->makePingTubeName(self::class);
        $this->am->sendRequestTimeout('Filter', ['Operation' => 'Add', 'Filter' => 'UserEvent: '.$pingTube]);
        $this->am->sendRequestTimeout('Filter', ['Operation' => 'Add', 'Filter' => 'Event: ConfbridgeJoin']);
    }

    public function userEvent($parameters):void
    {
        $this->replyOnPingRequest($parameters);
    }
    /**
     * Функция обработки оповещений.
     *
     * @param $parameters
     */
    public function callback($parameters):void{
        global $argv;

        if( stripos($parameters['Channel'], 'PJSIP') === false ||
            $parameters['Context'] === 'selector-meeting'){
            return;
        }
        if($parameters['BridgeNumChannels'] === '3'){
            return;
        }
        $script = dirname($argv[0], 2) ."/agi-bin/alertScript.php";
        // Номер вошедшего (alertScript ищет экстеншн по номеру и берёт его callerid).
        $callerNum = $parameters['CallerIDNum'] ?? '';
        try {
            // Типы важны: в ядре Originate() — ?int $priority/$timeout и bool $async.
            $this->am->Originate(
                "Local/**{$parameters['Conference']}@internal/n",
                null,                               // exten
                null,                               // context
                null,                               // priority (?int)
                'AGI',                              // application
                "$script,alert,{$callerNum}",       // data
                30,                                 // timeout (int)
                'ALERT',                            // callerid
                'alert=1',                          // variable
                null,                               // account
                true                                // async (bool)
            );
        } catch (\Throwable $e) {
            // Не роняем воркер из-за сбоя одного оповещения.
            Util::sysLogMsg(self::class, 'Originate alert failed: '.$e->getMessage());
        }
    }

    /**
     * Синтез речи нативным движком RHVoice (без Docker/REST).
     * Возвращает путь к WAV-файлу без расширения (для Asterisk Playback).
     *
     * @param string $text  Текст для озвучивания.
     * @param string $voice Голос (пусто — берётся из настроек модуля).
     *
     * @return string
     */
    public static function tts(string $text, string $voice = ''):string
    {
        $moduleDir = dirname(__DIR__);
        $dir       = $moduleDir.'/db/media';
        Util::mwMkdir($dir);

        /** @var ModuleRHVoice $settings */
        $settings = ModuleRHVoice::findFirst();
        if(empty($voice)){
            $voice = ($settings && !empty($settings->voice)) ? $settings->voice : self::DEFAULT_VOICE;
        }
        // Если выбранный голос ещё не скачан — откатываемся на встроенный, чтобы синтез не падал.
        if(!is_file($moduleDir.'/rhvoice/data/voices/'.$voice.'/voice.info')){
            $voice = self::DEFAULT_VOICE;
        }
        $rate = ($settings && $settings->rate !== null && $settings->rate !== '') ? (int)$settings->rate : 40;

        // Ключ кэша учитывает голос и темп, чтобы файлы не смешивались.
        $filename   = md5($voice.'_'.$rate.'_'.$text);
        $fullName   = "$dir/{$filename}_src.wav";
        $n_filename = "$dir/{$filename}.wav";

        if(!file_exists($n_filename)){
            $arch   = php_uname('m'); // x86_64 | aarch64 — выбираем бинарник под архитектуру
            $rhvDir = $moduleDir.'/rhvoice';
            $bin    = "$rhvDir/bin/$arch/RHVoice-test";
            // На случай, если установщик не сохранил бит исполнения.
            if(!is_executable($bin)){
                @chmod($bin, 0755);
            }

            // Настройка темпа модуля 0..100 (50 — норма) повторяет логику rhvoice-rest:
            // absolute_rate = rate/50 - 1, далее переводим в относительный множитель для CLI.
            $absolute = max(0, min(100, $rate)) / 50.0 - 1.0;
            $percent  = (int)round(pow(3.0, $absolute) * 100);

            $txtFile = "$dir/{$filename}.txt";
            file_put_contents($txtFile, $text);

            $env = 'LD_LIBRARY_PATH='.escapeshellarg("$rhvDir/lib/$arch")
                .' RHVOICE_DATA_PATH='.escapeshellarg("$rhvDir/data")
                .' RHVOICE_CONFIG_PATH='.escapeshellarg("$rhvDir/etc");

            // RHVoice выдаёт 24 кГц mono 16-bit PCM.
            Processes::mwExec(
                "$env ".escapeshellarg($bin)
                ." -p ".escapeshellarg($voice)
                ." -r ".escapeshellarg((string)$percent)
                ." -i ".escapeshellarg($txtFile)
                ." -o ".escapeshellarg($fullName)
            );
            @unlink($txtFile);

            // Приводим к формату Asterisk: 8 кГц mono 16-bit.
            $soxPath = Util::which('sox');
            Processes::mwExec("{$soxPath} -v 0.99 -G ".escapeshellarg($fullName)." -c 1 -r 8000 -b 16 ".escapeshellarg($n_filename));
            @unlink($fullName);
        }

        return Util::trimExtensionForFile($n_filename);
    }

}

$action = $argv[1]??'';
if($action === 'start' && Util::getFilePathByClassName(AmiConfClient::class) === $argv[0]){
    // Start worker process
    AmiConfClient::startWorker($argv??null);
}