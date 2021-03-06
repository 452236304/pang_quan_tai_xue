<?php
namespace Admin\Controller;
use Think\Controller;
class FormSubmitController extends BaseController {

    protected $model;
    public function __construct()
    {
        parent::__construct();
        $this->model = D('form_submit');
    }

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = $this->model;

        $param = $this->getMap();
        $map = array();
        if($param["keyword"]){
            $map["user_name"] = array("like","%".$param["keyword"]."%");
            $map["user_phone"] = array("like","%".$param["keyword"]."%");
        }

        $order = "add_time DESC";
        $count = $model->where($map)->count();
        $data = $this->pager(array("mo"=>$model, "count"=>$count), "10", $order, $map, $param);

        $this->assign($data);
        $this->assign("map",$param);
        $this->display();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
        $doinfo = I("get.doinfo");
        $model = $this->model;
        if($id > 0)
        {
            $info = $model->find($id);
            $info['upload_file'] = explode(',', $info['upload_file']);
            $info['file'] = [];
            foreach ($info['upload_file'] as $k => $v)
            {
                $upload_file = [];
                $upload_file['ext'] = pathinfo($v)['extension'];
                $upload_file['path'] = $v;
                $upload_file['all_path'] = $this->DoUrlHandle($v);
                array_push($info['file'], $upload_file);
            }
//            var_dump($info);die;
        }

        if($doinfo == "modifyad")
        {
            $d = [];
            $d['status'] = I('post.status', 0);
            $d['status_time'] = time();

            if($id > 0)
            {
                $model->where("id=".$id)->save($d);
            }

            $url = U('FormSubmit/listad')."?".http_build_query($this->getMap());
            header('Location:'.$url);
            //$this->redirect($url);
        }

        $this->assign('info', $info);
        $this->assign("map",$this->getMap());
        $this->display();
    }

    //补全访问链接地址
    protected function DoUrlHandle($thumb){
        if(!empty($thumb) && (strpos(strtolower($thumb), 'http://') === false && strpos(strtolower($thumb), 'https://') === false)){
            $http_type = "http://";
            if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')){
                $http_type = "https://";
            }
            return $http_type.$_SERVER['HTTP_HOST'].$thumb;
        }else{
            return $thumb;
        }
    }

    public function doDeal()
    {
        $id = I("get.id", 0);
        $model = $this->model;
        if($id <= 0)
        {
            $this->error('id异常');
        }

        $info = $model->find($id);
        if(empty($info))
        {
            $this->error('订单不存在');
        }

        $d = [];
        $d['status'] = 1;
        $d['status_time'] = time();
        $model->where("id=".$id)->save($d);

        $url = U('FormSubmit/listad')."?".http_build_query($this->getMap());
        header('Location:'.$url);
    }

    public function getMap(){
        $p = I("get.p");
        $keyword = I("get.keyword");
        if(empty($keyword)){
            $keyword = mb_convert_encoding($_GET["keyword"], "UTF-8", "GB2312");
        }

        $map = array("p"=>$p, "keyword"=>$keyword);
        return $map;
    }
}