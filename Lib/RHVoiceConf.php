<?php
/**
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Alexey Portnov, 12 2019
 */


namespace Modules\ModuleRHVoice\Lib;

use MikoPBX\Core\System\Configs\CronConf;
use MikoPBX\Core\System\Processes;
use MikoPBX\Core\System\Util;
use MikoPBX\Core\Workers\Cron\WorkerSafeScriptsCore;
use MikoPBX\Core\Workers\WorkerModelsEvents;
use MikoPBX\Core\Workers\Libs\WorkerModelsEvents\Actions\ReloadDialplanAction;
use MikoPBX\Modules\Config\ConfigClass;
use MikoPBX\Modules\PbxExtensionUtils;
use MikoPBX\PBXCoreREST\Lib\PBXApiResult;
use Modules\ModuleRHVoice\bin\AmiConfClient;
use Modules\ModuleRHVoice\Lib\VoiceManager;
use Modules\ModuleRHVoice\Models\ModuleRHVoice;

class RHVoiceConf extends ConfigClass
{

    /**
     * Receive information about mikopbx main database changes
     *
     * @param mixed $data
     */
    public function modelsEventChangeData($data): void
    {
        if ($data['model'] === ModuleRHVoice::class)
        {
            $this->onAfterModuleDisable();
            $this->onAfterModuleEnable();
            // Префиксы входа/администрирования и длина номера влияют на диалплан —
            // перегенерируем extensions.conf и перезагружаем диалплан.
            WorkerModelsEvents::invokeAction(ReloadDialplanAction::class);
        }
    }

    /**
     * Returns module workers to start it at WorkerSafeScriptCore
     *
     * @return array
     */
    public function getModuleWorkers(): array
    {
        return [
            [
                'type'   => WorkerSafeScriptsCore::CHECK_BY_AMI,
                'worker' => AmiConfClient::class,
            ],
        ];
    }

