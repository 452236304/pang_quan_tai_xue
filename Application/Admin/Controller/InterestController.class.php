<?php
namespace Admin\Controller;
use Think\Controller;
class InterestController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $order = "createtime desc";
        $param = $this->getMap();
        $count = D("interest")->count();
        $model = D("interest");
        $data = $this->pager(array("mo"=>$model, "count"=>$count), "10", $order, $map);

        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->show();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
		if(I('get.doinfo')=='modify'){
			$data['title']=I('post.title');
			$data['cate_id']=I('post.cate_id');
			$data['status']=I('post.status');
			switch($data['cate_id']){
				case '1':
					$data['category']='热门';
					break;
				case '2':
					$data['category']='社区';
					break;
				case '3':
					$data['category']='健康';
					break;
			}
			$id=I('post.id');
			if($id){
				D('interest')->where(array('id'=>$id))->save($data);
			}else{
				$data['createtime']=date('Y-m-d H:i:s');
				D('interest')->add($data);
			}
			$this->redirect('Interest/listad',$this->getMap());
		}
		$id=I('get.id');
		if($id){
			$info=D('interest')->where(array('id'=>$id))->find();
			$this->assign('info',$info);
		}
        $this->display();
    }

    

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("interest");
    	$id = I("get.id");
        $model->delete($id);
    	$this->redirect("Interest/listad", $this->getMap());
    }

    public function getMap(){
        $p = I("get.p");
        $role = I("get.role");
        $status = I("get.status");
        $keyword = I("post.keyword");
        if(empty($keyword)){
            $keyword = mb_convert_encoding($_GET["keyword"], "UTF-8", "gb2312");
        }
        $map = array("p"=>$p, "keyword"=>$keyword, "role"=>$role, "status"=>$status);
        return $map;
    }
}