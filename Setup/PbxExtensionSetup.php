<?php
/**
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Alexey Portnov, 5 2019
 */

namespace Modules\ModuleRHVoice\Setup;

use MikoPBX\Core\System\Util;
use MikoPBX\Modules\Setup\PbxExtensionSetupBase;


/**
 * Class PbxExtensionSetup
 * Module installer and uninstaller
 *
 * @package Modules\ModuleRHVoice\Setup
 */
class PbxExtensionSetup extends PbxExtensionSetupBase
{
    /**
     * Creates database structure according to models annotations
     *
     * If it necessary, it fills some default settings, and change sidebar menu item representation for this module
     *
     * After installation it registers module on PbxExtensionModules model
     *
     *
     * @return bool result of installation
     */
    public function installDB(): bool
    {
        $result = $this->createSettingsTableByModelsAnnotations();

        if ($result) {
            $result = $this->registerNewModule();
        }

        $this->addToSidebar();

        return $result;
    }

    /**
     * Create folders on PBX system and apply rights
     *
     * @return bool result of installation
     */
    public function installFiles(): bool
    {
        return parent::installFiles();
    }

    /**
     * Sets up ownerships and folder rights.
     * Базовый метод выставляет +x только на agi-bin и bin, поэтому отдельно
     * добавляем право исполнения нативным бинарникам RHVoice (по архитектурам).
     *
     * @return bool
     */
    public function fixFilesRights(): bool
    {
        $result = parent::fixFilesRights();

        foreach (['x86_64', 'aarch64'] as $arch) {
            $binDir = "{$this->moduleDir}/rhvoice/bin/{$arch}";
            if (is_dir($binDir)) {
                Util::addExecutableRights($binDir);
            }
        }

        return $result;
    }

    /**
     * Unregister module on PbxExtensionModules,
     * Makes data backup if $keepSettings is true
     *
     * Before delete module we can do some soft delete changes, f.e. change forwarding rules i.e.
     *
     * @param  $keepSettings bool creates backup folder with module settings
     *
     * @return bool uninstall result
     */
    public function unInstallDB($keepSettings = false): bool
    {
        return parent::unInstallDB($keepSettings);
    }

}
