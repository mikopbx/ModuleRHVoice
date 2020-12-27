<?php
namespace Modules\ModuleRHVoice\bin;
use Modules\ModuleRHVoice\Lib\RHVoiceConf;
require_once 'Globals.php';
$voiceConf      = new RHVoiceConf();
$voiceConf->checkStart();
