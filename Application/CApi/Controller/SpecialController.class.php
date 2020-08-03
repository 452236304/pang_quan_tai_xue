<?php
namespace CApi\Controller;
use Think\Controller;
class SpecialController extends BaseController {
	//会员中心首页
	public function index(){
		$this->UserAuthCheck();
		$user = $this->AuthUserInfo;
		//顶部广告图
		$bannermodel = D("banner");
		$map = array("status"=>1, "type"=>10);
		$banner = $bannermodel->where($map)->order("ordernum asc")->select();
		foreach($banner as $k=>$v){
		    $v["image"] = $this->DoUrlHandle($v["image"]);
		    $banner[$k] = $v;
		}
		//优惠券
		$map = array("type"=>3, "status"=>1, "count"=>array("gt", 0));
		$coupon=D('coupon')->where($map)->field('id,title,money')->select();
		foreach($coupon as $k=>$v){
			$map=array("couponid"=>$v['id'],'userid'=>$user['id']);
			//该用户领取该优惠券的数量
			$v['accept']=D('user_coupon')->where($map)->count();
			$coupon[$k]=$v;
			
		}
		
		//会员活动
		$map=array('status'=>1);
		$activity=D('vipactivity')->field("id,title,thumb,createtime")->where($map)->select();
		foreach($activity as $k=>$v){
			$v['thumb']=$this->DoUrlHandle($v['thumb']);
			$activity[$k]=$v;
		}
		$data = array(
		    "banner"=>$banner,"coupon"=>$coupon,"activity"=>$activity
		);
		return $data;
	}
	//会员详情
	public function vipdetail(){
		$this->UserAuthCheck();
		$user = $this->AuthUserInfo;
		$id=I('get.id');
		$map=array('id'=>$id);
		$activity=D('vipactivity')->field("id,title,images,thumb,createtime")->where($map)->find();
		$activity['thumb']=$this->DoUrlHandle($activity['thumb']);
		$activity['images']=$this->DoUrlHandle($activity['images']);
		$map=array('activity_id'=>$id,'user_id'=>$user['id']);
		$count=D('vipactivity_enroll')->where($map)->count();
		$activity['count']=$count;
		return $activity;
		
	}
	//活动报名
	public function enroll(){
		$this->UserAuthCheck();
		$user = $this->AuthUserInfo;
		$data=I('post.');
		$map=array('activity_id'=>$data['activity_id'],'user_id'=>$user['id']);
		$count=D('vipactivity_enroll')->where($map)->count();
		if($count>0){
			E('不能重复报名');
		}
		$d['activity_id']=$data['activity_id'];
		if(!$d['activity_id']){
			E('活动ID不能为空!');
		}
		$d['user_id']=$user['id'];
		if(!$d['user_id']){
			E('用户ID不能为空!');
		}
		$d['name']=$data['name'];
		if(!$d['name']){
			E('姓名不能为空!');
		}
		$d['gender']=$data['gender'];
		if(!$d['gender']){
			E('性别不能为空!');
		}
		$d['brith']=$data['brith'];
		if(!$d['brith']){
			E('生日不能为空!');
		}
		$d['mobile']=$data['mobile'];
		if(!$d['mobile']){
			E('手机号不能为空!');
		}
		if(!isMobile($d['mobile'])){
			E('手机号码格式不正确');
		}
		$d['province']=$data['province'];
		if(!$d['province']){
			E('省份不能为空!');
		}
		$d['city']=$data['city'];
		if(!$d['city']){
			E('城市不能为空!');
		}
		$d['region']=$data['region'];
		if(!$d['region']){
			E('区/县不能为空!');
		}
		$d['address']=$data['address'];
		if(!$d['address']){
			E('地址不能为空!');
		}
		$d['createtime']=date('Y-m-d H:i:s');
		$id=D('vipactivity_enroll')->add($d);
		$arr['id']=$id;
		$arr['success']=1;
		return $arr;
	}
	//专题列表
	public function lists(){
		$type=I('get.type');
		if($type){
			$map['type']=$type;
		}
		$page = I("get.page", 1);
		$row = I("get.row", 10);
		$begin = ($page-1)*$row;
		$map['status']=1;
		$count=D('special')->where($map)->count();
		$list=D('special')->where($map)->field('id,title,thumb,type,read_time,createtime')->order("ordernum asc")->select();
		$totalpage = ceil($count/$row);
		$this->SetPaginationHeader($totalpage, $count, $page, $row);
		foreach($list as $k=>$v){
			$v['thumb']=$this->DoUrlHandle($v['thumb']);
			//$v['avatar']=$this->DoUrlHandle($v['avatar']);
			//$v['video_file']=$this->DoUrlHandle($v['video_file']);
			//$v['audio_files']=$this->DoUrlListHandle($v['audio_files']);
			$list[$k]=$v;
		}
		return $list;
	}
	//专题详情
	public function detail(){
		$id=I('get.id');
		if(empty($id)){
			E('缺少专题ID');
		}
		$map['id']=$id;
		$info=D('special')->where($map)->find();
		$info['thumb']=$this->DoUrlHandle($info['thumb']);
		$info['avatar']=$this->DoUrlHandle($info['avatar']);
		$info['video_file']=$this->DoUrlHandle($info['video_file']);
		$map=array('status'=>1,'special_id'=>$id);
		$info['audio']=D('special_audio')->where($map)->order('ordernum asc')->select();
		foreach($info['audio'] as $k=>$v){
			$v['file_link']=$this->DoUrlHandle($v['file_link']);
			$info['audio'][$k]=$v;
		}
		return $info;
	}
	public function getCoupon(){
		$this->UserAuthCheck();
		$user = $this->AuthUserInfo;
		$id=I('post.id');
		$this->GrantUserCoupon($user,3,$id);
		return ;
	}
	
	//发放优惠券
	public function GrantUserCoupon($user,$type,$id){
	    if(empty($user)){
	        return;
	    }
	
	    $map = array("type"=>$type, "status"=>1, "count"=>array("gt", 0));
	    $register_coupon = D("coupon")->where($map)->select();
	    if(count($register_coupon) <= 0){
	        return;
	    }
	
	    //发放优惠券
	    $time = time();
	    foreach($register_coupon as $k=>$v){
	        $use_date = $v["use_date"];
	        $entity = array(
	            "couponid"=>$v["id"], "type"=>$v["type"], "code"=>$v["code"], "title"=>$v["title"],
	            "use_start_date"=>date("Y-m-d H:i:s", $time), "use_end_date"=>date("Y-m-d H:i:s", strtotime("+".$use_date." day", $time)),
	            "coupon_type"=>$v["coupon_type"], "status"=>0, "use_type"=>0,
	            "userid"=>$user["id"], "createdate"=>date("Y-m-d H:i:s", $time)
	        );
	    	switch($v['coupon_type']){
	    		case 0:
	    			$entity['money']=$v['money'];
	    			$entity['min_amount']=$v['min_amount'];
	    			break;
	    		case 1:
	    			$entity['product_id']=$v['product_id'];
	    			break;
	    		case 2:
	    			$entity['service_id']=$v['service_id'];
	    			break;
	    		case 3:
	    			$entity['org_id']=$v['org_id'];
	    			break;
	    	}
	        D("user_coupon")->add($entity);
	        //更新优惠券余量
	        $r_entity = array("count"=>($v["count"]-1), "sales"=>($v["sales"]+1));
	        D("coupon")->where("id=".$v["id"])->save($r_entity);
	    }
	
	}
}
