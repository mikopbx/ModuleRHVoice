<?php
/**
 * Copyright (C) MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Nikolay Beketov, 9 2018
 *
 */
namespace Modules\ModuleRHVoice\App\Forms;

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

        $arrConnType = [
            'alan' => 'Alan (American English)',
            'bdl' => 'Bdl (American English)',
            'clb' => 'Clb (American English)',
            'evgeniy-eng' => 'Evgeniy Eng (American English)',
            'lyubov' => 'Lyubov (American English)',
            'slt' => 'Slt (American English)',
            'aleksandr' => 'Aleksandr (Russian)',
            'aleksandr-hq' => 'Aleksandr Hq (Russian)',
            'anna' => 'Anna (Russian)',
            'arina' => 'Arina (Russian)',
            'artemiy' => 'Artemiy (Russian)',
            'elena' => 'Elena (Russian)',
            'evgeniy-rus' => 'Evgeniy Rus (Russian)',
            'irina' => 'Irina (Russian)',
            'mikhail' => 'Mikhail (Russian)',
            'pavel' => 'Pavel (Russian)',
            'tatiana' => 'Tatiana (Russian)',
            'timofey' => 'Timofey (Russian)',
            'umka' => 'Umka (Russian)',
            'victoria' => 'Victoria (Russian)',
            'vitaliy' => 'Vitaliy (Russian)',
            'vitaliy-ng' => 'Vitaliy Ng (Russian)',
            'vsevolod' => 'Vsevolod (Russian)',
            'yuriy' => 'Yuriy (Russian)'
        ];
        $library = new Select(
            'voice',
            $arrConnType,
            [
                'using'    => [
                    'id',
                    'name',
                ],
                'useEmpty' => false,
                'value'    => $entity->voice,
                'class'    => 'ui selection dropdown library-type-select',
            ]
        );
        $this->add($library);

    }
}
