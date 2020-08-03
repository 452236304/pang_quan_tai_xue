<?php
namespace CApi\Controller;
use Think\Controller;
//积分商城
class PointController extends BaseLoggedController {
	public function home(){
		$user = $this->AuthUserInfo;
		$now = date('Y-m-d H:i:s');
		$map = array('starttime'=>array('elt',$now),'endtime'=>array('egt',$now),'status'=>1);
		$activity=D('activity')->where($map)->order('id desc')->find();
		$activity['thumb']=$this->DoUrlHandle($activity['thumb']);
		
		return array('point'=>$user['point'],'activity'=>$activity);
	}
	//积分商品列表 - cate= 0 全部,1 热门,2 商品,3 服务
	public function point_shop(){
		$user = $this->AuthUserInfo;
		$cate = I('get.cate',0);
		$page = I('get.page',1);
		$row = I('get.row',10);
		$keyword = I('get.keyword');
		$map = array('ps.status'=>1);
		if($keyword){
			$where['ps.title'] = array('like','%'.$keyword.'%');
			$where['ps.subtitle'] = array('like','%'.$keyword.'%');
			$where['_logic'] = 'or';
			$map['_complex'] = $where;
		}
		switch($cate){
			case 1:
				$map['ps.hot'] = 1;
				break;
			case 2:
				$map['ps.type'] = 0;
				break;
			case 3:
				$map['ps.type'] = 1;
				break;
		}
		$begin = ($page-1)*$row;
		
		$point_shop=D('point_shop')->alias('ps')->field('ps.*,pa.price market_price')->where($map)->join('left join sj_product_attribute pa on ps.attribute_id = pa.id')->limit($begin,$row)->select();
		foreach($point_shop as $k=>&$v){
			$v['thumb'] = $this->DoUrlHandle($v['thumb']);
			if($v['type'] == 1){
				$level_price = D('service_project_level_price')->where(array('projectid'=>$v['object_id']))->order('price desc')->find();
				$v['market_price'] = $level_price['price'];
			}
		}
		$this->SetPaginationHeader($totalpage,$count,$page,$row);
		return $point_shop;
	}
	
