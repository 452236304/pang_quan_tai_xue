<?php
namespace Admin\Controller;
use Think\Controller;
class CategoryController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $depth = I("get.depth",1);
        $model = D("category");
        $where = [];
        if($depth!=1){
            $where['ca.depth'] = $depth;
        }else{
            $where['ca.depth'] = $depth;
        }
        $data = $model->field('ca.*,co.name column_name')->alias('ca')->where($where)->join('LEFT JOIN sj_column co on ca.parentid=co.id')->order("ca.ordernum asc")->select();
        foreach ($data as $key => $value) {
            if($data[$key]['category_pid']!=null){
                $category_name = $model->where('id='.$data[$key]['category_pid'])->field('name')->find();
                $data[$key]['category_p_name'] = $category_name['name'];
            }else{
                $data[$key]['category_p_name'] = "无";
            }
            
        }
        // dump($depth);
        $this->assign("depth", $depth);
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
        $depth = I("get.depth",1);
        // dump($depth);
    	$doinfo = I("get.doinfo");
        $model = D("category");
        $data["info"] = $model->find($id);

		$columnmodel=D('column');
		$column=$columnmodel->select();
		$this->assign('column',$column);

        $Category_p_data = $model->where('depth=1')->select();
        $this->assign('Category_p_data',$Category_p_data);
		
        if($doinfo == "modify"){
            $param = I('');
            // dump($param);die;    
            if(empty($param["thumb"])){
                $this->error('请上传图片');
            }
            if(isset($param['category_pid'])){
				if($param['category_pid']==''){
					$this->error('请选择父级分类');
				}
                $depth = $model->where('id='.$param['category_pid'])->field('depth')->find();
                $parentid = $model->where('id='.$param['category_pid'])->field('parentid')->find();
                $parentid = $parentid['parentid'];
                // dump($parentid);
            }else{
                $parentid = I("post.parentid", 0);
                $depth['depth'] = 0;
                // dump('222');
            }
            if(!is_file('.'.$param['thumb'])){
                $this->error($param["thumb"].'图片路径无效');
            }
            $d["status"] = I("post.status", 1);
            $d["parentid"] = $parentid;
            $d["name"] = I("post.name");
            $d["remark"] = I("post.remark");
            $d["thumb"] = I("post.thumb");
            $d["company_status"] = I("post.company_status");
            $d['category_pid'] = I("post.category_pid",0);;
            $d['depth'] = $depth['depth']+1;
            // dump($d);die;
            $d['ordernum'] = I("post.ordernum");
            if($id == 0){
                $d["createdate"] = date("Y-m-d H:i");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $id = $model->add($d);
            }
            
            $this->redirect("Category/listad", $this->getMap());
        }

        $this->assign("depth", $depth);
        $this->assign($data);
    	$this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("category");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("Category/listad", $this->getMap());
    }

    public function getMap(){
        $p = I("get.p");
        $depth = I("get.depth",1);
        $map = array("p"=>$p,'depth'=>$depth);
        return $map;
    }

    public function sortad(){
        $id = I("post.id");

        $ordernum = I("post.ordernum");

        if(count($id)>0){
            $model = D("category");
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