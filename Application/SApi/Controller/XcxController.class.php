<?php
namespace SApi\Controller;
use Think\Controller;
//小程序
class XcxController extends BaseController {
	
	//小程序商品列表的分类
	public function category(){
		$map = array('status'=>1);
		$list=D('service_category')->where($map)->order('ordernum asc')->select();
		foreach($list as $k=>&$v){
			$v['thumb'] = $this->DoUrlHandle($v['thumb']);
		}
		return $list;
	}
	//服务项目
	public function lists(){
		$categoryid = I("get.categoryid", 0);
	
		$model = D("service_project");
	
		$page = I("get.page", 1);
	    $row = I("get.row", 10);
	    $begin = ($page-1)*$row;
	
		$order = "sp.top desc, sp.ordernum asc, sp.sales desc, sp.browser_count desc";
	
	    //需要关联了服务项目星级价格才显示
		$map = array("sp.status"=>1);
		
		$where['lp.status'] = 1;
		$where['sp.assess'] = 1;
		$where['_logic'] = 'or';
		$map['_complex'] = $where;
		
		if($categoryid>0){
			$map['sp.categoryid']=$categoryid;
		}
	
	
	    $count = $model->alias('sp')->join('left join sj_service_project_level_price as lp on sp.id=lp.projectid')->where($map)->count();
	
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
			$v['type'] = 1;
			$list[$k] = $v;
		}
	
		return $list;
	}
}