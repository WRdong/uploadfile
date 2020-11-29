<?php


namespace Wrdong;

use App\Utils\Uuid;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Storage;

class UploadFile
{
    protected $options;

    protected $filePath;

    protected $fileSize;

    protected $uploadDir;

    protected $sliceSize = 1024 * 1024; // 1M

    protected $msg = '';  // 返回信息描述

    private $methods = [];

    public function __construct(array $options = [])
    {

    }

    public function upload1()
    {
        echo "upload\n";
    }

    protected function uploadInit()
    {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir);
        }
        $uuid = $this->getUuid();
        $fileInfo = [
            'uuid' => $uuid,
            'filePath' => $this->filePath,
            'fileSize' => $this->fileSize,
            'uploadSize' => 0,
        ];
        $this->saveFileInfo($uuid, $fileInfo);
        return $uuid;
    }

    protected function getUuid()
    {
        return str_replace('-', '', uuid_create());
    }

    protected function getFileSize()
    {
        if (is_file($this->filePath)) {
            return filesize($this->filePath);
        } else {
            return 0;
        }
    }

    protected function getJsonFilePath($uuid)
    {
        return  '/tmp/upload-' . $uuid . '.txt';
    }

    protected function saveFileInfo($uuid, array $data)
    {
        $jsonFilePath = $this->getJsonFilePath($uuid);
        return file_put_contents($jsonFilePath, json_encode($data));
    }

    protected function getFileInfo($uuid)
    {
        $jsonFilePath = $this->getJsonFilePath($uuid);
        if (!is_file($jsonFilePath)) {
            return null;
        }
        $data = file_get_contents($jsonFilePath);
        $data = json_decode($data, true);
        return $data;
    }

    /**
     * @param string $filePath 存储文件路径
     * @param int $fileSize    上传文件大小
     * @param string $uuid  上传 id
     * @param int $offset  上传偏移
     * @param int $size 切片大小
     */
    public function upload(string $filePath, int $fileSize, string $uuid = '', int $offset = 0, int $size = 0, $file = null)
    {
        $this->info(sprintf("提交参数: uuid: %s, offset: %d, size: %d", $uuid, $offset, $size));
        if ($fileSize < 0 || $offset < 0 || $size < 0) {
            $this->msg = '参数错误：文件大小，偏移量，切片大小不得小于 0 ';
            return $this->error();
        }
        if ($offset > $fileSize || $size > $fileSize) {
            $this->msg = '参数错误：偏移量、切片大小不得超过文件大小';
            return $this->error();
        }

        $this->filePath = $filePath;
        $this->uploadDir = dirname($filePath);
        $this->fileSize = $fileSize;
        if (empty($uuid)) {
            $uuid = $this->uploadInit();
            $this->msg = '服务端准备就绪: 请开始上传数据。';
            return $this->nextUploadInfo($uuid);
        }
        $fileInfo = $this->getFileInfo($uuid);
        // 找不到记录文件 -- 重传
        if (empty($fileInfo)) {
            $uuid = $this->uploadInit();
            $this->msg = '服务端无法找到 [uuid] 匹配的记录文件, 重置 偏移参数为 0。';
            return $this->nextUploadInfo($uuid);;
        }
        $this->filePath = $fileInfo['filePath'] ?? $filePath;
        $currFileSize = $this->getFileSize(); //已经上传的文件大小
        if ($size == 0) {
            $this->msg = 'size 为 0， 查询文件上传状态，返回下一次上传切片文件信息';
            return $this->nextUploadInfo($uuid, $currFileSize);
        }
        if ($currFileSize !== $offset) {
            $this->msg = sprintf("服务端已有上传文件大小 %d, 客户端提交的切片偏移 %d, 无法匹配。", $currFileSize, $offset);
            return $this->nextUploadInfo($uuid, $currFileSize);
        }
        $sliceSize = $this->getSliceFileSize($file);
        if ($size != $sliceSize) {
            $this->msg = '服务端检查上传切片文件大小与提交参数 [size] 大小不一致, 可能出现数据丢失，请重新上传';
            return $this->nextUploadInfo($uuid, $currFileSize);
        }
        try {
            $fp = fopen($this->filePath, 'a'); //写入方式打开，将文件指针指向文件末尾。如果文件不存在则尝试创建之。
            fwrite($fp, $file, $sliceSize);
            fclose($fp);
        } catch (\Exception $e) {
            $this->msg = sprintf("文件上传失败: line: %d, %s", $e->getLine(), $e->getMessage());
            return $this->error();
        }
        $this->msg = '本次上传成功';
        return $this->nextUploadInfo($uuid, $currFileSize + $sliceSize);
    }

    public function getSliceFileSize($file = null)
    {
        if ($file) {
            return strlen($file);
        } else {
            return 0;
        }
        return $this->sliceSize;
    }

    protected function nextUploadInfo($uuid, $offset = 0)
    {
        //文件上传完成
        if ($offset >= $this->fileSize) {
            return [
                'code' => 0,
                'uuid' => $uuid,
                'offset' => 0,
                'sliceSize' => 0,
                'msg' => '文件上传完成',
                'isFinish' => 1,
            ];
        }
        //文件上传为完成
        if (($offset + $this->sliceSize) > $this->fileSize) {
            $sliceSize = $this->fileSize - $offset;
        } else {
            $sliceSize = $this->sliceSize;
        }
        return [
            'code' => 0,
            'uuid' => $uuid,
            'offset' => $offset,
            'sliceSize' => $sliceSize,
            'msg' => $this->msg,
            'isFinish' => 0,
        ];
    }

    protected function error()
    {
        return [
            'code' => -1,
            'msg' => $this->msg,
        ];
    }




    public function info($msg)
    {
        $this->methods['info']($msg);
    }

    public function setInfo(\Closure $func)
    {
        $this->methods['info'] = $func;
    }
}
