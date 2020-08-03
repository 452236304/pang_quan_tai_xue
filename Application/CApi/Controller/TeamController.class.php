<?php
namespace CApi\Controller;

class TeamController extends BaseLoggedController
{

    public function index(){
        $user = $this->AuthUserInfo;
        $user_id = $user['id'];
        $earnings = D('Brokerage', 'Service')->earningsToday($user_id);
        $invite = D('User', 'Service')->inviteToday($user_id);
        $amount = D('WalletWithdraw')->amountDrawn($user_id);
        $month = [
            'order_num' => D('BrokerageLog')->orderNumbsMonth($user_id),
            'order_amount' => D('BrokerageLog')->orderAmountMonth($user_id),
            'earnings' => D('BrokerageLog')->earningsMonth($user_id),
        ];
        $total = [
            'order_num' => D('BrokerageLog')->orderNumbs($user_id),
            'order_amount' => D('BrokerageLog')->orderAmount($user_id),
            'earnings' => D('BrokerageLog')->earnings($user_id),
        ];
        $data = [
            'user' => $user,
            'earnings' => $earnings,
            'amount' => $amount,
            'invite' => $invite,
            'month' => $month,
            'total' => $total
        ];

        $config = D('withdraw_config')->find(1);

        $data['wallet_remark'] = $config['remark'];
        return $data;
    }

    /**
     * Notes: 我的直推合伙人
     * User: dede
     * Date: 2020/3/10
     * Time: 6:05 下午
     */
    public function children(){
        $user = $this->AuthUserInfo;
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;
        $keyword = I('keyword');
		$time = I('time',0);
		$province = I('province');
		$city = I('city');
		$region = I('region');
		$price = I('price',0); //返利排序
        $where = [];
        if( $keyword ){
            $where = [
                [
                    'u.nickname' => ['like', '%'.$keyword.'%'],
                    'u.mobile' => ['like', '%'.$keyword.'%'],
                    '_logic' => 'OR',
                ]
            ];
        }
		//按时间筛选 0无 1=最近一个月 2=最近两个月 3=最近三个月 4=最近半年
		if($time > 0){
			switch($time){
				case 1:
					$where['u.registertime'] = ['gt',date('Y-m-d H:i:s',strtotime('-1 month'))];
					break;
				case 2:
					$where['u.registertime'] = ['gt',date('Y-m-d H:i:s',strtotime('-2 month'))];
					break;
				case 3:
					$where['u.registertime'] = ['gt',date('Y-m-d H:i:s',strtotime('-3 month'))];
					break;
				case 4:
					$where['u.registertime'] = ['gt',date('Y-m-d H:i:s',strtotime('-6 month'))];
					break;
			}
			
		}
		
		//返利金额排序 1升序 2降序
		if($price==1){
			$order='achievement asc';
		}
		if($price==2){
			$order='achievement desc';
		}
		
		//区域筛选
		if($province){
			$where['u.province']=['in',$province];
		}
		if($city){
			$where['u.city']=['in',$city];
		}
		if($region){
			$where['u.region']=['in',$region];
		}
		
		
        $data = D('User', 'Service')->children($user['id'], $where, $offset, $row,$order);
		foreach($data['rows'] as $k=>$v){
			$rs=D('UserFollow', 'Service')->check_follow($user['id'],$v['id']);
			if($rs){
				$v['is_follow']=1;
			}else{
				$v['is_follow']=0;
			}
			$data['rows'][$k]=$v;
		}
        $grandchildren = D('User', 'Service')->grandChildrenNum($user['id'], $where);

        $count = $data['total'];
        $totalpage = ceil($count / $row);
        $this->SetPaginationHeader($totalpage, $count, $page, $row);
        return ['team' => $data['rows'], 'children_num' => $count, 'grandchildren_num' => $grandchildren ];
    }

