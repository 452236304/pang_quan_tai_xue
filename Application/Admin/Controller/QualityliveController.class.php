<?php
namespace Admin\Controller;
use Think\Controller;
class QualityliveController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function lists(){
        $model = D("live_notice");
        $param = $this->getMap();
        $where = array();
        if($param['content']!==''){
            $where["content"] = array("like","%".$param["content"]."%");
        }
        
        $data = D("live_notice")->where($where)->order("update_time desc")->select();
        $this->assign("data", $data);
        $this->assign("map", $param);
        $this->show();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
    	// $doinfo = I("get.doinfo");
        $model = D("live_notice");
        

        $param = $this->getMap();

        if(IS_POST){
            
            $d["content"] = I("post.content",0);

            if(!$d["content"]){
                $this->error('请输入公告内容');
            }
            $d["show"] = I("post.show",0);
			$d["content"] = htmlspecialchars_decode($d["content"]);
			$d["update_time"] = date("Y-m-d H:i:s");

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("Qualitylive/lists", $this->getMap());
        }
		$data["info"] = $model->find($id);
		
        $this->assign($data);
        $this->assign("map", $param);
    	$this->show();
    }


    public function getMap(){
        $content = I("get.content");
        
        $map = array("content"=>$content);
        return $map;
    }
}