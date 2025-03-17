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
    public const VOICE_DATA = [
        'alan' => ['Alan (American English)', 'en-US'],
        'bdl' => ['Bdl (American English)', 'en-US'],
        'clb' => ['Clb (American English)', 'en-US'],
        'evgeniy-eng' => ['Evgeniy-eng (American English)', 'en-US'],
        'lyubov' => ['Lyubov (American English)', 'en-US'],
        'slt' => ['Slt (American English)', 'en-US'],

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

        'alicja' => ['Alicja (Polish)', 'pl-PL'],
        'cezary' => ['Cezary (Polish)', 'pl-PL'],
        'magda' => ['Magda (Polish)', 'pl-PL'],
        'michal' => ['Michal (Polish)', 'pl-PL'],
        'natan' => ['Natan (Polish)', 'pl-PL'],

        /*'anatol' => ['Anatol (Ukrainian)', 'uk-UA'],
        'marianna' => ['Marianna (Ukrainian)', 'uk-UA'],
        'natalia' => ['Natalia (Ukrainian)', 'uk-UA'],
        'volodymyr' => ['Volodymyr (Ukrainian)', 'uk-UA'],*/

        'azamat' => ['Azamat (Kyrgyz)', 'ky-KG'],
        'nazgul' => ['Nazgul (Kyrgyz)', 'ky-KG'],

        'hana' => ['Hana (Albanian)', 'sq-AL'],

        'kiko' => ['Kiko (Macedonian)', 'mk-MK'],
        'suze' => ['Suze (Macedonian)', 'mk-MK'],

        'letícia-f123' => ['Letícia-f123 (Brazilian Portuguese)', 'pt-BR'],
        'natia' => ['Natia (Georgian)', 'ka-GE'],
        'ondro' => ['Ondro (Slovak)', 'sk-SK'],
        'sevinch' => ['Sevinch (Uzbek)', 'uz-UZ'],
        'talgat' => ['Talgat (Tatar)', 'tt-RU'],
        'zdenek' => ['Zdenek (Czech)', 'cs-CZ'],
    ];

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
     * Integer field example
     *
     * @Column(type="integer", default="1", nullable=true)
     */
    public $local_port;

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
