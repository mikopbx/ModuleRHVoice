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

use MikoPBX\Core\Asterisk\AGI;
use MikoPBX\Common\Models\Extensions;

$action = $argv[1]??'';
if($action === 'test'){
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