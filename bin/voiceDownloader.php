#!/usr/bin/php
<?php
/*
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 */
namespace Modules\ModuleRHVoice\bin;
require_once 'Globals.php';

use Modules\ModuleRHVoice\Lib\VoiceManager;

$voice = $argv[1] ?? '';
if ($voice === '') {
    exit(1);
}

// Каталог модуля: этот скрипт лежит в <moduleDir>/bin.
$moduleDir = dirname(__DIR__);
$manager   = new VoiceManager($moduleDir);
$manager->install($voice);
