<?php
namespace Admin\Controller;
use Think\Controller;
class UserPapersController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $userid = I("get.userid", 0);
        $order = "id asc";
        $param = $this->getMap();
        $map = array("userid"=>$userid);
        if($param["keyword"]){
            $where["name"] = array("like","%".$param["keyword"]."%");
            $where["_logic"] = "or";
            $map["_complex"] = $where;
        }
        $data = $this->pager('user_papers', "10", $order, $map);

        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->show();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
        $userid = I("get.userid");
    	$doinfo = I("get.doinfo");
        $model = D("user_papers");
        $data["info"] = $model->find($id);

        if($doinfo == "modify"){
            if (!is_numeric($userid)) {
                alert_back("缺少所需用户信息");
            }
            $d["userid"] = $userid;
            $d["name"] = I("post.name");
            $d["type"] = I("post.type");
            $d["begintime"] = I("post.begintime");
            $d["validtime"] = I("post.validtime");
            $d["images"] = I("post.images");
            $d["status"] = I("post.status",0);
            $d["updatetime"] = date("Y-m-d H:i:s");

            if ($d['type'] == 5) {
                $d["job"] = I("post.job");
            }
            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                if ($d['type'] == 1) {
                    $is = $model->where("userid=".$userid." and type=".$d['type'])->find();
                    if ($is) {
                        alert_back("身份证类型只能存在一份");
                    }
                }
                $d["createdate"] = date("Y-m-d H:i:s");
                $model->add($d);
            }

            
            $this->redirect("UserPapers/listad", $this->getMap());
        }

        $this->assign($data);
        $this->assign("map", $this->getMap());
    	$this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("user_papers");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("UserPapers/listad", $this->getMap());
    }


    public function getMap(){
        $p = I("get.p");
        $userid = I("get.userid");
        $keyword = I("post.keyword");
        if(empty($keyword)){
            $keyword = mb_convert_encoding($_GET["keyword"], "UTF-8", "gb2312");
        }
        $map = array("p"=>$p, "keyword"=>$keyword, "userid"=>$userid);
        return $map;
    }
}