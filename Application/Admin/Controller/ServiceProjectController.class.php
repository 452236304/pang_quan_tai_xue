<?php
namespace Admin\Controller;
use Think\Controller;
class ServiceProjectController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $order = "p.status desc,p.updatetime desc";
        $param = $this->getMap();
        $map = array();
		if($param['top']!==''){
			$map['top']=$param['top'];
		}
		if($param['recommend']!==''){
			$map['recommend']=$param['recommend'];
		}
		if($param['seckill']!==''){
			$map['seckill']=$param['seckill'];
		}
        if($param["keyword"]){
            $map["p.title"] = array("like","%".$param["keyword"]."%");
            $map["p.subtitle"] = array("like","%".$param["keyword"]."%");
            $map["_logic"] = "or";
        }
        $count = D("service_project")->alias("p")->where($map)->count();
        $model = D("service_project")->alias("p")->join("left join sj_service_category as c on p.categoryid=c.id")
            ->field("p.*,c.role as service_role,c.title as categoryname");
        $data = $this->pager(array("mo"=>$model, "count"=>$count), "10", $order, $map, $param);
        $this->assign($data);
        $this->assign("map",$this->getMap());
        $this->display();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $model = D("service_project");
        $data["info"] = $model->find($id);

        if($doinfo == "modify"){
            $d["status"] = I("post.status", 1);
            $d["title"] = I("post.title");
            $d["subtitle"] = I("post.subtitle");
            $d["thumb"] = I("post.thumb");
			if(empty($d["thumb"])){
				$this->error('请上传图片');
			}
			// if(!is_http($d['thumb'])){
			// 	if(!is_file('.'.$d['thumb'])){
			// 		$this->error($d["thumb"].'图片路径无效');
			// 	}
            // }
            $begin_hour = I("post.begin_hour", 0);
            $end_hour = I("post.end_hour", 0);
            if($begin_hour >= $end_hour){
                $this->error("服务结束时间必须大于服务开始时间");
            }
            $d["begin_hour"] = $begin_hour;
            $d["end_hour"] = $end_hour;
            $d["images"] = I("post.images");
            $d["time"] = I("post.time");
            $d["attribute1"] = I("post.attribute1");
            $d["attribute2"] = I("post.attribute2");
            $d["attribute3"] = I("post.attribute3");
            $d["tips_content"] = htmlspecialchars_decode(I("post.tips_content"));
            $d["content"] = htmlspecialchars_decode(I("post.content"));
            $d["categoryid"] = I("post.categoryid", 0);
            $d["time_type"] = I("post.time_type", 0);
            $d["sales"] = I("post.sales", 0);
            $d["browser_count"] = I("post.browser_count", 0);
            $d["major_level"] = I("post.major_level", 1);
            $d["price"] = I("post.price", 0);
            $d["market_price"] = I("post.market_price", 0);
            $d["brokerage"] = I("post.brokerage", 0);
            $d["ordernum"] = I("post.ordernum", 0);
			$d["platform_money"] = I("post.platform_money", 0);
            $d["top"] = I("post.top", 0);
			$d["recommend"] = I("post.recommend", 0);
			$d["seckill"] = I("post.seckill", 0);
			$d["home_label"] = I("post.home_label");
			$d["home_label_after"] = I("post.home_label_after");
			
			$d["recommend_orderby"] = I("post.recommend_orderby", 0);
            $d["seckill_orderby"] = I("post.seckill_orderby", 0);
            
			$d["doctor"] = I("post.doctor", 0);
			$d["assess"] = I("post.assess", 0);

            $attr = array(
                "attr1"=>I("post.attr1"),
                "attr2"=>I("post.attr2")
            );
            $d["label"] = json_encode($attr);
			$d["updatetime"] = date("Y-m-d H:i:s");
            if($id == 0){
                $d["createdate"] = date("Y-m-d H:i");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            $url = U('ServiceProject/listad')."?".http_build_query($this->getMap());
			header('Location:'.$url);
            //$this->redirect($url);
        }
        //栏目列表
        $categoryModel = D("service_category");
        $categoryList = $categoryModel->order("ordernum asc")->select();
        $this->assign("categoryList", $categoryList);

        $data["info"]["label"] = json_decode($data["info"]["label"], true);
        $this->assign($data);
        $this->assign("map",$this->getMap());
		
    	$this->display();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("service_project");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("ServiceProject/listad",$this->getMap());
    }

    /**
     * [statusAd]
     * @return [type] [description]
     */
    public function topad(){
        $id = I("get.id", 0);
        $d['top'] = I("get.top", 0);
        $model = D("service_project");
        if($id > 0){
            $model->where("id=".$id)->save($d);
        }else{
            $model->add($d);
        }
        $this->redirect("ServiceProject/listad",$this->getMap());
    }

    public function sortad(){
        $id = I("post.id");
        $ordernum = I("post.ordernum");
        if(count($id)>0){
            $model = D("service_project");
            foreach ($id as $key=>$val){
                $model->where("id=".$val)->setField("ordernum", $ordernum[$key]);
            }
            $this->redirect("ServiceProject/listad",$this->getMap());
            exit();
        }else{
            $this->assign("jumpUrl", U("ServiceProject/listad",$this->getMap()));
            $this->error("没有进行任何操作");
            exit();
        }
    }
	
	public function longlist(){
        $param = $this->getMap();

        $map = array("projectid"=>$param["projectid"]);
	    $data = $this->pager("service_detail", "10", "id asc", $map, null);
	    $this->assign($data);
	    $this->assign("map", $param);
	    $this->show();
	}
	public function longad(){
	    $id = I("get.id", 0);
		$projectid = I("get.projectid", 0);
	    $doinfo = I("get.doinfo");
	    $model = D("service_detail");
	    $data["info"] = $model->find($id);
		if($doinfo == "modify"){
			$d["title"] = I("post.title");
			$d["content"] = htmlspecialchars_decode(I("post.content"));
			if($id > 0){
			    $model->where("id=".$id)->save($d);
			}else{
				$d['projectid'] = $projectid;
				$model->add($d);
			}
			$this->redirect("ServiceProject/longlist",$this->getMap());
		}
		$this->assign($data);
		$this->assign("map",$this->getMap());
		$this->display();
	}
	
	
    public function getMap(){
        $p = I("get.p");
        $keyword = I("get.keyword");
        if(empty($keyword)){
            $keyword = mb_convert_encoding($_GET["keyword"], "UTF-8", "GB2312");
        }
		$type = I('get.type');
		$recommend = I("get.recommend");
		$seckill = I("get.seckill");
		$top = I("get.top");
		$projectid = I("get.projectid");
        $map = array("p"=>$p, "keyword"=>$keyword,'recommend'=>$recommend,'seckill'=>$seckill,'top'=>$top, 'projectid'=>$projectid);
        return $map;
    }
}