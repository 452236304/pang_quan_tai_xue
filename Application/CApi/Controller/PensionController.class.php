<?php
namespace CApi\Controller;
use Think\Controller;
class PensionController extends BaseController {
	//养老机构 - 首页
	public function index(){
		//机构首页 - 广告图
		$now = date('Y-m-d H:i:s');
		$map = array('status'=>1,'starttime'=>array('lt',$now),'endtime'=>array('gt',$now));
		$activity = D('pension_activity')->where($map)->order('price asc')->select();
		$banner = array();
		foreach($activity as $k=>$v){
			$banner[] = array('image'=>$this->DoUrlHandle($v['thumb']),'link'=>'/pagesA/organ/organActive/organActive?id='.$v['id']);
		}
		
		
		$map = array('status'=>1);
		$lists = D('pension')->field('id,title,subtitle,thumb,province,city,region')->where($map)->limit(0,6)->order('ordernum asc,createtime desc')->select();
		foreach($lists as $k=>&$v){
			$v['thumb'] = $this->DoUrlHandle($v['thumb']);
		}
		
		return array('banner'=>$banner,'lists'=>$lists);
	}
	//机构列表
	public function lists(){
		$page = I('get.page');
		$row = I('get.row');
		$begin = ($page-1)*$row;
		
		$order = array();
		$map = array('status'=>1);
		//床位需求 不限，1=10张以内，2=10-50张，3=50-100张，4=100-300张，5=300张以上
		$bed = I('get.bed',0);
		switch($bed){
			case 1:
				$map['bed'] = array('elt',10);
				break;
			case 2:
				$map['bed'] = array(array('egt',10),array('elt',50));
				break;
			case 3:
				$map['bed'] = array(array('egt',50),array('elt',100));
				break;
			case 4:
				$map['bed'] = array(array('egt',100),array('elt',300));
				break;
			case 5:
				$map['bed'] = array('egt',300);
				break;
		}
		//热门机构 or 冷门机构 0 不排序 1 升序(冷门) 2 降序(热门)
		$hot = I('get.hot',0);
		switch($hot){
			case 1:
				$order[]='hot asc';
				break;
			case 2:
				$order[]='hot desc';
				break;
		}
		//距离最近 or 距离最远
		$range = I('get.range',0);
		$lat = I('get.lat'); //纬度
		$lng = I('get.lng'); //经度
		if($range>0 && $lat && $lng){
			switch($range){
				case 1:
					$order[]='distance asc';
					break;
				case 2:
					$order[]='distance desc';
					break;
			}
			$field_append=',(6371 * acos ( cos ( radians('.$lat.') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians('.$lng.') ) + sin ( radians('.$lat.') ) * sin( radians( latitude ) ) ) ) AS distance';
		}else{
			$field_append='';
		}
		
		/*筛选*/
		//机构类型 array 多选
		$type = I('get.type');
		if($type){
			$map['type'] = array('in',$type);
		}
		
		//所在位置 多选
		$region = I('get.region');
		if($region){
			$map['region'] = array('in',$region);
		}
		
		//收住对象 多选
		$object = I('get.object');
		if($object){
			$object = explode(',',$object);
			$object = implode('%',$object);
			$map['object']=array('like','%'.$object.'%');
		}
		
		//特色服务 多选
		$item = I('get.item');
		if($item){
			$item = explode(',',$item);
			$item = implode('%',$item);
			$map['item']=array('like','%'.$item.'%');
		}
		
		//收费标准 1=1000以下 2=1000-3000 3=3000-5000 4=5000以上 单选
		$price = I('get.price',0);
		if($price != 0){
			switch($price){
				case 1:
					$map['price_start'] = array('elt',1000);
					break;
				case 2:
					$map['price_start'] = array('lt',3000);
					$map['price_end'] = array('gt',1000);
					break;
				case 3:
					$map['price_start'] = array('lt',5000);
					$map['price_end'] = array('gt',3000);
					break;
				case 4:
					$map['price_end'] = array('egt',5000);
					break;
			}
		}
		
		//户型设置 多选
		$layout = I('get.layout');
		if($layout){
			$layout = explode(',',$layout);
			$layout = implode('%',$layout);
			$map['layout']=array('like','%'.$layout.'%');
		}
		
		//机构性质 多选
		$nature = I('get.nature');
		if($nature){
			$map['nature'] = array('in',$nature);
		}
		$order[]='ordernum asc';
		$order[]='createtime desc';
		$order = implode(',',$order);
		$count = D('pension')->where($map)->count();
		$lists = D('pension')->field('id,title,subtitle,thumb,province,city,region'.$field_append)->where($map)->limit($begin,$row)->order($order)->select();
		$totalpage = ceil($count/$row);
		$this->SetPaginationHeader($totalpage,$count,$page,$row);
		foreach($lists as $k=>&$v){
			$v['thumb']=$this->DoUrlHandle($v['thumb']);
		}
		return $lists;
	}
	//机构详情
	public function detail(){
		$id = I('get.id');
		if(empty($id)){
			E('缺少机构ID');
		}
		$map = array('id'=>$id);
		$info = D('pension')->where($map)->find();
		if($info['status']!=1){
			E('机构已下架');
		}
		$info['thumb'] = $this->DoUrlHandle($info['thumb']);
		$info['images'] = $this->DoUrlListHandle($info['images']);
		
		$map = array('status'=>1,'pension_id'=>$id);
		$info['advantage'] = D('pension_advantage')->where($map)->order('ordernum asc')->select();
		foreach($info['advantage'] as $k=>&$v){
			$v['thumb'] = $this->DoUrlHandle($v['thumb']);
		}
		$map = array('pension_id'=>$id,'status'=>1);
		$info['online']=D('pension_online')->where($map)->order('ordernum asc,createtime desc')->select();
		foreach($info['online'] as $k=>&$v){
			$v['images']=$this->DoUrlListHandle($v['images']);
		}
		
		//是否已收藏
		$info["is_collection"] = '0';
		
		if($this->UserAuthCheckLogin()){
		    $user = $this->AuthUserInfo;
		    $user_record_model = D("user_record");
		
		    $map = array("userid"=>$user["id"], "source"=>5, "type"=>1, "objectid"=>$id);
		    $record = $user_record_model->where($map)->find();
		    if ($record) {
		        $info["is_collection"] = '1';
		    }
		}
		
		//匹配的活动
		$now = date('Y-m-d H:i:s');
		$map = array('status'=>1,'pension_id'=>$id,'starttime'=>array('lt',$now),'endtime'=>array('gt',$now));
		$info['activity'] = D('pension_activity')->where($map)->order('price asc')->find();
		$info['activity']['id'] = $info['id'];
		if(empty($info['activity']['price'])){
			$info['activity']['price'] = $info['price']; 
		}
		return $info;
	} 
	//机构动态
	public function Moment(){
		$id = I('get.id');
		$page = I("get.page", 1);
		$row = I("get.row", 10);
		$offset = ($page-1)*$row;
		$where = array('pension_id'=>$id);
		$moment = D('Moment', 'Service')->getList(0, $where, $offset, $row);
		return $moment;
	}
	
	//折扣住活动详情
	public function activity(){
		$id = I('get.id');
		$now = date('Y-m-d H:i:s');
		$map = array('status'=>1,'id'=>$id);
		$info=D('pension_activity')->where($map)->find();
		if(empty($info)){
			E('活动已下架');
		}
		if($info['starttime']>$now){
			E('活动尚未开始');
		}
		if($info['endtime']<$now){
			E('活动已结束');
		}
		$info['thumb'] = $this->DoUrlHandle($info['thumb']);
		$info["content"] = $this->UEditorUrlReplace($info["content"]);
		$info['id']=$info['pension_id'];
		return $info;
	}
	
	
}