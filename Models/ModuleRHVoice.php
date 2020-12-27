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

use MikoPBX\Common\Models\Providers;
use MikoPBX\Modules\Models\ModulesModelsBase;
use Phalcon\Mvc\Model\Relation;

class ModuleRHVoice extends ModulesModelsBase
{

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
        if (is_a($calledModelObject, Providers::class)) {
            $calledModelObject->belongsTo(
                'id',
                ModuleRHVoice::class,
                'dropdown_field',
                [
                    'alias'      => 'ModuleRHVoiceProvider',
                    'foreignKey' => [
                        'allowNulls' => 0,
                        'message'    => 'Models\ModuleRHVoiceProvider',
                        'action'     => Relation::ACTION_RESTRICT
                        // запретить удалять провайдера если есть ссылки в модуле
                    ],
                ]
            );
        }
    }

    public function initialize(): void
    {
        $this->setSource('m_ModuleRHVoice');
        $this->hasOne(
            'dropdown_field',
            Providers::class,
            'id',
            [
                'alias'      => 'Providers',
                'foreignKey' => [
                    'allowNulls' => true,
                    'action'     => Relation::NO_ACTION,
                ],
            ]
        );
        parent::initialize();
    }


}