    /**
     * Notes: 我的间推合伙人
     * User: dede
     * Date: 2020/3/10
     * Time: 6:05 下午
     */
    public function grandChildren(){
        $user = $this->AuthUserInfo;
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;
        $keyword = I('keyword');
        $where = [];
        if( $keyword ){
            $where = [
                [
                    'nickname' => ['like', '%'.$keyword.'%'],
                    'mobile' => ['like', '%'.$keyword.'%'],
                    '_logic' => 'OR',
                ]
            ];
        }
        $data = D('User', 'Service')->grandChildren($user['id'], $where, $offset, $row);
		$children_num = D('User', 'Service')->childrenNum($user['id'], $where);
        $count = $data['total'];
        $totalpage = ceil($count / $row);
        $this->SetPaginationHeader($totalpage, $count, $page, $row);
        return ['team' => $data['rows'], 'children_num' => $children_num, 'grandchildren_num' => $count];
    }

    public function history(){
        $user = $this->AuthUserInfo;
        $where = [];
        $time_begin = I('time_begin', 1, 'intval');
        if( $time_begin == 1 ){
            // 最近30天
            $start_time = date('Y-m-d', strtotime('-30 day'));
            $where['BL.settle_time'] = ['egt', strtotime($start_time)];
        }else if( $time_begin == 1 ){
            // 最近3个月
            $start_time = date('Y-m-d', strtotime('-3 month'));
            $where['BL.settle_time'] = ['egt', strtotime($start_time)];
        }else if( $time_begin == 1 ){
            // 最近6个月
            $start_time = date('Y-m-d', strtotime('-6 month'));
            $where['BL.settle_time'] = ['egt', strtotime($start_time)];
        }else if( $time_begin == 1 ){
            // 最近1年
            $start_time = date('Y-m-d', strtotime('-1 year'));
            $where['BL.settle_time'] = ['egt', strtotime($start_time)];
        }
        $buy_from = I('buy_from', 0, 'intval');
        if( $buy_from == 1 ){
            // 直推合伙人
            $where['BL.from_grade'] = 1;
        }else if( $buy_from == 2 ){
            // 间推合伙人
            $where['BL.from_grade'] = 2;
        }
        $status = I('status');
        if( $status == 1 ){
            // 进行中
            $where['BL.status'] = 0;
        }else if( $status == 2 ){
            // 已生效
            $where['BL.status'] = 1;
        }else if( $status == 3 ){
            // 取消
            $where['BL.status'] = -1;
        }
        $keyword = I('keyword');
        if( $keyword ){
            $where['keyword'] = $keyword;
        }
        $user_id = $user['id'];
        $log = D('Brokerage', 'Service')->history($user_id, $where);
        foreach ($log as &$item){
            $item['avatar'] = DoUrlHandle($item['avatar']);
            if( $item['from_grade'] == 1 ){
                $item['from_grade'] = '直推合伙人';
            }elseif ( $item['from_grade'] == 2 ){
                $item['from_grade'] = '间推合伙人';
            }else{
                $item['from_grade'] = '';
            }
            if( $item['status'] == 0 ){
                $item['status'] = '进行中';
            }elseif ( $item['status'] == 1 ){
                $item['status'] = '已生效';
            }elseif ( $item['status'] == -1 ){
                $item['status'] = '已取消';
            }
        }
        $data = [
            'rows' => $log,
            'amount' => array_sum(array_column($log, 'amount'))
        ];
        return $data;
    }
	//生活优品
	public function product_lists(){
		//商品栏目（categoryid：1=配餐,2=家具,3=卫浴,4=辅具,5=改造）
		$categoryid = I("get.categoryid");
		/*if(!in_array($categoryid, [1,2,3,4,5])){
			E("请选择要查看的商品栏目");
		}*/
	
		//排序类型：1=综合,2=销量高,3=销量低,4=价格高,5=价格低） 默认综合排序
		$ordertype = I("get.ordertype", 1);
		//分类
		$typeid = I("get.typeid", 0);
		//关键字
		$keyword = I("get.keyword");
	    //始-金额范围
	    $beginamount = I("get.beginamount", 0);
	    //止-金额范围
	    $endamount = I("get.endamount", 0);
	    //产品分类
	    $attribute_cpid = I("get.attribute_cpid", 0);
	    //材质分类
	    $attribute_czid = I("get.attribute_czid", 0);
		//秒杀
		$seckill = I("get.seckill", 0);
	
		$model = D("product");
	
		$map = array("p.status"=>1, "p.type"=>0,'a.status'=>1,'p.brokerage'=>array('gt',0));
		if($seckill>0){
			$map["p.seckill"] = $seckill;
		}
	    if ($categoryid>0) {
	        $map["p.categoryid"] = $categoryid;
	    }
		if(!in_array($categoryid, [1,5]) && $typeid){
			$map["p.typeid"] = $typeid;
		}
		if($keyword){
			$where["p.title"] = array("like", "%".$keyword."%");
			$where["p.subtitle"] = array("like", "%".$keyword."%");
			$where["_logic"] = "or";
			$map["_complex"] = $where;
		}
	    if ($beginamount > 0 && $endamount > 0) {
	        $map['p.price'] = array(array('egt',$beginamount),array('elt',$endamount),'AND');
	    } else if ($beginamount > 0) {
	        $map['p.price'] = array('egt', $beginamount);
	    } else if ($endamount > 0) {
	        $map['p.price'] = array('elt', $endamount);
	    }
	    if ($attribute_cpid > 0) {
	        $map['p.attribute_cpid'] = $attribute_cpid;
	    }
	
	    if ($attribute_czid > 0) {
	        $map['p.attribute_czid'] = $attribute_czid;
	    }
		$page = I("get.page", 1);
	    $row = I("get.row", 10);
		$begin = ($page-1)*$row;
		
		$order = "p.top desc, p.recommend desc, p.ordernum asc, p.sales desc";
	    switch ($ordertype) {
	        case 2:
	            $order = 'p.sales desc';
	            break;
	        case 3:
	            $order = 'p.sales asc';
	            break;
	        case 4:
	            $order = 'p.price desc';
	            break;
	        case 5:
	            $order = 'p.price asc';
	            break;
	        default:
	            break;
	    }
	    $count = count($model->alias('p')->field('p.*,MAX(a.price) max_price,MIN(a.price) min_price')->where($map)->join('LEFT JOIN sj_product_attribute a on p.id=a.productid')->group('p.id')->order($order)->select());
	    $totalpage = ceil($count/$row);
		$list = $model->alias('p')->field('p.*,MAX(a.price) max_price,MIN(a.price) min_price')->where($map)->join('LEFT JOIN sj_product_attribute a on p.id=a.productid')->group('p.id')->order($order)->limit($begin, $row)->select();
	
		$this->SetPaginationHeader($totalpage, $count, $page, $row);
	
	    $commentmodel = D("product_comment");
	
		foreach($list as $k=>$v){
	        $v["thumb"] = $this->DoUrlHandle($v["thumb"]);
	
			if(empty($v["label"])){
				$v["label"] = array("attr"=>"", "color"=>"");
			} else{
				$v["label"] = json_decode($v["label"], true);
			}
	        $v["market_price"] = getNumberFormat($v["market_price"]);
	        $v["price"] = getNumberFormat($v["min_price"]);
			//$v["max_price"] = getNumberFormat($v["max_price"]);
			//$v["min_price"] = getNumberFormat($v["min_price"]);
	        
	        $map = array("status"=>1, "productid"=>$v["id"]);
	        //评论数量
	        $commentcount = $commentmodel->where($map)->count();
	        $v["comment_count"] = $commentcount;
	
	        //好评度
	        $map["socre"] = array("egt", 80);
	        $goodcomment = $commentmodel->where($map)->count();
	        if($commentcount > 0){
	            $v["comment_percent"] = ceil($goodcomment/$commentcount*100);
	        } else{
	            $v["comment_percent"] = 100;
	        }
	
			$list[$k] = $v;
		}
		
		return $list;
	}
	//服务项目
	public function service_lists(){
		$categoryid = I("get.categoryid", 0);
	
		$model = D("service_project");
	
		$page = I("get.page", 1);
	    $row = I("get.row", 10);
	    $begin = ($page-1)*$row;
	
		$order = "sp.top desc, sp.ordernum asc, sp.sales desc, sp.browser_count desc";
	
	    //需要关联了服务项目星级价格才显示
		$map = array("sp.status"=>1, 'sp.brokerage'=>array('gt',0));
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