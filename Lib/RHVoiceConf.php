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
use MikoPBX\Modules\Config\ConfigClass;
use MikoPBX\Modules\PbxExtensionUtils;
use MikoPBX\PBXCoreREST\Lib\PBXApiResult;
use Modules\ModuleRHVoice\Models\ModuleRHVoice;

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
     *  Process CoreAPI requests under root rights
     *
     * @param array $request
     *
     * @return PBXApiResult An object containing the result of the API call.
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
        Processes::mwExecBg($binDir . DIRECTORY_SEPARATOR.'docker run -d -p '.$localPort.':8080 aculeasis/rhvoice-rest:amd64');
    }

    /**
     * Добавление задач в crond.
     *
     * @param array $tasks
     */
    public function createCronTasks(array &$tasks): void
    {
        if ( ! is_array($tasks)) {
            return;
        }
        $workerPath = $this->moduleDir.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'safeScript.php';
        $phpPath = Util::which('php');
        $tasks[]      = "*/1 * * * * {$phpPath} -f {$workerPath} > /dev/null 2> /dev/null\n";
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
        Processes::mwExec($binDir . DIRECTORY_SEPARATOR. "docker ps | {$grep} aculeasis/rhvoice-rest |{$busybox} awk  '{ print $1}'", $out);
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
        if($moduleEnabled === true){
            $this->onAfterModuleEnable();
        }else{
            $this->onAfterModuleDisable();
        }
    }
}
