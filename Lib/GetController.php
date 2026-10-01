<?php
/*
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Alexey Portnov, 8 2020
 */

namespace Modules\ModuleRHVoice\Lib;
use MikoPBX\PBXCoreREST\Controllers\BaseController;
use Throwable;
use Modules\ModuleRHVoice\bin\AmiConfClient;

class GetController extends BaseController
{
    /**
     * Синтез речи нативным RHVoice и отдача WAV.
     * /pbxcore/api/rhvoice/say
     * По умолчанию отдаётся inline (для прослушивания в браузере);
     * с параметром dl=1 — как вложение (скачивание файла).
     * curl -o test.wav 'http://127.0.0.1/pbxcore/api/rhvoice/say?text=%D0%9F%D1%80%D0%B8%D0%B2%D0%B5%D1%82&voice=vitaliy-ng&dl=1'
     */
    public function recordsAction(): void
    {
        try {
            $text  = (string)$this->request->get('text');
            $voice = (string)$this->request->get('voice');
            if ($text === '') {
                $this->sendError(400);
                return;
            }
            $wav = AmiConfClient::tts($text, $voice) . '.wav';
            if (!file_exists($wav)) {
                $this->sendError(502);
                return;
            }
            $disposition = ((string)$this->request->get('dl') === '1') ? 'attachment' : 'inline';
            header('Content-Type: audio/wav');
            header('Accept-Ranges: bytes');
            header('Content-Length: '.filesize($wav));
            header('Content-Disposition: '.$disposition.'; filename="output.wav"');
            readfile($wav);
        } catch (Throwable $e) {
            $this->sendError(501, $e->getMessage());
        }
    }
}