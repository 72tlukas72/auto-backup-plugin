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
            
    if($this->getConfig()['github'])
    {
        if($this->getConfig()['token'])
            $token = $this->getConfig()['token'];
        
        if($this->getConfig()['repoOwner'])
            $repoOwner = $this->getConfig()['repoOwner'];
            
        if($this->getConfig()['repoName'])
            $repoName = $this->getConfig()['repoName'];
            
        if($token != null && $repoOwner != null && $repoName != null)
        {
            $backupPath = SL_ROOT . 'system/backup/' . $backup_name;
            $url = "https://api.github.com/repos/$repoOwner/$repoName/contents/" . date('Y/m') . "/$backup_name";
            $fileContent = file_get_contents($backupPath);
            $fileContentEncoded = base64_encode($fileContent);
            $data = [
              'message' => $backup_name,
              'branch' => 'main',
              'content' => $fileContentEncoded
            ];
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: token $token",
                "User-Agent: PHP",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            
            curl_exec($ch);
            curl_close($ch);
        }
    }
};
