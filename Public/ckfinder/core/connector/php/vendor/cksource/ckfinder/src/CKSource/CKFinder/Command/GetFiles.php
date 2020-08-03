<?php

namespace CKSource\CKFinder\Command;


use CKSource\CKFinder\Acl\Permission;
use CKSource\CKFinder\Filesystem\Folder\WorkingFolder;
use CKSource\CKFinder\Utils;

class GetFiles extends CommandAbstract
{
    protected $requires = array(Permission::FILE_VIEW);

    public function execute(WorkingFolder $workingFolder)
    {
        $data = new \stdClass();
        $files = $workingFolder->listFiles();

        $data->files = array();
        
        foreach ($files as $file) {

            $size = $file['size'];

            $size = ($size && $size < 1024) ? 1 : (int) round($size / 1024);

            $name = $file['basename']; //mb_convert_encoding($file['basename'], 'UTF-8', 'GBK');

            $fileObject = array(
                'name' => $name,
                'date' => Utils::formatDate($file['timestamp']),
                'size' => $size
            );

            $data->files[] = $fileObject;
        }

        // Sort files
        usort($data->files, function($a, $b) {
            return strnatcasecmp($a['date'], $b['date']);
        });

        return $data;
    }
}
