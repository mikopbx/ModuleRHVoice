<?php
/*
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 */

namespace Modules\ModuleRHVoice\Lib;

use MikoPBX\Core\System\Processes;
use MikoPBX\Core\System\Util;
use Modules\ModuleRHVoice\Models\ModuleRHVoice;

/**
 * Управление голосами RHVoice: установка по запросу с GitHub и отслеживание прогресса.
 *
 * Прогресс пишется в файл .status_<voice>.json в каталоге голосов и читается
 * web-интерфейсом через module API (VOICE-PROGRESS).
 */
class VoiceManager
{
    public const STATE_IDLE        = 'idle';
    public const STATE_QUEUED      = 'queued';
    public const STATE_DOWNLOADING = 'downloading';
    public const STATE_EXTRACTING  = 'extracting';
    public const STATE_INSTALLING  = 'installing';
    public const STATE_DONE        = 'done';
    public const STATE_ERROR       = 'error';

    /** Предохранитель: максимум времени на скачивание одного голоса, мкс. */
    private const DOWNLOAD_TIMEOUT_US = 180000000;

    private string $moduleDir;

    public function __construct(string $moduleDir)
    {
        $this->moduleDir = $moduleDir;
    }

    /**
     * Каталог с голосами.
     */
    private function voicesDir(): string
    {
        return $this->moduleDir.'/rhvoice/data/voices';
    }

    /**
     * Путь к файлу статуса конкретного голоса.
     */
    private function statusFile(string $voice): string
    {
        return $this->voicesDir().'/.status_'.$voice.'.json';
    }

    /**
     * Голос установлен, если присутствует voice.info.
     */
    public function isInstalled(string $voice): bool
    {
        return is_file($this->voicesDir().'/'.$voice.'/voice.info');
    }

    /**
     * Список установленных голосов из числа поддерживаемых.
     *
     * @return array
     */
    public function getInstalledVoices(): array
    {
        $installed = [];
        foreach (array_keys(ModuleRHVoice::VOICE_DATA) as $key) {
            if ($this->isInstalled($key)) {
                $installed[] = $key;
            }
        }
        return $installed;
    }

    /**
     * Текущий статус докачки голоса.
     *
     * @return array{voice:string,state:string,percent:int,message:string}
     */
    public function readStatus(string $voice): array
    {
        if ($this->isInstalled($voice)) {
            return ['voice' => $voice, 'state' => self::STATE_DONE, 'percent' => 100, 'message' => ''];
        }
        $file = $this->statusFile($voice);
        if (is_file($file)) {
            $data = json_decode((string)@file_get_contents($file), true);
            if (is_array($data) && isset($data['state'])) {
                return $data;
            }
        }
        return ['voice' => $voice, 'state' => self::STATE_IDLE, 'percent' => 0, 'message' => ''];
    }

    /**
     * Записать статус докачки.
     */
    public function writeStatus(string $voice, string $state, int $percent, string $message = ''): void
    {
        @file_put_contents(
            $this->statusFile($voice),
            json_encode([
                'voice'   => $voice,
                'state'   => $state,
                'percent' => $percent,
                'message' => $message,
            ])
        );
    }

    /**
     * Полная установка голоса: скачивание tarball с GitHub, распаковка, установка.
     * Вызывается из фонового скрипта bin/voiceDownloader.php (под root).
     */
    public function install(string $voice): void
    {
        $repo = ModuleRHVoice::getVoiceRepo($voice);
        if ($voice === '' || $repo === '') {
            $this->writeStatus($voice, self::STATE_ERROR, 0, "Unknown voice: $voice");
            return;
        }
        if ($this->isInstalled($voice)) {
            $this->writeStatus($voice, self::STATE_DONE, 100, '');
            return;
        }

        $voicesDir = $this->voicesDir();
        $target    = "$voicesDir/$voice";
        Util::mwMkdir($voicesDir);

        $tmp = "$voicesDir/.tmp_$voice";
        Processes::mwExec('rm -rf '.escapeshellarg($tmp));
        Util::mwMkdir($tmp);

        $tar  = "$tmp/voice.tar.gz";
        $url  = "https://api.github.com/repos/$repo/tarball";
        $curl = Util::which('curl');

        // Запускаем curl в фоне, чтобы отслеживать прогресс по размеру файла
        // (codeload GitHub отдаёт архив без Content-Length).
        $this->writeStatus($voice, self::STATE_DOWNLOADING, 5, '');
        $pid = (int)shell_exec(
            $curl." -fsSL --connect-timeout 15 -A 'MikoPBX-ModuleRHVoice' -o "
            .escapeshellarg($tar).' '.escapeshellarg($url)." >/dev/null 2>&1 & echo \$!"
        );
        if ($pid <= 0) {
            $this->fail($voice, $tmp, 'Download launch failed');
            return;
        }

        $waited = 0;
        while (file_exists("/proc/$pid")) {
            $bytes   = is_file($tar) ? (int)@filesize($tar) : 0;
            // Без общего размера: асимптотически приближаемся к 70%.
            $percent = 5 + (int)round(65 * (1 - 8388608 / (8388608 + $bytes)));
            $this->writeStatus($voice, self::STATE_DOWNLOADING, $percent, sprintf('%.1f MB', $bytes / 1048576));
            usleep(400000);
            $waited += 400000;
            if ($waited > self::DOWNLOAD_TIMEOUT_US) {
                @exec("kill $pid 2>/dev/null");
                $this->fail($voice, $tmp, 'Download timeout');
                return;
            }
        }

        if (!is_file($tar) || (int)@filesize($tar) < 1024) {
            $this->fail($voice, $tmp, 'Download failed');
            return;
        }

        $this->writeStatus($voice, self::STATE_EXTRACTING, 85, '');
        Processes::mwExec(Util::which('tar').' -xzf '.escapeshellarg($tar).' -C '.escapeshellarg($tmp));

        $extracted = '';
        foreach (glob("$tmp/*", GLOB_ONLYDIR) as $d) {
            if (is_file("$d/voice.info")) {
                $extracted = $d;
                break;
            }
        }
        if ($extracted === '') {
            $this->fail($voice, $tmp, 'Extraction failed');
            return;
        }

        $this->writeStatus($voice, self::STATE_INSTALLING, 95, '');
        Processes::mwExec('rm -rf '.escapeshellarg($target));
        $mv = Processes::mwExec('mv '.escapeshellarg($extracted).' '.escapeshellarg($target));
        Processes::mwExec('rm -rf '.escapeshellarg($tmp));

        if ($mv !== 0 || !is_file("$target/voice.info")) {
            $this->fail($voice, null, 'Install failed');
            return;
        }

        $this->writeStatus($voice, self::STATE_DONE, 100, '');
    }

    /**
     * Пометить ошибку и убрать временные файлы.
     */
    private function fail(string $voice, ?string $tmp, string $message): void
    {
        if ($tmp !== null) {
            Processes::mwExec('rm -rf '.escapeshellarg($tmp));
        }
        $this->writeStatus($voice, self::STATE_ERROR, 0, $message);
    }
}