	//优惠卷
	public function coupon(){
		$user = $this->AuthUserInfo;
		//优惠券
		$map = array("type"=>4, "status"=>1, "count"=>array("gt", 0));
		$coupon=D('coupon')->where($map)->field('id,title,money,point,min_amount')->select();
		foreach($coupon as $k=>$v){
			$map=array("couponid"=>$v['id'],'userid'=>$user['id']);
			$v['money']=floatval($v['money']);
			$v['min_amount']=floatval($v['min_amount']);
			//该用户领取该优惠券的数量
			$v['accept']=D('user_coupon')->where($map)->count();
			$coupon[$k]=$v;
		}
		$vip=D('vip_rule')->select();
		foreach($vip as $k=>$v){
			$v['price']=floatval($v['price']);
			$vip[$k]=$v;
		}
		return array('coupon'=>$coupon,'vip'=>$vip);
	}
	//任务
	public function mission(){
		$user = $this->AuthUserInfo;
		//今天获得的积分
		$today = strtotime(date('Y-m-d'));
		$map = array('user_id'=>$user['id'],'adjust'=>array('gt',0),'add_time'=>array('gt',$today));
		$today_point=D('user_point_log')->field('SUM(adjust) adjust')->where($map)->find();
		
		//连续签到
		$map = array('user_id'=>$user['id']);
		$sign = D('sign_log')->where($map)->find();
		if($sign['sign_date'] == date('Y-m-d')){
			//已签到
			$is_sign = 1;
			$signday = ($sign['sign_num'])%7;
			if($signday == 0){
				$signday = 7;
			}
		}elseif($sign['sign_date'] == date('Y-m-d',strtotime('yesterday'))){
			//正在连续签到中
			$signday = ($sign['sign_num'])%7;
			$is_sign = 0;
		}else{
			$is_sign = 0;
			$signday = 0;
		}
		$sign_day = $signday;
		if($is_sign == 1){
			$sign_day--;
		}
		$date = array();
		for($i=$sign_day;$i>0;$i--){
			$date[] = date('m.d',strtotime('- '.$i.' day'));
		}
		$date[] = '今天';
		for($j=1;$j<7;$j++){
			$date[] = date('m.d',strtotime('+ '.$j.' day'));
		}
		
		$sign = array('signday'=>$signday,'is_sign'=>$is_sign,'date'=>$date);
		
		/**
		 * 任务列表
		 * 1=新手任务 2=每日任务 3=会员任务 4=邀请任务 5=志愿者任务 6=交易任务
		 */
		$pointRuleModel = D('point_rule');
		//新手任务
		$map = array('type'=>1);
		$list = $pointRuleModel->where($map)->select();
		foreach($list as $k=>&$v){
			$v['thumb'] = $this->DoUrlHandle($v['thumb']);
			$v['complate'] = D('PointLog','Service')->check_tag($user['id'],$v['tag']);
			if($v['tag'] == 'complate_usercare' || $v['tag'] == 'add_usercare'){
				$v['complate_num'] = D('PointLog','Service')->tag_surplus($user['id'],$v['tag']);
			}
		}
		$mission[] = array(
			'title'=>'新手任务',
			'list'=>$list
		);
		//每日任务
		$map = array('type'=>2);
		$list = $pointRuleModel->where($map)->select();
		foreach($list as $k=>&$v){
			$v['thumb'] = $this->DoUrlHandle($v['thumb']);
			$v['complate'] = D('PointLog','Service')->check_tag($user['id'],$v['tag']);
			$v['complate_num'] = D('PointLog','Service')->tag_surplus($user['id'],$v['tag']);
		}
		$mission[] = array(
			'title' => '每日任务',
			'list' => $list
		);
		//会员任务
		
		//查询会员等级
		$vip_info = D('user_vip')->where(array('user_id'=>$user['id'],'over_time'=>array('gt',date('Y-m-d'))))->find();
		$map = array('type'=>3);
		$list = array();
		switch($vip_info['level']){
			case 1:
				$map['tag'] = array('in','one_sign,two_levelup,three_levelup');
				$list = $pointRuleModel->where($map)->select();
				break;
			case 2:
				$map['tag'] = array('in','two_sign,three_levelup');
				$list = $pointRuleModel->where($map)->select();
				break;
			case 3:
				$map['tag'] = 'three_sign';
				$list = $pointRuleModel->where($map)->select();
				break;
			default:
				$map['tag'] = array('in','one_levelup,two_levelup,three_levelup');
				$list = $pointRuleModel->where($map)->select();
		}
		foreach($list as $k=>&$v){
			$v['thumb'] = $this->DoUrlHandle($v['thumb']);
			$v['complate'] = D('PointLog','Service')->check_tag($user['id'],$v['tag']);
		}
		
		$mission[] = array(
			'title'=>'会员任务',
			'list'=>$list
		);
		
		//邀请任务
		$map = array('type'=>4);
		$list = $pointRuleModel->where($map)->select();
		foreach($list as $k=>&$v){
			$v['thumb'] = $this->DoUrlHandle($v['thumb']);
			$v['complate'] = D('PointLog','Service')->check_tag($user['id'],$v['tag']);
		}
		$mission[] = array(
			'title'=>'邀请任务',
			'list'=>$list
		);
		//志愿者任务
		$map = array('type'=>5);
		$list = $pointRuleModel->where($map)->select();
		foreach($list as $k=>&$v){
			$v['thumb'] = $this->DoUrlHandle($v['thumb']);
			$v['complate'] = D('PointLog','Service')->check_tag($user['id'],$v['tag']);
		}
		$mission[] = array(
			'title'=>'志愿者任务',
			'list'=>$list
		);
		//交易任务
		$map = array('type'=>6);
		$list = $pointRuleModel->where($map)->select();
		foreach($list as $k=>&$v){
			$v['thumb'] = $this->DoUrlHandle($v['thumb']);
			$v['complate'] = D('PointLog','Service')->check_tag($user['id'],$v['tag']);
			$v['complate_num'] = D('PointLog','Service')->tag_surplus($user['id'],$v['tag']);
		}
		$mission[] = array(
			'title'=>'交易任务',
			'list'=>$list
		);
		
		return array(
			'point'=>$user['point'],'today_point'=>$today_point['adjust']?:0,'sign'=>$sign,
			'mission'=>$mission
		);
	}
	//领取优惠卷
	public function get_coupon(){
		$user = $this->AuthUserInfo;
		$coupon_id = I('post.coupon_id');
		if(empty($coupon_id)){
			E('请选择优惠卷');
		}
		$map = array('id'=>$coupon_id);
		$coupon=D('coupon')->where($map)->find();
		if($coupon['count']<=0){
			E('该优惠卷已兑换完');
		}
		//判断优惠卷是否已领取
		$map=array("couponid"=>$coupon_id,'userid'=>$user['id']);
		//该用户领取该优惠券的数量
		$accept_count=D('user_coupon')->where($map)->count();
		if($accept_count>0){
			E('已领取过该优惠卷');
		}
		
		//判断是否需要积分购买
		if($coupon['point']>0){
			//消耗积分
			$data = array('tag'=>'get_coupon','remark'=>'消耗积分兑换优惠卷');
			$pointlog=D('PointLog','Service')->append($user['id'],-$coupon['point'],$data);
			if(!$pointlog){
				E('积分不足，兑换优惠卷失败');
			}
		}
		$this->GrantUserCoupon($user,$coupon_id);
		
		return;
	}
	//签到
	public function sign(){
		$user = $this->AuthUserInfo;
		$map = array('user_id'=>$user['id']);
		$sign=D('sign_log')->where($map)->find();
		if($sign['sign_date']==date('Y-m-d')){
			E('已签到成功，请勿重复签到');
		}
		if($sign){
			if($sign['sign_date']==date('Y-m-d',strtotime('yesterday'))){
				//昨天签到过今天算连续签到
				$sign_num=($sign['sign_num']+1)%7;
				if($sign_num==0){
					//连续签到7天时有额外奖励
					D('Point','Service')->append($user['id'],'continuity_sign');
					$sign_num = 7;
				}
				$entity = array('sign_date'=>date('Y-m-d'),'sign_num'=>$sign['sign_num']+1);
				D('sign_log')->where($map)->save($entity);
				
			}else{
				//昨天未签到 重新开始签到
				$map = array('user_id'=>$user['id']);
				$entity = array('sign_date'=>date('Y-m-d'),'sign_num'=>1);
				$sign_num = 1;
				D('sign_log')->where($map)->save($entity);
			}
		}else{
			//无签到记录直接进行签到
			$entity = array('user_id'=>$user['id'],'sign_date'=>date('Y-m-d'),'sign_num'=>1);
			$sign_num = 1;
			D('sign_log')->add($entity);
		}
		//签到成功增加积分
		$data = array('remark'=>'连续签到'.$sign_num.'天');
		D('PointLog','Service')->append($user['id'],$sign_num,$data);
		return;
	}
	//积分明细
	public function point_log(){
		$user = $this->AuthUserInfo;
		
		$model = D('user_point_log');
		$page = I('get.page');
		$row = I('get.row');
		$type = I('get.type',0);//0=全部 1=收入 2=支出
		$begin = ($page-1)*$row;
		$map = array('user_id'=>$user['id']);
		switch($type){
			case 1:
				$map['adjust']=array('gt',0);
				break;
			case 2:
				$map['adjust']=array('lt',0);
				break;
		}
		$count = $model->where($map)->count();
		$list = $model->where($map)->field('id,adjust,remark,add_time')->limit($begin,$row)->select();
		foreach($list as $k=>$v){
			$v['add_time']=date('Y-m-d',$v['add_time']);
			$list[$k]=$v;
		}
		$totalpage = ceil($count/$row);
		$this->SetPaginationHeader($totalpage,$count,$page,$row);
		return $list;
	}
	//发放优惠券
	private function GrantUserCoupon($user,$id){
	    if(empty($user)){
	        return;
	    }
	
	    $map = array("id"=>$id, "status"=>1, "count"=>array("gt", 0));
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
	//抽奖活动详情
	public function activity_detail(){
		$user = $this->AuthUserInfo;
		$activity_id = I('get.activity_id');
		
		$now = date('Y-m-d H:i:s');
		$map = array('starttime'=>array('elt',$now),'endtime'=>array('egt',$now),'status'=>1,'id'=>$activity_id);
		$activity=D('activity')->where($map)->order('id desc')->find();
		if(empty($activity)){
			E('活动已结束');
		}
		$activity['thumb']=$this->DoUrlHandle($activity['thumb']);
		//奖品
		$prize=D('prize')->where(array('activity_id'=>$activity_id))->select();
		if(count($prize)%2==1){
			$prize[]=array('id'=>'-1','activity_id'=>$activity_id,'title'=>'谢谢参与','type'=>0);
		}
		foreach($prize as $k=>$v){
			if($v['type']==0){
				$v['thumb']=$this->DoUrlHandle('/Public/Home/img/smile.png');
			}else{
				$v['thumb']='';
			}
			$prize[$k]=$v;
		}
		//中奖纪录
		$prize_log=D('prize_log')->where(array('activity_id'=>$activity_id,'type'=>array('in','1,2')))->limit(0,10)->order('createtime desc')->select();
		
		//抽奖次数
		$map = array('user_id'=>$user['id'],'activity_id'=>$activity_id);
		$user_prize=D('user_prize')->field('surplus')->where($map)->find();
		
		return array('activity'=>$activity,'prize'=>$prize,'prize_log'=>$prize_log,'user_prize'=>$user_prize['surplus']?:0);
	}
	
	//积分抽奖
	public function prize(){
		$user = $this->AuthUserInfo;
		$activity_id = I('post.activity_id');
		$rs=D('Prize','Service')->prize($user['id'],$activity_id);
		return $rs;
	}
	//兑换抽奖次数
	public function buy_prize_num(){
		$user = $this->AuthUserInfo;
		$activity_id = I('post.activity_id');
		$user_prize=D('Prize','Service')->buy($user['id'],$activity_id);
		return $user_prize;
	}
	
	//浏览 转发等任务完成
	public function complate_mission(){
		$user = $this->AuthUserInfo;
		//类型 1商品 2服务 3机构 4文章 5分享
		$type = I('post.type');
		switch($type){
			case 1:
				D('Point','Service')->append($user['id'],'view_product');
				$result = D('PointLog','Service')->tag_surplus($user['id'],'view_product');
				break;
			case 2:
				D('Point','Service')->append($user['id'],'view_service');
				$result = D('PointLog','Service')->tag_surplus($user['id'],'view_service');
				break; 
			case 3:
				D('Point','Service')->append($user['id'],'view_pension');
				$result = D('PointLog','Service')->tag_surplus($user['id'],'view_pension');
				break;
			case 4:
				D('Point','Service')->append($user['id'],'view_article');
				$result = D('PointLog','Service')->tag_surplus($user['id'],'view_article');
				break;
			case 5:
				D('Point','Service')->append($user['id'],'share');
				$result = D('PointLog','Service')->tag_surplus($user['id'],'share');
				break;
		}
		
		return $result;
	}
	
	//会员每日领取积分
	public function member_sign(){
		$user = $this->AuthUserInfo;
		$vip_info = D('user_vip')->where(array('user_id'=>$user['id']))->find();
		switch($vip_info['level']){
			case 1:
				D('Point','Service')->append($user['id'],'one_sign');
				break;
			case 2:
				D('Point','Service')->append($user['id'],'two_sign');
				break;
			case 3:
				D('Point','Service')->append($user['id'],'three_sign');
				break;
		}
		return ;
	}
	//积分商品详情
	public function detail(){
		$id = I('get.id');
		$point_shop = D('point_shop')->where(array('id'=>$id,'status'=>1))->find();
		if(empty($point_shop)){
			E('商品已下架');
		}
		
		if($point_shop['type'] == 1){
			//服务
			
			$model = D("service_project");
			
			$map = array("status"=>1, "id"=>$point_shop['object_id']);
			$detail = $model->where($map)->find();
			if(empty($detail)){
				E("服务项目不存在");
			}
			
			$detail["thumb"] = $this->DoUrlHandle($detail["thumb"]);
			$detail["images"] = $this->DoUrlListHandle($detail["images"]);
			$detail["content"] = $this->UEditorUrlReplace($detail["content"]);
			$detail["tips_content"] = $this->UEditorUrlReplace($detail["tips_content"]);
			$detail["market_price"] = getNumberFormat($detail["market_price"]);
			$detail["price"] = getNumberFormat($point_shop["price"]);
			$detail["point"] = $point_shop["point"];
			
			//时长类型 - 月
			if($detail["time_type"] == 3){
				$detail["time"] = 1;
			}
			
			if(empty($detail["label"])){
				$detail["label"] = array("attr1"=>"", "attr2"=>"");
			} else{
				$detail["label"] = json_decode($detail["label"], true);
			}
			if($detail['point_rule']===0){
				$detail['point_point']=$detail['price']*100;
			}
			
			//是否已收藏
			$detail["is_collection"] = '0';
			if($this->UserAuthCheckLogin()){
			    $user = $this->AuthUserInfo;
			    $user_record_model = D("user_record");
			
			    $map = array("userid"=>$user["id"], "source"=>4, "type"=>1, "objectid"=>$id);
			    $record = $user_record_model->where($map)->find();
			    if ($record) {
			        $detail["is_collection"] = '1';
			    }
			}
			//店铺
			if($detail['company_id']==0){
				//默认酒店
				$detail['company_id']=1;
			}
			$map = array('id'=>$detail['company_id']);
			$business = D('business')->where($map)->find();
			$detail['company']=$business['title'];
			$detail['company_thumb']=$this->DoUrlHandle($detail['thumb']);
			$detail['company_address']=$bussiness['province'].$bussiness['city'].$bussiness['region'].$bussiness['address'];
			
			//有护理级别是查询 护理内容
			if($detail['assess'] == 1){
				$detail['detail'] = D('service_detail')->where(array('projectid'=>$detail['id']))->order('id asc')->select();
				foreach($detail['detail'] as $k=>&$v){
					$v['content'] = $this->UEditorUrlReplace($v['content']);
				}
			}
			
			if(empty($detail)){
				E("商品已下架");
			}
			$point_shop = $detail;
		}else{
			//商品
			$model = D("product");
			$map = array("status"=>1,"shelf"=>1,  "id"=>$point_shop['object_id']);
			$detail = $model->where($map)->find();
			if(empty($detail)){
				E("商品已下架");
			}
			
			$point_shop["thumb"] = $this->DoUrlHandle($detail["thumb"]);
			$point_shop["images"] = $this->DoUrlListHandle($detail["images"]);
			$point_shop["content"] = $this->UEditorUrlReplace($detail["content"]);
			$point_shop["spec_content"] = $this->UEditorUrlReplace($detail["spec_content"]);
			
			$point_shop['attribute'] = D('point_shop')->where(array('status'=>1,'object_id'=>$point_shop['object_id']))->select();
			
		}
		return $point_shop;
	}
	
	//积分订单创建
	public function createorder(){
		$user = $this->AuthUserInfo;
		//积分商品的ID
		$id = I('post.id');
		//购买的数量
		$quantity = I('post.quantity',1);
		
		$map = array('id'=>$id,'status'=>1);
		$object = D('point_shop')->where($map)->find();
		if(empty($object)){
			E('商品已下架');
		}
		
		if($object['type'] == 0){
			
			//商品订单
			$addressid = I('post.addressid');
			if(empty($addressid)){
				E("请选择收货地址");
			}
			
			return D('PointShop','Service')->create_product_order($user,$object['object_id'],$object['attribute_id'],$quantity,$addressid,$id);
			
		}else{
			//订单来源
			$hybrid = I('post.hybrid');
			$data = I('post.');
			return D('PointShop','Service')->create_service_order($user,$data,$hybrid,$id);
			
		}
		
	}
	
	
	//积分订单确认检查
	public function checkorder(){
		$user = $this->AuthUserInfo;
		//积分商品的ID
		$id = I('post.id');
		//购买的数量
		$quantity = I('post.quantity',1);
		
		$map = array('id'=>$id,'status'=>1);
		$object = D('point_shop')->where($map)->find();
		if(empty($object)){
			E('商品已下架');
		}
		if($object['type'] == 0){
			//商品订单
			$addressid = I('post.addressid');
			
			return D('PointShop','Service')->check_product_order($user,$object['object_id'],$object['attribute_id'],$quantity,$addressid,$id);
		}else{
			return D('PointShop','Service')->check_service_order($user,$object['object_id'],$id);
		}
		
	}
	
}