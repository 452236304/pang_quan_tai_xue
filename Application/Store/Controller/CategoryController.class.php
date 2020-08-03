<?php
namespace Store\Controller;
use Think\Controller;
class CategoryController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $depth = I("get.depth",1);
        $model = D("business_category");
        $where = [];
		$where['business_id'] = $_SESSION['businessID'];
        $data = $model->where($where)->order('path asc')->select();
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
        $model = D("business_category");
        $data["info"] = $model->find($id);
		
        $Category_p_data = $model->where(array('business_id'=>$_SESSION['businessID'],'pid'=>0,'id'=>array('neq',$id)))->select();
        $this->assign('Category_p_data',$Category_p_data);
		
        if($doinfo == "modify"){
			$path = '';
            $param = I('post.');  
            if(empty($param["thumb"])){
                $this->error('请上传图片');
            }
            if(!is_file('.'.$param['thumb'])){
                $this->error($param["thumb"].'图片路径无效');
            }
			
            $d["status"] = I("post.status", 1);
            $d["title"] = I("post.title");
            $d["remark"] = I("post.remark");
            $d["thumb"] = I("post.thumb");
            $d['ordernum'] = I("post.ordernum");
			$d['business_id'] = $_SESSION['businessID'];
            if($id == 0){
                $d["createtime"] = date("Y-m-d H:i:s");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
				$d['pid'] = I("post.pid",0);
				if($d['pid']>0){
					$map = array('id'=>$d['pid']);
					$ptitle = D('business_category')->field('title,path')->where($map)->find();
					$d['ptitle'] = $ptitle['title'];
					$path = $ptitle['path'].',';
				}
                $id = $model->add($d);
				$model->where(array('id'=>$id))->save(array('path'=>$path.$id));
            }
            
            $this->redirect("Category/listad", $this->getMap());
        }

        $this->assign($data);
    	$this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("business_category");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("Category/listad", $this->getMap());
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
            $model = D("business_category");
            foreach ($id as $key=>$val){
                $model->where("id=".$val)->setField("ordernum", $ordernum[$key]);
            }
            $this->redirect("category/listad", $this->getMap());
            exit();
        }else{
            $this->assign("jumpUrl", U("category/listad", $this->getMap()));
            $this->error("没有进行任何操作");
            exit();
        }
    }
}