<?php
/**
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Alexey Portnov, 2 2019
 */

/*
 * https://docs.phalconphp.com/3.4/ru-ru/db-models-metadata
 *
 */


namespace Modules\ModuleRHVoice\Models;

use MikoPBX\Modules\Models\ModulesModelsBase;

class ModuleRHVoice extends ModulesModelsBase
{
    /**
     * Поддерживаемые голоса (только русский и узбекский).
     * Языковые данные для них поставляются в составе модуля (rhvoice/data/languages).
     */
    public const VOICE_DATA = [
        'aleksandr' => ['Aleksandr (Russian)', 'ru-RU'],
        'aleksandr-hq' => ['Aleksandr-hq (Russian)', 'ru-RU'],
        'anna' => ['Anna (Russian)', 'ru-RU'],
        'arina' => ['Arina (Russian)', 'ru-RU'],
        'artemiy' => ['Artemiy (Russian)', 'ru-RU'],
        'elena' => ['Elena (Russian)', 'ru-RU'],
        'evgeniy-rus' => ['Evgeniy-rus (Russian)', 'ru-RU'],
        'irina' => ['Irina (Russian)', 'ru-RU'],
        'mikhail' => ['Mikhail (Russian)', 'ru-RU'],
        'pavel' => ['Pavel (Russian)', 'ru-RU'],
        'tatiana' => ['Tatiana (Russian)', 'ru-RU'],
        'timofey' => ['Timofey (Russian)', 'ru-RU'],
        'umka' => ['Umka (Russian)', 'ru-RU'],
        'victoria' => ['Victoria (Russian)', 'ru-RU'],
        'vitaliy' => ['Vitaliy (Russian)', 'ru-RU'],
        'vitaliy-ng' => ['Vitaliy-ng (Russian)', 'ru-RU'],
        'vsevolod' => ['Vsevolod (Russian)', 'ru-RU'],
        'yuriy' => ['Yuriy (Russian)', 'ru-RU'],

        'sevinch' => ['Sevinch (Uzbek)', 'uz-UZ'],
    ];

    /**
     * Карта «ключ голоса → GitHub-репозиторий» для докачки по запросу.
     * Владелец org регистронезависим на GitHub.
     */
    public const VOICE_REPO = [
        'aleksandr'    => 'RHVoice/aleksandr-rus',
        'aleksandr-hq' => 'RHVoice/aleksandr-hq-rus',
        'anna'         => 'RHVoice/anna-rus',
        'arina'        => 'RHVoice/arina-rus',
        'artemiy'      => 'RHVoice/artemiy-rus',
        'elena'        => 'RHVoice/elena-rus',
        'evgeniy-rus'  => 'RHVoice/evgeniy-rus',
        'irina'        => 'RHVoice/irina-rus',
        'mikhail'      => 'RHVoice/mikhail-rus',
        'pavel'        => 'RHVoice/pavel-rus',
        'tatiana'      => 'RHVoice/tatiana-rus',
        'timofey'      => 'RHVoice/timofey-rus',
        'umka'         => 'RHVoice/umka-rus',
        'victoria'     => 'RHVoice/victoria-rus',
        'vitaliy'      => 'RHVoice/vitaliy-rus',
        'vitaliy-ng'   => 'RHVoice/vitaliy-ng-rus',
        'vsevolod'     => 'RHVoice/vsevolod-rus',
        'yuriy'        => 'RHVoice/yuriy-rus',
        'sevinch'      => 'RHVoice/Sevinch-uzb',
    ];

    /**
     * Возвращает GitHub-репозиторий голоса (owner/repo) или '' если голос неизвестен.
     */
    public static function getVoiceRepo(string $voice): string
    {
        return self::VOICE_REPO[$voice] ?? '';
    }

    /**
     * @param bool $keyIsCode
     * @return array
     */
    public static function getSelectVoiceData(bool $keyIsCode = false):array {
        $result = [];
        foreach (self::VOICE_DATA as $key => [$name, $langCode]) {
            if($keyIsCode) {
                $result[strtolower($langCode)][] = $key;
            }else{
                $result[$key] = $name;
            }
        }
        return $result;
    }

    /**
     * @Primary
     * @Identity
     * @Column(type="integer", nullable=false)
     */
    public $id;

    /**
     * @Column(type="string", default="1", nullable=true)
     */
    public $voice;

    /**
     * @Column(type="string", default="40", nullable=true)
     */
    public $rate = "40";

    /**
     * Returns dynamic relations between module models and common models
     * MikoPBX check it in ModelsBase after every call to keep data consistent
     *
     * There is example to describe the relation between Providers and ModuleRHVoice models
     *
     * It is important to duplicate the relation alias on message field after Models\ word
     *
     * @param $calledModelObject
     *
     * @return void
     */
    public static function getDynamicRelations(&$calledModelObject): void
    {

    }

    public function initialize(): void
    {
        $this->setSource('m_ModuleRHVoice');
        parent::initialize();
    }


}