    /**
     * Generates the internal dialplan for IVR.
     *
     * @return string The generated internal dialplan.
     */
    public function extensionGenInternal(): string
    {
        // Настройки комбинаций и длины номера конференции.
        $settings    = ModuleRHVoice::findFirst();
        $enterPrefix = ($settings && $settings->enter_prefix !== null && $settings->enter_prefix !== '') ? $settings->enter_prefix : '**';
        $adminPrefix = ($settings && $settings->admin_prefix !== null && $settings->admin_prefix !== '') ? $settings->admin_prefix : '***';
        $len         = ($settings && (int)$settings->conf_number_length > 0) ? (int)$settings->conf_number_length : 4;

        $digits     = str_repeat('X', $len);
        $enterExten = '_'.$enterPrefix.$digits;              // шаблон входа, напр. _**XXXX
        $adminExten = '_'.$adminPrefix.$digits;              // шаблон администрирования, напр. _***XXXX
        $num        = '${EXTEN:'.strlen($enterPrefix).'}';   // номер конференции = часть после префикса

        $conf = "exten => $enterExten,1,NoOp(---)" . PHP_EOL .
            "    same => n,ExecIf(\$[ \"\${alert}\" == \"1\" ]?Goto(start_conf))" . PHP_EOL .
            "    same => n,Set(dbPin=\${DB(CB_PINS/$num)})" . PHP_EOL .
            "    same => n,GotoIf(\$[ \"\${dbPin}\" == \"\" ]?skip_pin)" . PHP_EOL .
            "    same => n,AGI($this->moduleDir/agi-bin/alertScript.php,enter_pin)" . PHP_EOL .
            "    same => n,Read(pin,beep,3)" . PHP_EOL .
            "    same => n,ExecIf(\$[ \"\${pin}\" != \"\${dbPin}\" ]?Playback(beep))" . PHP_EOL .
            "    same => n,ExecIf(\$[ \"\${pin}\" != \"\${dbPin}\" ]?Hangup)" . PHP_EOL .
            "    same => n(skip_pin),Set(bridgePeer=\${CHANNEL})" . PHP_EOL .
            "    same => n,Set(i=1)" . PHP_EOL .
            "    same => n,While(\$[\${i} < 10])" . PHP_EOL .
            "    same => n,ExecIf(\$[ \"\${bridgePeer:0:5}\" != \"Local\" ]?ExitWhile())" . PHP_EOL .
            "    same => n,ExecIf(\$[ \"\${bridgePeer:0:5}\" == \"Local\" ]?Set(pl=\${IF(\$[\"\${CHANNEL:-1}\" == \"1\"]?2:1)}))" . PHP_EOL .
            "    same => n,ExecIf(\$[ \"\${bridgePeer:0:5}\" == \"Local\" ]?Set(bridgePeer=\${IMPORT(\${CUT(bridgePeer,\\\;,1)}\\\;\${pl},BRIDGEPEER)}))" . PHP_EOL .
            "    same => n,ExecIf(\$[ \"\${bridgePeer}x\" == \"x\" ]?ExitWhile())" . PHP_EOL .
            "    same => n,Set(i=\$[\${i} + 1])" . PHP_EOL .
            "    same => n,EndWhile" . PHP_EOL .
            "    same => n,ExecIf(\$[ \"\${bridgePeer}\" != \"\${CHANNEL}\" && \"\${bridgePeer:0:5}\" != \"Local\" && \"\${bridgePeer}x\" != \"x\" ]?ChannelRedirect(\${bridgePeer},\${CONTEXT},\${EXTEN},\${PRIORITY}))" . PHP_EOL .
            "    same => n,ExecIf(\$[\"\${CHANNEL(channeltype)}\" == \"Local\"]?Hangup())" . PHP_EOL .
            "    same => n,AGI(meetme_dial.php)" . PHP_EOL .
            "    same => n,Answer()" . PHP_EOL .
            "    same => n,Gosub(set-answer-state,\${EXTEN},1)" . PHP_EOL .
            "    same => n,Set(CHANNEL(hangup_handler_wipe)=hangup_handler_meetme,s,1)" . PHP_EOL .
            "    same => n,Set(CONFBRIDGE(bridge,record_file)=\${MEETME_RECORDINGFILE}.wav)" . PHP_EOL .
            "    same => n,Set(CONFBRIDGE(bridge,record_file_timestamp)=false)" . PHP_EOL .
            "    same => n,Set(CONFBRIDGE(bridge,record_conference)=yes)" . PHP_EOL .
            "    same => n,Set(CONFBRIDGE(bridge,video_mode)=follow_talker)" . PHP_EOL .
            "    same => n,Set(CONFBRIDGE(user,talk_detection_events)=yes)" . PHP_EOL .
            "    same => n(start_conf),Set(CONFBRIDGE(user,quiet)=yes)" . PHP_EOL .
            "    same => n,Set(CONFBRIDGE(user,music_on_hold_when_empty)=yes)" . PHP_EOL .
            "    same => n,ConfBridge($num)" . PHP_EOL .
            "    same => n,Hangup()" . PHP_EOL .
            PHP_EOL .
            "exten => $adminExten,1,NoOp()" . PHP_EOL .
            "    same => n,AGI($this->moduleDir/agi-bin/alertScript.php,menu)";
        return $conf;
    }

    /**
     * Returns array of additional routes for PBXCoreREST interface from module
     *
     * [ControllerClass, ActionMethod, RequestTemplate, HttpMethod, RootUrl, NoAuth ]
     *
     * @RoutePrefix("/pbxcore/api")
     * @Get("/cdr/get_data")
     * @Get("/cdr/records")
     * @Post("/fax/upload")
     *
     * @return array
     */
    public function getPBXCoreRESTAdditionalRoutes(): array
    {
        return [
            [GetController::class, 'recordsAction', '/pbxcore/api/rhvoice/say', 'get', '/', true],
        ];
    }

