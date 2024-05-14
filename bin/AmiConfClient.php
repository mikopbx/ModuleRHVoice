#!/usr/bin/php
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
use MikoPBX\PBXCoreREST\Lib\System\ConvertAudioFileAction;
use MikoPBX\PBXCoreREST\Lib\SystemManagementProcessor;
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
            if ($result == false) {
                // Нужен реконнект.
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
        $this->am->Originate(
            // "Local/{$parameters['Conference']}@rh-Voice-conf-alert/n",
            "Local/**{$parameters['Conference']}@applications/n",
            '',
            '',
            '',
            'AGI',
            "{$argv[0]},alert,{$parameters['CallerIDName']}",
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
if($action === 'start'){
    // Start worker process
    AmiConfClient::startWorker($argv??null);
}elseif($action === 'test'){
    AmiConfClient::tts('Это тестовый файл');
}elseif($action === 'alert'){
    $agi = new AGI();
    $agi->answer();
    $agi->verbose("--- {$argv[1]} ---- {$argv[2]}");
    $extData = Extensions::findFirst("number='{$argv[2]}'");
    if($extData){
        $agi->exec('Playback', AmiConfClient::tts('К конференции присоединился '. $extData->callerid));
    }
}elseif ($action === 'new_pin'){
    $agi = new AGI();
    $agi->exec('Playback', AmiConfClient::tts('Вы первый участник конференции. Придумайте ПИН код из трех цифр.'));
}elseif($action === 'enter_pin'){
    $agi = new AGI();
    $agi->exec('Playback', AmiConfClient::tts('Введите пин код'));
}elseif($action === 'is_const_conf'){
    $agi = new AGI();
    $agi->exec('Playback', AmiConfClient::tts('Введите один, если хотите сделать конференцию постоянной'));
}elseif($action === 'menu'){
    $agi = new AGI();
    $num = str_replace(['#',"*"], ['',''], $agi->request['agi_extension']);
    $agi->exec('Playback', AmiConfClient::tts('Редактирование конференции с номером '. $num));
    $action = '';
    while ($action === ''){
        $agi->exec('Playback', AmiConfClient::tts('Введите 1 для редактирования ПИН кода. Введите 2 для удаления конференции.'));
        $result = $agi->getData('beep', 6000, 1);
        $action = $result['result']??'';
    }
    if($action === '1'){
        $selectedNum = '';
        while ($selectedNum === ''){
            $agi->exec('Playback', AmiConfClient::tts('Введите новый пин код конференции'));
            $result = $agi->getData('beep', 6000, 3);
            $selectedNum = $result['result']??'';
        }
        $agi->exec('Playback', AmiConfClient::tts('Новый пин код конференции '. $selectedNum));
        $agi->set_variable("CB_PINS/{$num}", $selectedNum);
        $agi->databasePut('CB_PINS', $num, $selectedNum);
    }elseif ($action === '2'){
        $agi->database_del('CB_PINS', $num);
    }
}
