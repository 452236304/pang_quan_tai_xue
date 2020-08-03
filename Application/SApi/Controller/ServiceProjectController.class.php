<?php
namespace SApi\Controller;
use Think\Controller;
class ServiceProjectController extends BaseController {
	
	//上门照护信息
	public function index(){
		$bannermodel = D("banner");
		$map = array("status"=>1, "type"=>5);
		$banner = $bannermodel->where($map)->order("ordernum asc")->select();
		foreach($banner as $k=>$v){
			$v["image"] = $this->DoUrlHandle($v["image"]);
            
            if($v["param"]){
                $v["param"] = json_decode($v["param"], true);
            } else{
                $v["param"] = array("param_type"=>"-1", "param_id"=>"");
            }

			$banner[$k] = $v;
		}

		$aboutmodel = D("about");
		$map = array("status"=>1, "type"=>1);
		$about = $aboutmodel->where($map)->select();
		foreach($about as $k=>$v){
			$v["content"] = $this->DoUrlHandle($v["content"]);

			$about[$k] = $v;
		}

		$data = array(
			"banner"=>$banner, "about"=>$about
		);

		return $data;
	}

	//服务栏目
	public function category(){
		$role = I("get.role", 0);
		$type=I('get.type',0);
		$model = D("service_category");
		$map = array("status"=>1,"type"=>$type);
		if(in_array($role, [3,4,5])){
			$map["role"] = $role;
		}
		$list = $model->where($map)->order("ordernum asc")->select();
		foreach ($list as $k=>$v) {
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
			$list[$k] = $v;
		}

		return $list;
	}

	//健康医疗
	public function healthy_category(){
		$role = I("get.role", 0);
		$type=1;
		$model = D("service_category");
		$map = array("type"=>$type);
		if(in_array($role, [3,4,5])){
			$map["role"] = $role;
		}
		$list = $model->where($map)->order("ordernum asc")->select();
		foreach ($list as $k=>$v) {
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
			$categoryid=$v['id'];
			$projectmodel = D("service_project");
			$where=array('categoryid'=>$categoryid);
			$plist=$projectmodel->where($where)->select();
			foreach($plist as $key=>$value){
				$value["thumb"] = $this->DoUrlHandle($value["thumb"]);
				$plist[$key]=$value;
			}
			$v['list']=$plist;
			$list[$k] = $v;
		}
	
		return $list;
	}

	//服务项目
	public function lists(){
		$categoryid = I("get.categoryid", 0);
		if(empty($categoryid)){
			E("请选择要查看的服务项目栏目");
		}

		$model = D("service_project");

		$page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;

		$order = "sp.top desc, sp.ordernum asc, sp.sales desc, sp.browser_count desc";

        //需要关联了服务项目星级价格才显示
		$map = array("sp.status"=>1, "sp.categoryid"=>$categoryid, "lp.status"=>1);


        $count = $model->alias('sp')->join('left join sj_service_project_level_price as lp on sp.id=lp.projectid')->group('sp.id')->where($map)->count();

        $totalpage = ceil($count/$row);
        $list = $model->alias('sp')->join('left join sj_service_project_level_price as lp on sp.id=lp.projectid')->field('sp.*')->group('sp.id')->where($map)->order($order)->limit($begin, $row)->select();
		
		$this->SetPaginationHeader($totalpage, $count, $page, $row);

		foreach($list as $k=>$v){
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);

			if(empty($v["label"])){
				$v["label"] = array("attr1"=>"", "attr2"=>"");
			} else{
				$v["label"] = json_decode($v["label"], true);
			}
            $v["market_price"] = getNumberFormat($v["market_price"]);
			$v["price"] = ($v["price"]);
			
			//时长类型 - 月
			if($v["time_type"] == 3){
				$v["time"] = 1;
			}

			$list[$k] = $v;
		}

		return $list;
	}

	//服务详情
	public function detail(){
		$id = I("get.id", 0);

		$model = D("service_project");

		$map = array("status"=>1, "id"=>$id);
		$detail = $model->where($map)->find();
		if(empty($detail)){
			E("服务项目不存在");
		}

		$detail["thumb"] = $this->DoUrlHandle($detail["thumb"]);
		$detail["images"] = $this->DoUrlListHandle($detail["images"]);
		$detail["content"] = $this->UEditorUrlReplace($detail["content"]);
		$detail["tips_content"] = $this->UEditorUrlReplace($detail["tips_content"]);
		$detail["market_price"] = getNumberFormat($detail["market_price"]);
		$detail["price"] = ($detail["price"]);

		//时长类型 - 月
		if($detail["time_type"] == 3){
			$detail["time"] = 1;
		}

		if(empty($detail["label"])){
			$detail["label"] = array("attr1"=>"", "attr2"=>"");
		} else{
			$detail["label"] = json_decode($detail["label"], true);
		}

		return $detail;
	}

	public function servicedetail(){
		$type=I('get.type');
		$map['id']=$type;
		$detail=D('service_detail')->where($map)->find();
		$detail["content"] = $this->UEditorUrlReplace($detail["content"]);
		return $detail;
	}
}