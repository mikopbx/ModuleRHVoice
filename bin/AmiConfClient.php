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
use MikoPBX\Core\Asterisk\AGI;
use MikoPBX\Common\Models\Extensions;
use Modules\ModuleRHVoice\Models\ModuleRHVoice;

class AmiConfClient extends WorkerBase
{
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

        if(stripos($parameters['Channel'], 'PJSIP') === false){
            return;
        }
        if($parameters['BridgeNumChannels'] === '3'){
            return;
        }
        $script = dirname($argv[0], 2) ."/agi-bin/alertScript.php";
        $this->am->Originate(
            "Local/**{$parameters['Conference']}@internal/n",
            '',
            '',
            '',
            'AGI',
            "$script,alert,{$parameters['CallerIDName']}",
            '30',
            'ALERT',
            'alert=1',
            '',
            '1');
    }

    public static function tts($text):string
    {
        $dir = dirname(__DIR__).'/db/media';
        Util::mwMkdir($dir);
        $filename = md5($text);
        $fullName   = "$dir/{$filename}_src.wav";
        $n_filename = "$dir/{$filename}.wav";
        $port = '';
        /** @var ModuleRHVoice $settings */
        $settings = ModuleRHVoice::findFirst();
        if($settings){
            $port = $settings->local_port;
        }
        if(empty($port)){
            $port='7788';
        }
        if(!file_exists($n_filename)){
            $url = "http://127.0.0.1:$port/say?format=wav&text=".rawurlencode($text);
            shell_exec("/usr/bin/curl -o $fullName '$url'");
            $soxPath      = Util::which('sox');
            Processes::mwExec("{$soxPath} -v 0.99 -G '{$fullName}' -c 1 -r 8000 -b 16 '{$n_filename}'");
        }

        return Util::trimExtensionForFile($n_filename);
    }

}

$action = $argv[1]??'';
if($action === 'start' && Util::getFilePathByClassName(AmiConfClient::class) === $argv[0]){
    // Start worker process
    AmiConfClient::startWorker($argv??null);
}