    /**
     *  Process CoreAPI requests under root rights
     *
     * @param array $request
     *
     * @return PBXApiResult
     */
    public function moduleRestAPICallback(array $request): PBXApiResult
    {
        $res    = new PBXApiResult();
        $res->processor = __METHOD__;
        $action  = strtoupper($request['action']);
        // Параметры запроса приходят во вложенном ключе 'data' (payload getData()),
        // а сам action — на верхнем уровне сообщения воркера.
        $data    = (isset($request['data']) && is_array($request['data'])) ? $request['data'] : $request;
        $voice   = (string)($data['voice'] ?? '');
        $manager = new VoiceManager($this->moduleDir);
        switch ($action) {
            case 'CHECK':
                $res->success = is_executable($this->moduleDir.'/rhvoice/bin/'.php_uname('m').'/RHVoice-test');
                break;
            case 'VOICES-STATUS':
                $res->success = true;
                $res->data    = ['installed' => $manager->getInstalledVoices()];
                break;
            case 'DOWNLOAD-VOICE':
                $this->startVoiceDownload($manager, $voice, $res);
                break;
            case 'VOICE-PROGRESS':
                $res->success = true;
                $res->data    = $manager->readStatus($voice);
                break;
            default:
                $res->success    = false;
                $res->messages[] = 'API action not found in moduleRestAPICallback ModuleRHVoice';
        }
        return $res;
    }

    /**
     * Запуск асинхронной докачки голоса: помечаем статус и стартуем фоновый скрипт.
     * Сам процесс качает/распаковывает голос и пишет прогресс в статус-файл.
     *
     * @param VoiceManager $manager
     * @param string       $voice Ключ голоса из ModuleRHVoice::VOICE_REPO.
     * @param PBXApiResult $res   Результат для заполнения.
     */
    private function startVoiceDownload(VoiceManager $manager, string $voice, PBXApiResult $res): void
    {
        if ($voice === '' || ModuleRHVoice::getVoiceRepo($voice) === '') {
            $res->success    = false;
            $res->messages[] = "Unknown voice: $voice";
            return;
        }
        if ($manager->isInstalled($voice)) {
            $res->success = true;
            $res->data    = ['voice' => $voice, 'state' => VoiceManager::STATE_DONE, 'percent' => 100];
            return;
        }

        // Начальный статус до старта фонового процесса, чтобы поллинг сразу видел прогресс.
        $manager->writeStatus($voice, VoiceManager::STATE_QUEUED, 1, '');

        $phpPath = Util::which('php');
        Processes::mwExecBg(
            "$phpPath -f ".escapeshellarg($this->moduleDir.'/bin/voiceDownloader.php').' '.escapeshellarg($voice)
        );

        $res->success = true;
        $res->data    = ['voice' => $voice, 'state' => VoiceManager::STATE_QUEUED, 'percent' => 1];
    }


    /**
     * Process after enable action in web interface
     *
     * @return void
     */
    public function onAfterModuleEnable(): void
    {
        // Нативный RHVoice не требует фонового сервиса — синтез выполняется по требованию.
        // Перезапускаем cron, чтобы поднялись задачи модуля и AMI-воркер.
        $cron = new CronConf();
        $cron->reStart();
    }

    /**
     * Добавление задач в crond.
     *
     * @param $tasks
     */
    public function createCronTasks(&$tasks): void
    {
        $phpPath = Util::which('php');
        $tasks[]      = "*/1 * * * * $phpPath -f $this->moduleDir/bin/safeScript.php > /dev/null 2> /dev/null\n";
    }

    /**
     * Process after disable action in web interface
     *
     * @return void
     */
    public function onAfterModuleDisable(): void
    {
        // Фоновых сервисов нет — останавливать нечего.
    }

    /**
     * Периодическая проверка (safeScript): при выключенном модуле гасим AMI-воркер.
     */
    public function checkStart():void{
        $moduleEnabled = PbxExtensionUtils::isEnabled($this->moduleUniqueId);
        if($moduleEnabled === true){
            return;
        }
        $workerPid = Processes::getPidOfProcess(AmiConfClient::class);
        if(!empty($workerPid)){
            shell_exec("kill $workerPid");
        }
    }
}
