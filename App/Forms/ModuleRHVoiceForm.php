<?php
/**
 * Copyright (C) MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Nikolay Beketov, 9 2018
 *
 */
namespace Modules\ModuleRHVoice\App\Forms;

use Modules\ModuleRHVoice\Models\ModuleRHVoice;
use Phalcon\Forms\Element\Select;
use Phalcon\Forms\Element\TextArea;
use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Numeric;
use Phalcon\Forms\Element\Hidden;


class ModuleRHVoiceForm extends Form
{
    public function initialize($entity = null, $options = null) :void
    {

        $this->add(new Hidden('id', ['value' => $entity->id]));
        $this->add(new TextArea('text', ['rows' => 3]));
        $this->add(new Numeric('local_port', [
            'maxlength'    => 5,
            'style'        => 'width: 120px;',
            'defaultValue' => 8080,
        ]));

        $library = new Select(
            'voice',
            ModuleRHVoice::getSelectVoiceData(),
            [
                'using'    => [
                    'id',
                    'name',
                ],
                'useEmpty' => false,
                'value'    => $entity->voice,
                'class'    => 'ui search selection dropdown library-type-select',
            ]
        );
        $this->add($library);

    }
}
