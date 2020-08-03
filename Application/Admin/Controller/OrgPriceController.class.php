<?php
namespace Admin\Controller;
use Think\Controller;
class OrgPriceController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("org_price");
        $type = I("get.type", 0);
        $orgid = I("get.orgid", 0);
        $map = array("op.type"=>$type, "op.orgid"=>$orgid);
        $data = $model->alias("op")->join("left join sj_org o on op.orgid=o.id")
            ->field("op.*,o.title as otitle")->where($map)->order("ordernum asc")->select();
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
        $model = D("org_price");

        if($doinfo == "modify"){
            $d["type"] = I("get.type", 0);
            $orgid = I("get.orgid");
            if(empty($orgid)){
                alert_back("请选择要关联的机构");
            }
            $d["orgid"] = $orgid;
            $d["status"] = I("post.status", 1);
            $d["title"] = I("post.title");
            $date = I("post.date", 0);
            $d["date"] = $date;
            $map = array("orgid"=>$orgid, "date"=>$date);
            $checkprice = $model->where($map)->find();
            if($checkprice && empty($id)){
                alert_back("当前机构已经添加过".$date."天周期的短期入住，请勿重复添加");
            }
            $d["price"] = I("post.price", 0);
            $d["remark"] = I("post.remark");
            $d["ordernum"] = I("post.ordernum", 0);

            if($id == 0){
                $d["createdate"] = date("Y-m-d H:i");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("OrgPrice/listad", $this->getMap());
        }

        $data["info"] = $model->find($id);
        $this->assign($data);
        $this->assign("map", $this->getMap());
    	$this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("org_price");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("OrgPrice/listad", $this->getMap());
    }

    public function sortad(){
        $id = I("post.id");
        $ordernum = I("post.ordernum");
        if(count($id)>0){
            $model = D("org_price");
            foreach ($id as $key=>$val){
                $model->where("id=".$val)->setField("ordernum", $ordernum[$key]);
            }
            $this->redirect("OrgPrice/listad", $this->getMap());
            exit();
        }else{
            $this->assign("jumpUrl", U("OrgPrice/listad", $this->getMap()));
            $this->error("没有进行任何操作");
            exit();
        }
    }

    public function getMap(){
        $type = I("get.type");
        $orgid = I("get.orgid");
        $p = I("get.p");
        $map = array("type"=>$type, "orgid"=>$orgid,"p"=>$p);
        return $map;
    }
}