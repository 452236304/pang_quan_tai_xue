<?php
namespace Admin\Controller;
use Think\Controller;
class ApplyCompanyController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $order = "updatetime desc";
        $param = $this->getMap();
        if(I("post.keyword")){
            $map["name"] = array("like","%".I("post.keyword")."%");
    		$map["contact"] = array("like","%".I("post.keyword")."%");
            $map["mobile"] = array("like","%".I("post.keyword")."%");
            $map["_logic"] = "OR";
        }
        $data = $this->pager("company", "10", $order, $map, $param);
        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->display();
    }
	
	public function modifyad(){
		$id = I('get.id');
		$doinfo = I('get.doinfo');
		if($doinfo == 'modify'){
			$d = array();
			$d['status'] = I('post.status');
			$d['remark'] = I('post.remark');
			D('company')->where(array('id'=>$id))->save($d);
			$this->redirect('ApplyCompany/listad',$this->getMap());
		}
		$info = D('Company')->where(array('id'=>$id))->find();
		$this->assign('info',$info);
		$this->assign('map',$this->getMap());
	    $this->display();
	}

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("company");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("ApplyCompany/listad", $this->getMap());
    }

    public function getMap(){
        $p = I("get.p");
        $keyword = I("post.keyword");
        $map = array("p"=>$p, "keyword"=>$keyword);
        return $map;
    }
}