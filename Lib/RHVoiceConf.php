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
use MikoPBX\Modules\Config\ConfigClass;
use MikoPBX\Modules\PbxExtensionUtils;
use MikoPBX\PBXCoreREST\Lib\PBXApiResult;
use Modules\ModuleRHVoice\bin\AmiConfClient;
use Modules\ModuleRHVoice\Models\ModuleRHVoice;
use Phalcon\Text;

class RHVoiceConf extends ConfigClass
{

    /**
     * Receive information about mikopbx main database changes
     *
     * @param $data
     */
    public function modelsEventChangeData($data): void
    {
        if ($data['model'] === ModuleRHVoice::class)
        {
            $this->onAfterModuleDisable();
            $this->onAfterModuleEnable();
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
        $conf = "exten => _**XXXX,1,NoOp(---)" . PHP_EOL .
            "    same => n,ExecIf(\$[ \"\${alert}\" == \"1\" ]?Goto(start_conf))" . PHP_EOL .
            "    same => n,AGI($this->moduleDir/agi-bin/alertScript.php,enter_pin)" . PHP_EOL .
            "    same => n,Read(pin,beep,3)" . PHP_EOL .
            "    same => n,Set(dbPin=\${DB(CB_PINS/\${EXTEN:2})})" . PHP_EOL .
            "    same => n,ExecIf(\$[ \"\${pin}\" != \"\${dbPin}\" ]?Playback(beep))" . PHP_EOL .
            "    same => n,ExecIf(\$[ \"\${pin}\" != \"\${dbPin}\" ]?Hangup)" . PHP_EOL .
            "    same => n,Set(bridgePeer=\${CHANNEL})" . PHP_EOL .
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
            "    same => n,AGI(cdr_connector.php,meetme_dial)" . PHP_EOL .
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
            "    same => n,ConfBridge(\${EXTEN:2})" . PHP_EOL .
            "    same => n,Hangup()" . PHP_EOL .
            PHP_EOL .
            "exten => _***XXXX,1,NoOp()" . PHP_EOL .
            "    same => n,AGI($this->moduleDir/agi-bin/alertScript.php,menu)";

        return $conf;
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
        $action = strtoupper($request['action']);
        switch ($action) {
            case 'CHECK':
                $res->success = !empty($this->getPidContainer());
                break;
            default:
                $res->success    = false;
                $res->messages[] = 'API action not found in moduleRestAPICallback ModuleRHVoice';
        }
        return $res;
    }


    /**
     * Process after enable action in web interface
     *
     * @return void
     */
    public function onAfterModuleEnable(): void
    {
        if(!empty($this->getPidContainer())){
            return;
        }

        $cron = new CronConf();
        $cron->reStart();

        /**
         * @var ModuleRHVoice $settings
         */
        $settings = ModuleRHVoice::findFirst();

        $binDir     = $this->getBinDir();
        $localPort  = $settings->local_port;
        // Запускаем docker.
        Processes::mwExecBg($binDir . DIRECTORY_SEPARATOR.'docker run -d --rm -p '.$localPort.':8080 aculeasis/rhvoice-rest:amd64');
    }

    /**
     * Добавление задач в crond.
     *
     * @param $tasks
     */
    public function createCronTasks(&$tasks): void
    {
//        if ( ! is_array($tasks)) {
//            return;
//        }
//        $workerPath = $this->moduleDir.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'safeScript.php';
//        $phpPath = Util::which('php');
//        $tasks[]      = "*/1 * * * * $phpPath -f $workerPath > /dev/null 2> /dev/null\n";
    }

    /**
     * Process after disable action in web interface
     *
     * @return void
     */
    public function onAfterModuleDisable(): void
    {
        $binDir = $this->getBinDir();
        $pid = $this->getPidContainer();
        if(!empty($pid)){
            Processes::mwExec($binDir.DIRECTORY_SEPARATOR.'docker stop '.$pid);
        }
    }

    /**
     * Возвращает идентификатор контейнера Docker.
     * @return string
     */
    private function getPidContainer():string{
        $binDir = $this->getBinDir();
        $grep   = Util::which('grep');
        $busybox   = Util::which('busybox');
        Processes::mwExec($binDir . DIRECTORY_SEPARATOR. "docker ps | $grep aculeasis/rhvoice-rest | $busybox awk  '{ print $1}'", $out);
        return implode('', $out);
    }

    /**
     * @return string
     */
    private function getBinDir():string{
        return dirname($this->moduleDir) .DIRECTORY_SEPARATOR.'ModuleDocker'. DIRECTORY_SEPARATOR . 'bin';
    }

    /**
     *
     */
    public function checkStart():void{

        $moduleEnabled  = PbxExtensionUtils::isEnabled($this->moduleUniqueId);
        $workerPid = Processes::getPidOfProcess(AmiConfClient::class);
        if($moduleEnabled === true){
            $this->onAfterModuleEnable();
//            $am       = Util::getAstManager();
//            $res_ping = $am->pingAMIListener(Text::camelize("ping_".AmiConfClient::class, '\\'));
//            if (false === $res_ping) {
//                if(!empty($workerPid)){
//                    shell_exec("kill -9 $workerPid");
//                }
//                Processes::mwExecBg("$this->moduleDir/bin/AmiConfClient.php start");
//            }
        }else{
            if(!empty($workerPid)){
                shell_exec("kill $workerPid");
            }
            $this->onAfterModuleDisable();
        }
    }
}
