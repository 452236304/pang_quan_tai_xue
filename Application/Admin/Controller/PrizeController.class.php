<?php
namespace Admin\Controller;
use Think\Controller;
class PrizeController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("prize");
        $activity_id = I('get.activity_id',0);
        $map = array('activity_id'=>$activity_id);
        $data = $model->where($map)->order("createtime desc")->select();
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
        $model = D("prize");
        

        $param = $this->getMap();

        if($doinfo == "modify"){
            $d["activity_id"] = $param["activity_id"];
            if(empty($d["activity_id"])){
                $this->error("请选择关联的活动");
            }
            $d["type"] = I("post.type",0);
            $d["point"] = I("post.point",0);
			$d["coupon_id"] = I("post.coupon_id",0);
			$d["prize_num"] = I("post.prize_num",0);
            $d["title"] = I("post.title");
            $d["num"] = I("post.num");
            $d["probability"] = I("post.probability");
			$d["updatetime"] = date("Y-m-d H:i:s");
            if($id == 0){
                $d["createtime"] = date("Y-m-d H:i:s");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("Prize/listad", $this->getMap());
        }
		$data["info"] = $model->find($id);
		$map = array('status'=>1,'count'=>array('gt',0));
		$data['coupon']=D('coupon')->where($map)->select();
        $this->assign($data);
        $this->assign("map", $param);
    	$this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("prize");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("Prize/listad", $this->getMap());
    }

    public function sortad(){
        $id = I("post.id");
        $ordernum = I("post.ordernum");
        if(count($id)>0){
            $model = D("prize");
            foreach ($id as $key=>$val){
                $model->where("id=".$val)->setField("ordernum", $ordernum[$key]);
            }
            $this->redirect("Prize/listad", $this->getMap());
            exit();
        }else{
            $this->assign("jumpUrl", U("Prize/listad", $this->getMap()));
            $this->error("没有进行任何操作");
            exit();
        }
    }


    public function getMap(){
        $activity_id = I("get.activity_id");
        $map = array("activity_id"=>$activity_id);
        return $map;
    }
}