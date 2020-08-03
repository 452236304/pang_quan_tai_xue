<?php
namespace Admin\Controller;
use Think\Controller;
class PensionController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function lists(){
        $order = "ordernum asc";
        $param = $this->getMap();
        if(I("post.keyword")){
            $map["title"] = array("like","%".I("post.keyword")."%");
            $map["subtitle"] = array("like","%".I("post.keyword")."%");
            $map["_logic"] = "or";
        }


        $data = $this->pager("pension", "10", $order, $map, $param);
        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->display();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $model = D("pension");
        $data["info"] = $model->find($id);

        if($doinfo == "modify"){
            $param = I('');

            if(!empty($param)){
                $param['object'] = implode(',',$param['object']);
                $param['item'] = implode(',',$param['item']);
                $param['layout'] = implode(',',$param['layout']);
                $param['advantage'] = implode(',',$param['advantage']);
                
            }
			$param['check_in'] = htmlspecialchars_decode($param['check_in']);
			$param['discount'] = htmlspecialchars_decode($param['discount']);


            if($id == 0){
                $param["createtime"] = date("Y-m-d H:i");
            }

           // dump($param);die;
            if(!is_numeric($param['price_start']) || !is_numeric($param['price_end'])){
                $this->error('价格请输入数字');
            }
			
			if($param['price_start'] > $param['price_end']){
				$this->error('最低价不得大于最高价格');
			}
            if(!is_numeric($param['bed'])){
                $this->error('床位请输入数字');
            }


            if($id > 0){
                $model->where("id=".$id)->save($param);
            }else{
                $model->add($param);
            }
            
            $this->redirect("Pension/lists", $this->getMap());
        }
		$map = array('status'=>1);
        $datas = D('pension_advantage')->where($map)->select();
		$map = array('status'=>1);
		$data['pension_type']=D('pension_type')->where($map)->select();
        $this->assign('datas',$datas);
        $this->assign($data);
        $this->assign("map", $this->getMap());
    	$this->display();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("pension");
    	$id = I("get.id");
        $let_del = D("pension_online")->where('pension_id='.$id)->select();
        // if()
        // dump($let_del);die;
        if($let_del){
            $this->error('有在线参观的数据绑定,请先删除在线参观的数据');
        }
    	$model->delete($id);
    	$this->redirect("Pension/lists", $this->getMap());
    }

    public function pension_advantage(){
        $order = "id asc";
        $param = $this->getMap();
        $map = ['pension_id'=>$param['pension_id']];
        if(I("post.title")){
            $map["title"] = array("like","%".I("post.title")."%");
        }
        //$data = D('pension_advantage')->select();
        $data = $this->pager("pension_advantage", "10", $order, $map, $param);
        // dump($data);
        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->display();
    }

    public function pension_advantage_modifyad(){
        $id = I("get.id", 0);
        $doinfo = I("get.doinfo");
        $model = D("pension_advantage");
        $data["info"] = $model->find($id);

        if($doinfo == "modify"){
            $param = I('');
			$map = $this->getMap();
		
            if($id == 0){
                $param["createtime"] = date("Y-m-d H:i");
            }

           // dump($param);die;

			$param['pension_id'] = $map['pension_id'];
            if($id > 0){
                $model->where("id=".$id)->save($param);
            }else{
                $model->add($param);
            }
            
            $this->redirect("Pension/pension_advantage", $this->getMap());
        }

        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->display();
    }


    public function pension_advantage_del(){
        $model = D("pension_advantage");
        $id = I("get.id");
        $pension_data = D('pension')->select();
        foreach ($pension_data as $key => $value) {
            $value['advantage'] = explode(',', $value['advantage']);
            if(in_array($id,$value['advantage'])){
                $this->error('有机构绑定此优势');
            }
        }
        $model->delete($id);
        $this->redirect("Pension/pension_advantage", $this->getMap());
    }



    public function pension_online_list(){
        $order = "ordernum asc";
        $param = $this->getMap();
        if(I("post.keyword")){
            $map["title"] = array("like","%".I("post.keyword")."%");
        }
        $map["pension_id"] = I("id",0);
        // dump($map["pension_id"]);
        $this->assign('pension_id',$map["pension_id"]);
        $data = $this->pager("pension_online", "10", $order, $map, $param);
        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->display();
    }

    public function pension_online_modifyad(){
        $id = I("get.id", 0);
        $pension_id = I("get.pension_id", 0);
        // dump($pension_id);
        $doinfo = I("get.doinfo");
        $model = D("pension_online");
        $data["info"] = $model->find($id);

        if($doinfo == "modify"){
            $param = I('');


            if($id == 0){
                $param["createtime"] = date("Y-m-d H:i");
            }

            $pension_ids = ['id'=>$param['pension_id']];

            $getMap = $this->getMap();

            $getMap = array_merge($pension_ids, $getMap);
           // dump($getMap);die;


            if($id > 0){
                $model->where("id=".$id)->save($param);
            }else{
                $model->add($param);
            }
            $this->redirect("Pension/pension_online_list", $getMap);
        }

        $this->assign($data);
        $this->assign('pension_id',$pension_id);
        $this->assign("map", $this->getMap());
        $this->display();
    }


    public function pension_online_del(){
        $model = D("pension_online");
        $id = I("get.id");
        $model->delete($id);
        $this->redirect("Pension/pension_online_list", $this->getMap());
    }
	
	public function pension_type(){
	    $order = "id asc";
	    $param = $this->getMap();
	    $map = ['type'=>$param['type']];
	    if(I("post.title")){
	        $map["title"] = array("like","%".I("post.title")."%");
	    }
	    $data = $this->pager("pension_type", "10", $order, $map, $param);
	    // dump($data);
	    $this->assign($data);
	    $this->assign("map", $this->getMap());
	    $this->display();
	}
	
	public function pension_type_modifyad(){
	    $id = I("get.id", 0);
	    $doinfo = I("get.doinfo");
	    $model = D("pension_type");
	    $data["info"] = $model->find($id);
	
	    if($doinfo == "modify"){
	        $param = I('');
			$param['type'] = I('get.type');
	
	        if($id == 0){
	            $param["createtime"] = date("Y-m-d H:i");
	        }
	
	       // dump($param);die;
	
	
	        if($id > 0){
	            $model->where("id=".$id)->save($param);
	        }else{
	            $model->add($param);
	        }
	        
	        $this->redirect("Pension/pension_type", $this->getMap());
	    }
	
	    $this->assign($data);
	    $this->assign("map", $this->getMap());
	    $this->display();
	}
	
	
	public function pension_type_del(){
	    $model = D("pension_type");
	    $id = I("get.id");
	    $pension_data = D('pension')->select();
	    foreach ($pension_data as $key => $value) {
	        $value['advantage'] = explode(',', $value['advantage']);
	        if(in_array($id,$value['advantage'])){
	            $this->error('有机构绑定此优势');
	        }
	    }
	    $model->delete($id);
	    $this->redirect("Pension/pension_type", $this->getMap());
	}
	
    public function images(){
        $id = I('get.id');
        $model = D("pension_online");
            $pension_id = I("get.pension_id", 0);
                // dump($pension_id);
            $doinfo = I("get.doinfo");
            $model = D("pension_online");
            $data["info"] = $model->find($id);
            $this->assign($data);
            $this->assign('pension_id',$pension_id);
            $this->assign("map", $this->getMap());
            $this->display();
        
    }

    public function getMap(){
        $p = I("get.p");
		$type = I("get.type");
		$pension_id = I("get.pension_id");
        $keyword = I("post.keyword");
        if(empty($keyword)){
            $keyword = mb_convert_encoding($_GET["keyword"], "UTF-8", "gb2312");
        }
        $map = array("p"=>$p, "keyword"=>$keyword,'type'=>$type,'pension_id'=>$pension_id);
        return $map;
    }

    public function selectlatlng(){
        $address = I('post.address');
        $url = "https://apis.map.qq.com/ws/geocoder/v1/?address='".$address."'&key=G56BZ-TW4HW-XEWRG-OSDVP-RWGJJ-ZRBAG";
        $data = http_request($url);
        $this->ajaxReturn($data, "json");
    }
}