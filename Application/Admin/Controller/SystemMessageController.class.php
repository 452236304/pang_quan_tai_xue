<?php
namespace Admin\Controller;
use Think\Controller;
class SystemMessageController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $order = "createdate desc";
        $hybrid = I('get.hybrid');
        $map = array("hybrid"=>$hybrid);
        $param = $this->getMap();
        $data = $this->pager("system_message", "10", $order, $map, $param);
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
    	$doinfo = I("get.doinfo");
        $model = D("system_message");
        $data["info"] = $model->find($id);

        if($doinfo == "modify"){
            $d["status"] = I("post.status", 1);
            $d["title"] = I("post.title");
            $d["content"] = htmlspecialchars_decode(I("post.content"));
            $d["type"] = I("post.type", 0);
            $d["hybrid"] = I("get.hybrid");
            $d["param"] = I("post.param");
            $d["remark"] = I("post.remark");

            if($id == 0){
                $d["createdate"] = date("Y-m-d H:i");
				$d["updatetime"] = date("Y-m-d H:i:s");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("SystemMessage/listad", $this->getMap());
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
    	$model = D("system_message");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("SystemMessage/listad", $this->getMap());
    }

    public function send(){
        $id = I("get.id", 0);
        $hybrid = I("get.hybrid", 'client');
        $system = D("system_message")->find($id);
		D("system_message")->where(array('id'=>$id))->save(array('updatetime'=>date('Y-m-d H:i:s')));
        if(empty($system)){
            alert_back("系统消息不存在，推送消息失败");
        }

        $map = array("status"=>200);
        $users = D("user")->where($map)->select();

        $igeitui = D("Common/IGeTuiMessagePush");
        $igeitui->setHybrid($hybrid);

        foreach($users as $k=>$v){
            $entity = array(
                "hybrid"=>$system['hybrid'], "status"=>0, "sendid"=>0, "sender"=>"系统消息", "userid"=>$v["id"],
                "title"=>$system["title"], "content"=>$system["content"], "param"=>$system["param"],
                "createdate"=>date("Y-m-d H:i:s"), "type"=>$system["type"], "systemid"=>$system["id"]
            );

            $map = array("systemid"=>$system["id"],"userid"=>$v["id"]);
            $check = D("user_message")->where($map)->find();
            if(empty($check)){
                D("user_message")->add($entity);
            } else{
                D("user_message")->where($map)->save($entity);
            }

            $ext = null;
            if($system["param"]){
                $ext = json_decode($system["param"]);
            }
            if ($hybrid == 'client') {
                $bz = $v["clientid"];
                $xt = $v["system"];
            }else{
                $bz = $v["sclientid"];
                $xt = $v["ssystem"];
            }
            $igeitui->PushMessageToSingle($bz, $xt, "您有一条新的消息", $system["title"], $ext);
        }

    	alert("消息推送成功", U("SystemMessage/listad", $this->getMap()));
    }

    public function getMap(){
        $p = I("get.p");
        $hybrid = I("get.hybrid");
        $map = array("p"=>$p, "hybrid"=>$hybrid);
        return $map;
    }
}