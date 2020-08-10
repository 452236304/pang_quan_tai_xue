<?php
namespace Admin\Controller;
use Think\Controller;
class FormSetController extends BaseController {

    protected $model;
    public function __construct()
    {
        parent::__construct();
        $this->model = D('form_set');
    }

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = $this->model;
        $type_content = [1=>'订单类型',2=>'科目类型',3=>'学历背景',4=>'引用数',5=>'写作格式'];

        $param = $this->getMap();
        $map = array();
        $map["type"] = $param["type"];
        if($param["keyword"]){
            $map["title"] = array("like","%".$param["keyword"]."%");
        }

        $order = "orderby ASC,add_time DESC";
        $count = $model->where($map)->count();
        $data = $this->pager(array("mo"=>$model, "count"=>$count), "10", $order, $map, $param);
        foreach ($data['data'] as $k => &$v)
        {
            $v['type_content'] = $type_content[$v['type']];
        }

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
        }

        if($doinfo == "modifyad")
        {
            $d = [];
            $d['title'] = I('post.title');
            $d['orderby'] = I('post.orderby');
            $param = $this->getMap();
            $d['type'] = $param['type'];
            $d['add_time'] = time();

            if(empty($d['title'])) $this->error('标题不为空');

            if($id > 0)
            {
                $model->where("id=".$id)->save($d);
            }
            else
            {
                $model->add($d);
            }

            $url = U('FormSet/listad')."?".http_build_query($this->getMap());
            header('Location:'.$url);
            //$this->redirect($url);
        }

        $this->assign('info', $info);
        $this->assign("map",$this->getMap());
        $this->display();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
        $model = $this->model;
        $id = I("get.id");
        $model->delete($id);
        $this->redirect("FormSet/listad",$this->getMap());
    }

    public function getMap(){
        $p = I("get.p");
        $keyword = I("get.keyword");
        if(empty($keyword)){
            $keyword = mb_convert_encoding($_GET["keyword"], "UTF-8", "GB2312");
        }
        $type = I('get.type',1);

        $map = array("p"=>$p, "keyword"=>$keyword,'type'=>$type);
        return $map;
    }
}