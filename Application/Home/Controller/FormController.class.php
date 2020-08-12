<?php
namespace Home\Controller;

//use Home\UploadFileController;
//use Think\Controller;

use app\common\controller\Upload_file;

class FormController extends BaseController
{
    protected $title = [];
    protected $err = ['code'=>0,'data'=>[],'msg'=>''];
    public function __construct()
    {
        parent::__construct();
        $this->getTitle();
    }

    public function index()
    {
        $end_time = I('get.end_time');
        $this->assign('end_time', $end_time);
        $this->assign('title', $this->title);
        $this->assign('head_title', '下单页面');
        $this->display();
    }

    function upload()
    {
        $this->filePath = './upload/userfiles/';
        $this->fileSize = 10 * 1048576;
        $this->fileType = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/msword'];
        $this->fileExt = ['jpg', 'jpeg', 'png', 'gif','xlsx','xls','docx','doc'];
        $this->flag = false;

        $this_file = $_FILES['upload_file'];
        if(empty($this_file) || !isset($this_file))
        {
            $this->err['msg'] = '上传为空';
            $this->ajaxReturn($this->err);
        }

        $this->fileInfo = $this_file;
        $res = $this->upload_file();
        if($res['error'] != 1)
        {
            $this->err['msg'] = '上传失败';
            $this->ajaxReturn($this->err);
        }
        $this->err['code'] = 1;
        $this->err['msg'] = '上传成功';
        $this->err['data'] = ['all_path'=>$this->DoUrlHandle($res['data']),'path'=>$res['data']];
        $this->ajaxReturn($this->err);
    }

    public function submit()
    {
        $data = I('post.');
        if(empty($data))
        {
            $this->err['msg'] = '数据异常';
            $this->ajaxReturn($this->err);
        }

        $msg = '';
        $upload_file = [];
        foreach ($data as $k => $v )
        {
            if($k == "title1")
            {
                if($v == '')
                {
                    $msg = '请选择订单类型';
                }
            }
            else if($k == "title2")
            {
                if($v == '')
                {
                    $msg = '请选择科目类型';
                }
            }
            else if($k == "title3")
            {
                if($v == '')
                {
                    $msg = '请选择学历背景';
                }
            }
            else if($k == "title4")
            {
                if($v == '')
                {
                    $msg = '请选择引用数';
                }
            }
            else if($k == "title5")
            {
                if($v == '')
                {
                    $msg = '请选择写作格式';
                }
            }
            else if($k == "word_num")
            {
                if($v == '' || $v == 0)
                {
                    $msg = '请填写字数数量要求';
                }
            }
            else if($k == "end_time")
            {
                if($v == '')
                {
                    $msg = '请选择截止日期';
                }
            }
            else if($k == "font_space")
            {
                if($v == '')
                {
                    $msg = '请选择字体行距';
                }
            }
            else if($k == "user_name")
            {
                if($v == '')
                {
                    $msg = '请输入您的名称';
                }
            }
            else if($k == "user_phone")
            {
                if($v == '')
                {
                    $msg = '请填写联系电话';
                }
                else if(!preg_match("/^1[34578]\d{9}$/", $data['user_phone']))
                {
                    $msg = "联系电话有误，请重填";
                }
            }
            else if($k == "user_mail")
            {
                if($v == '')
                {
                    $msg = '请填写接收邮箱';
                }
                else if(!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/", $data['user_mail']))
                {
                    $msg = "邮箱错误，请重填";
                }
            }
            if(!empty($msg))
            {
                $this->err['msg'] = $msg;
                $this->ajaxReturn($this->err);
                break;
            }
        }

        $title = $this->title;
        $data['title1'] = $title['title1'][$data['title1']];
        $data['title2'] = $title['title2'][$data['title2']];
        $data['title3'] = $title['title3'][$data['title3']];
        $data['title4'] = $title['title4'][$data['title4']];
        $data['title5'] = $title['title5'][$data['title5']];
        $data['word_num'] = intval($data['word_num']);
        $data['forcase_price'] = bcmul($data['word_num'], 0.8, 2);
        $data['end_time'] = strtotime($data['end_time']);
        $data['upload_file'] = implode(',', $data['upload_file']);
        $data['add_time'] = time();

        $res = D('form_submit')->add($data);
        if(!$res)
        {
            $this->err['msg'] = '询价失败';
            $this->ajaxReturn($this->err);
        }

        $this->err['code'] = 1;
        $this->err['msg'] = '询价成功';
        $this->ajaxReturn($this->err);
    }

    /**
     * 获取表单设置
     */
    public function getTitle()
    {
        $model = D('form_set');
        $set = $model->field('id,title,type')->order('orderby ASC,add_time DESC')->select();
        $title = [];
        foreach ($set as $k => $v)
        {
            switch ($v['type'])
            {
                case 1:// 1订单类型
                    $title['title1'][$v['id']] = $v['title'];
                    break;
                case 2:// 2科目类型
                    $title['title2'][$v['id']] = $v['title'];
                    break;
                case 3:// 3学历背景
                    $title['title3'][$v['id']] = $v['title'];
                    break;
                case 4:// 4引用数
                    $title['title4'][$v['id']] = $v['title'];
                    break;
                case 5:// 5写作格式
                    $title['title5'][$v['id']] = $v['title'];
                    break;
            }
        }
        $this->title = $title;
    }



    public $fileInfo = [];
    public $filePath;
    public $fileName;
    public $fileSize;
    public $fileType;
    public $fileExt;
    public $flag;
    protected $errMsg = ['error'=>0, 'msg'=>'', 'data'=>''];
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

            $path = $this->filePath  . date('Ymd');
            if(!is_dir($path))
            {
                mkdir($path, 0777, true);
                chmod($path, 0777);
            }
            $this->fileName = uniqid(rand(1000, 9999)) . '.' . pathinfo($this->fileInfo['name'])['extension'];
            $destination = $path . '/' . $this->fileName;
            if(move_uploaded_file($this->fileInfo['tmp_name'], $destination))
            {
                $this->errMsg['error'] = 1;
                $this->errMsg['data'] = $destination;
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