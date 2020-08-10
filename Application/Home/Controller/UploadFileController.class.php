<?php
namespace Home\Controller;

class UploadFileController
{
    public $fileInfo = [];
    public $filePath;
    public $fileName;
    public $fileSize;
    public $fileType;
    public $fileExt;
    public $flag;
    protected $errMsg = ['error'=>0, 'msg'=>'', 'data'=>''];

    /**
     * Upload_file constructor.
     * @param string $filePath  保存路径
     * @param int $fileSize     文件大小（单位：M）
     * @param array $fileType   文件类型
     * @param array $fileExt    文件后缀
     * @param bool $flag        是否检查真实图片
     */
    public function __construct($filePath='./upload', $name='file', $fileSize=6, $fileType=['image/jpeg', 'image/jpg', 'image/png', 'image/gif'], $fileExt=['jpg', 'jpeg', 'png', 'gif'], $flag=true)
    {
        $this->fileInfo = $name;
        $this->filePath = $filePath;
        $this->fileSize = $fileSize * 1048576;
        $this->fileType = $fileType;
        $this->fileExt = $fileExt;
        $this->flag = $flag;
    }

    /**
     * 上传文件
     * @return array
     */
    public function upload_file()
    {
        if($this->checkError() && $this->checkSize() && $this->checkExt() && $this->checkType() && $this->checkPost() && $this->checkImg())
        {
            if(!empty($this->errMsg['msg']))
            {
                return $this->errMsg;
            }

            $path = $this->filePath . DS . date('Ymd');
            if(!is_dir($path))
            {
                mkdir($path, 0777, true);
                chmod($path, 0777);
            }
            $this->fileName = uniqid(rand(1000, 9999)) . '.' . pathinfo($this->fileInfo['name'])['extension'];
            $destination = $path . DS . $this->fileName;
            if(move_uploaded_file($this->fileInfo['tmp_name'], $destination))
            {
                $this->errMsg['error'] = 1;
                $this->errMsg['data'] = DS . $destination;
            }
            else
            {
                $this->errMsg['msg'] = '移动文件失败';
            }
        }

        return $this->errMsg;
    }


    /**
     * 检查图片真实
     * @return string
     */
    protected function checkImg()
    {
        if($this->flag)
        {
            if(!getimagesize($this->fileInfo['tmp_name']))
            {
                $this->errMsg['msg'] = '不是真实图片';
                return false;
            }
        }
        return true;
    }

    /**
     * 检查post上传
     * @return string
     */
    protected function checkPost()
    {
        if(!is_uploaded_file($this->fileInfo['tmp_name']))
        {
            $this->errMsg['msg'] = '不是POST上传';
            return false;
        }
        return true;
    }

    /**
     * 检查类型
     * @return mixed
     */
    protected function checkType()
    {
        if(!in_array($this->fileInfo['type'], $this->fileType))
        {
            $this->errMsg['msg'] = '文件类型错误';
            return false;
        }
        return true;
    }

    /**
     * 检查后缀
     * @return mixed
     */
    protected function checkExt()
    {
        if(!in_array(pathinfo($this->fileInfo['name'])['extension'], $this->fileExt))
        {
            $this->errMsg['msg'] = '文件错误';
            return false;
        }
        return true;
    }

    /**
     * 检查文件大小
     * @return string
     */
    protected function checkSize()
    {
        if($this->fileSize < $this->fileInfo['size'])
        {
            $this->errMsg['msg'] = '上传文件大于:'. ($this->fileSize / 1048576) .'M';
            return false;
        }
        return true;
    }

    /**
     * 检查文件错误码
     * @return string
     */
    protected function checkError()
    {
        $msg = '';
        switch ($this->fileInfo['error']) {
            case 1:
                $msg = "上传的文件超过了 php.ini 中 upload_max_filesize选项限制的值";
                break;
            case 2:
                $msg = "上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值";
                break;
            case 3:
                $msg = "文件只有部分被上传";
                break;
            case 4:
                $msg = "没有文件被上传";
                break;
            case 6:
                $msg = "找不到临时文件夹";
                break;
            case 7:
                $msg = "文件写入失败";
                break;
        }

        if(!empty($msg))
        {
            $this->errMsg['msg'] = $msg;
            return false;
        }
        return true;
    }
}