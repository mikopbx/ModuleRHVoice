<?php
/*
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Alexey Portnov, 8 2020
 */

namespace Modules\ModuleRHVoice\Lib;
use MikoPBX\PBXCoreREST\Controllers\BaseController;
use GuzzleHttp\Client;
use Throwable;
use Modules\ModuleRHVoice\Models\ModuleRHVoice;

class GetController extends BaseController
{
    /**
     * Скачивание записи разговора.
     * /pbxcore/api/cdr/records MIKO AJAM
     * curl -o test.wav 'http://127.0.0.1/pbxcore/api/rhvoice/say?text=%D0%9F%D1%80%D0%B8%D0%B2%D0%B5%D1%82&voice=vitaliy-ng&'
     */
    public function recordsAction(): void
    {
        try {
            $settings = ModuleRHVoice::findFirst();
            if(!$settings || empty($settings->local_port)){
                $this->sendError(503);
                return;
            }
            $client = new Client([
                'base_uri' => 'http://127.0.0.1:'.$settings->local_port,
                'timeout'  => 10.0,
            ]);
            $queryParams = [
                'text'   => $this->request->get('text'),
                'voice'  => $this->request->get('voice'),
                'format' => 'wav',
            ];
            $response = $client->get('/say', [
                'query' => $queryParams,
            ]);
            if ($response->getStatusCode() === 200) {
                $stream = $response->getBody();
                $resource = $stream->detach();
                header('Content-Type: audio/wav');
                header('Content-Disposition: attachment; filename="output.wav"');
                fpassthru($resource);
                $stream->close();
            } else {
                $this->sendError(502);
            }
        } catch (Throwable $e) {
            $this->sendError(501, $e->getMessage());
        }
    }
}