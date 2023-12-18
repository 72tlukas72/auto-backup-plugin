<?php

use Sunlight\Backup\BackupBuilder;

return function (int $lastRunTime, int $delay) {
    $backup_builder = new BackupBuilder();
    $backup_builder->setFullBackup(true);
    $enabled_dynamic_paths = [];
    
    if($this->getConfig()['upload'])
        $enabled_dynamic_paths[] = 'upload';
        
    if($this->getConfig()['images_user'])
        $enabled_dynamic_paths[] = 'images_user';
        
    if($this->getConfig()['images_articles'])
        $enabled_dynamic_paths[] = 'images_articles';
    
    if($this->getConfig()['images_galleries'])
        $enabled_dynamic_paths[] = 'images_galleries';
    
    foreach ($backup_builder->getDynamicPathNames() as $name) {
        if (!in_array($name, $enabled_dynamic_paths, true)) {
            $backup_builder->disableDynamicPath($name);
        }
    }

    $backup = $backup_builder->build();

    $backup_name = sprintf(
        '%s_%s_%s.zip',
        'auto_backup',
        Sunlight\Core::getBaseUrl()->getHost(),
        date('Y-m-d_His')
    );

    $backup->move(SL_ROOT . 'system/backup/' . $backup_name);
    
    $backups = glob(SL_ROOT . 'system/backup/auto_backup*');
    if(count($backups) > $this->getConfig()['count'])
        foreach($backups as $backup)
            if(time() - filectime($backup) > $this->getConfig()['count'] * 24 * 60 * 60) {
                unlink($backup);
                Sunlight\Logger::notice("system", "Deleted old auto-backup - " . $backup);
            }
};
