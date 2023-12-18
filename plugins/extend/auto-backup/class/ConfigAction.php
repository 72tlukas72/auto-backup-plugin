<?php

namespace SunlightExtend\AutoBackup;

use Sunlight\Plugin\Action\ConfigAction as BaseConfigAction;

class ConfigAction extends BaseConfigAction
{
    public function getConfigLabel(string $key): string
    {
        return _lang('auto-backup.config.' . $key);
    }
}
