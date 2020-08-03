<?php
namespace Admin\Controller;
use Think\Controller;
class CaseCateController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function index(){
        $order = "orderby ASC";
        $param = $this->getMap();
        $map = array();

        if($param["keyword"]){
            $map["title"] = array("like","%".$param["keyword"]."%");
        }
        $case_catemodel = D("case_cate");
        $count = $case_catemodel->count();
        $model = $case_catemodel;

        $data = $this->pager(array("mo"=>$model, "count"=>$count), "10", $order, $map, $param);

        $this->assign($data);
        $this->assign("map",$this->getMap());
        $this->display();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modify(){
        $id = I("get.id", 0);
        $doinfo = I("get.doinfo");
        $model = D("case_cate");
        if($id > 0)
        {
            $info = $model->find($id);
        }
        else
        {
            $info['status'] = 1;
        }

        if($doinfo == "modify")
        {
            $d = [];
            $d['title'] = I('post.title');
            $d['orderby'] = I('post.orderby');
            $d['status'] = I('post.status');
            $d['add_time'] = time();

            if($id > 0)
            {
                $model->where("id=".$id)->save($d);
            }
            else
            {
                $model->add($d);
            }

            $url = U('CaseCate/index')."?".http_build_query($this->getMap());
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
    public function del(){
        $model = D("case_cate");
        $id = I("get.id");
        $model->delete($id);
        $this->redirect("CaseCate/index",$this->getMap());
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