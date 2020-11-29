<?php
require_once 'vendor/autoload.php';

use Wrdong\UploadFile;




class UploadFileTest {

    public $filePath;
    public $fileSize;
    public $filename;

    public function __construct($filePath)
    {
        if (is_file($filePath)) {
            $this->filePath = $filePath;
            $this->fileSize = filesize($filePath);
            $this->filename = pathinfo($filePath, PATHINFO_BASENAME);
        } else {
            throw new Exception("file is not found: " . $filePath, -1);
        }
    }

    public function getFileInfo()
    {
        return [
            'filePath' => $this->filePath,
            'fileSize' => $this->fileSize,
            'filename' => $this->filename
        ];
    }
    public function upload()
    {
        $uuid = '459bb719411947659168289db2318fe7';
//        $uuid = '';
        $storePath = '/var/www/html/basic/storage/ftp/zbk/' . date('Ymd') . '/' . $this->filename;
        $uploadFile = new UploadFile();
        $ret = $uploadFile->upload($storePath, $this->fileSize, $uuid, $offset = 0, $size = 0);

        print_r($ret);
    }
}


$filePath = '/var/www/html/basic/storage/ftp/zbk/kd3000.tgz';

$test = new UploadFileTest($filePath);

print_r($test->getFileInfo());
$test->upload();

