<?php
namespace Admin\Controller;
use Think\Controller;
class ColumnController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("column");
        $data = $model->where('is_lock!=2')->order('ordernum asc')->select();
        $this->assign("data", $data);
        $this->assign("map", $this->getMap());
        $this->show();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $model = D("column");
        $data["info"] = $model->find($id);
        if($doinfo == "modify"){
            $param = I('');
            if(empty($param["thumb"])){
                $this->error('请上传图片');
            }

            
            if(!is_file('.'.$param['thumb'])){
                $this->error($param["thumb"].'图片路径无效');
            }

            $d["status"] = I("post.status", 1);
            $d["name"] = I("post.name");
            $d["remark"] = I("post.remark");
            $d["thumb"] = I("post.thumb");
            $d["ordernum"] = I("post.ordernum");
            if($id == 0){
                $d["createdate"] = date("Y-m-d H:i");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $id = $model->add($d);
            }
            
            $this->redirect("Column/listad", $this->getMap());
        }

        $this->assign($data);
    	$this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("column");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("Column/listad", $this->getMap());
    }

    public function getMap(){
        $p = I("get.p");
        $map = array("p"=>$p);
        return $map;
    }

    public function sortad(){
        $id = I("post.id");

        $ordernum = I("post.ordernum");

        if(count($id)>0){
            $model = D("Column");
            foreach ($id as $key=>$val){
                $model->where("id=".$val)->setField("ordernum", $ordernum[$key]);
            }
            $this->redirect("Column/listad", $this->getMap());
            exit();
        }else{
            $this->assign("jumpUrl", U("Column/listad", $this->getMap()));
            $this->error("没有进行任何操作");
            exit();
        }
    }
}