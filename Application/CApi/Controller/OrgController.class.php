<?php
namespace CApi\Controller;
use Think\Controller;
class OrgController extends BaseController {
	
	//机构首页
	public function index(){
		//机构筛选
		$org_type = [
			array("name"=>"个性需求", "type"=>2),
			array("name"=>"护理级别", "type"=>3),
		];
		$orgconditionmodel = D("org_condition");
		$map = array("status"=>1);
		foreach($org_type as $k=>$v){
			$map["type"] = $v["type"];
			$list = $orgconditionmodel->where($map)->select();

			$data["condition"]["type_".$v["type"]] = array("name"=>$v["name"], "type"=>$v["type"], "list"=>$list);
		}

		$orgmodel = D("org");

		//入住机构
		$pricemodel = D("org_price");
		$map = array("status"=>1);
		$orgids = $pricemodel->distinct(true)->field("orgid")->where($map)->order("orgid asc")->select();
		foreach ($orgids as $k=>$v) {
			$ids[] = $v["orgid"];
		}
		if($ids){
			$map = array("status"=>1, "id"=>array("in", $ids));
			$list = $orgmodel->where($map)->select();
			$data["condition"]["org"] = $list;
		}

		//服务特色推荐
		$map = array("status"=>1);
		$order = "top desc, ordernum asc";
		$list = $orgmodel->where($map)->order($order)->limit(4)->select();

        foreach($list as $k=>$v){
            $v["thumb"] = $this->DoUrlHandle($v["thumb"]);
            $v["images"] = $this->DoUrlListHandle($v["images"]);

            $list[$k] = $v;
        }

		$data["org"] = $list;

		$data["date"] = array(30, 90);
		
		//底部广告图
		$bannermodel = D("banner");
		$map = array("status"=>1, "type"=>3);
		$banner = $bannermodel->where($map)->select();
		foreach ($banner as $k=>$v) {
			$v["image"] = $this->DoUrlHandle($v["image"]);
            
            if($v["param"]){
                $v["param"] = json_decode($v["param"], true);
            } else{
                $v["param"] = array("param_type"=>"-1", "param_id"=>"");
            }

			$banner[$k] = $v;
		}
		$data["banner"] = $banner;

		return $data;
	}

    //根据天数获取对应机构列表
    public function orgprice(){
        $date = I("get.date", 0);

        $model = D("org_price");

        $map = array("o.status"=>1,"op.status"=>1, "op.date"=>$date);
        $list = $model->alias('op')->join('right join sj_org as o on op.orgid=o.id')->where($map)->field('o.id as orgid,o.title as orgtitle,op.id as orgpriceid,op.date,op.price')->order("o.top desc, o.ordernum asc")->select();

        return $list;
    }

	//机构类型首页
	public function typeindex(){
		$type = I("get.type");
		//机构筛选
		$org_type = [
			array("name"=>"地区", "type"=>1),
			array("name"=>"月费区间", "type"=>5)
		];

		if($type == 1){ //养老公寓
			$org_type[] = array("name"=>"个性需求", "type"=>2);
			$org_type[] = array("name"=>"护理级别", "type"=>3);
			$org_type[] = array("name"=>"长护险", "type"=>4);
		} else if($type == 2){ //护理院
			$org_type[] = array("name"=>"机构特色", "type"=>6);
			$org_type[] = array("name"=>"长护险", "type"=>4);
		} else if($type == 3){ //旅居养老
			$org_type[] = array("name"=>"个性需求", "type"=>2);
			$org_type[] = array("name"=>"旅居类型", "type"=>7);
		} else if($type == 4){ //医院

		}

		$orgconditionmodel = D("org_condition");
		$map = array("status"=>1);
		foreach($org_type as $k=>$v){
			$map["type"] = $v["type"];
			$list = $orgconditionmodel->where($map)->select();

			$data["condition"]["type_".$v["type"]] = array("name"=>$v["name"], "type"=>$v["type"], "list"=>$list);
		}

		$orgmodel = D("org");
		$map = array("status"=>1, "type"=>$type);
		$order = "top desc, ordernum asc";
		$list = $orgmodel->where($map)->order($order)->limit(4)->select();

        foreach($list as $k=>$v){
            $v["thumb"] = $this->DoUrlHandle($v["thumb"]);
            $v["images"] = $this->DoUrlListHandle($v["images"]);

            $list[$k] = $v;
        }

		$data["org"] = $list;
		
		return $data;
	}

	//机构列表
	public function lists(){
		//关键字
		$keyword = I("get.keyword");
		//地区
		$query1 = I("get.query1");
		//个性需求
		$query2 = I("get.query2");
		//护理级别
		$query3 = I("get.query3");
		//长护险
		$query4 = I("get.query4");
		//月费区间
		$query5_1 = I("get.query5_1", 0);
		$query5_2 = I("get.query5_2", 0);
		//机构特色
		$query6 = I("get.query6");
		//旅居类型
		$query7 = I("get.query7");

		$model = D("org");

		$map = array("status"=>1);
		if($keyword){
			$where["title"] = array("like", "%".$keyword."%");
			$where["subtitle"] = array("like", "%".$keyword."%");
			$where["_logic"] = "or";
			$map["_complex"] = $where;
		}
		if($query1){ //地区
            $query1 = explode("-", $query1);
			$map["province"] = $query1[0];
			$map["city"] = $query1[1];
			$map["region"] = $query1[2];
		}
		if($query2){ //个性需求
			$where["query2"] = array("like", "%".$query2."%");
		}
		if($query3){ //护理级别
			$where["query3"] = array("like", "%".$query3."%");
		}
		if($query4){ //长护险
			$map["query4"] = $query4;
		}
		if($query5_1){ //月费区间 大于
			$map["query5"] = array("egt", $query5_1);
		}
		if($query5_2){ //月费区间 小于
			$map["query5"] = array("elt", $query5_2);
		}
		if($query6){ //机构特色
			$where["query6"] = array("like", "%".$query6."%");
		}
		if($query7){ //旅居类型
			$map["query7"] = $query7;
		}

		$page = I("get.page", 1);
        $row = I("get.row", 10);
		$begin = ($page-1)*$row;

		$order = "top desc, ordernum asc";
		$count = $model->where($map)->count();
        $totalpage = ceil($count/$row);
		$list = $model->where($map)->order($order)->limit($begin, $row)->select();

		$this->SetPaginationHeader($totalpage, $count, $page, $row);

		foreach($list as $k=>$v){
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
			
			$list[$k] = $v;
		}

		return $list;
	}

	//机构详情
	public function detail(){
		$id = I("get.id", 0);

		$model = D("org");

		$map = array("status"=>1, "id"=>$id);
		$detail = $model->where($map)->find();
		if(empty($detail)){
			E("机构不存在");
		}

		$detail["thumb"] = $this->DoUrlHandle($detail["thumb"]);
		$detail["images"] = $this->DoUrlListHandle($detail["images"]);
		$detail["content1"] = $this->UEditorUrlReplace($detail["content1"]);
		$detail["content2"] = $this->UEditorUrlReplace($detail["content2"]);
		$detail["content3"] = $this->UEditorUrlReplace($detail["content3"]);
		$detail["content4"] = $this->UEditorUrlReplace($detail["content4"]);
		$detail["content5"] = $this->UEditorUrlReplace($detail["content5"]);

		//关联 1=预约参观活动 / 2=机构长住活动
		$activitymodel = D("org_activity_relation");
		$map = array("oa.type"=>array("in", [1,2]), "oar.orgid"=>$detail["id"]);
		$activity = $activitymodel->alias("oar")->join("left join sj_org_activity as oa on oar.activityid=oa.id")->field("oa.*,oar.dis_price")
			->where($map)->order("oa.ordernum asc")->select();
        $detail["activity"] = array();
		foreach($activity as $k=>$v){
			$detail["activity"][] = array(
				"id"=>$v["id"], "title"=>$v["title"], "type"=>$v["type"], "price"=>$v["price"], "dis_price"=>$v["dis_price"]
			);
		}

		//关联短期入住价格
		$orgpricemodel = D("org_price");
		$map = array("status"=>1, "type"=>1, "orgid"=>$detail["id"]);
		$detail["org_price"] = $orgpricemodel->where($map)->select();

		return $detail;
	}

	//机构评论列表
	public function comment(){
		$orgid = I("get.orgid", 0);
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;

        $model = D("org_comment");
        
        $order = "createdate desc";
        $map = array("status"=>1, "orgid"=>$orgid);
        $count = $model->where($map)->count();
        $totalpage = ceil($count/$row);
        $list = $model->where($map)->limit($begin, $row)->select();
		
		$this->SetPaginationHeader($totalpage, $count, $page, $row);

		foreach($list as $k=>$v){
			$v["avatar"] = $this->DoUrlHandle($v["avatar"]);
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
			$v["images"] = $this->DoUrlListHandle($v["images"]);

			$list[$k] = $v;
		}

		return $list;
	}
	
	//机构活动（1=预约参观/2=折扣长住）
	public function activity(){
		$type = I("get.type", 0);
		if(!in_array($type, [1,2])){
			E("请选择要查看的机构活动");
		}
		$list_type=I('get.list');
		$model = D("org_activity");
		$map = array("a.status"=>1, "a.type"=>$type);
		$orgid = I('get.orgid');
		if(empty($orgid) || $orgid=='undefinded'){
			$list = $model->alias('a')->where($map)->select();
		}else{
			$map['ar.orgid']=$orgid;
			$list = $model->alias('a')->field('a.*')->join('left join sj_org_activity_relation ar on a.id=ar.activityid')->where($map)->select();
		}
		if(count($list) <= 0){
			E("当前机构活动暂未开放，请耐心等候");
		}

		if(count($list) == 1 && $list_type!=1){
			$detail = $list[0];

			$detail['price'] = intval($detail['price']);
			$detail["thumb"] = $this->DoUrlHandle($detail["thumb"]);
			$detail["content"] = $this->UEditorUrlReplace($detail["content"]);

			$orgs = $this->orgactivityrelation($detail["id"]);
			
			$data = array(
				"detail"=>$detail, "list"=>$orgs
			);

			return $data;
		}

		foreach($list as $k=>$v){
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
            $v['price'] = intval($v['price']);
			$list[$k] = $v;
		}

        return array("list"=>$list);
	}

	//机构活动详情
	public function activitydetail(){
		$activityid = I("get.activityid", 0);

		$activitymodel = D("org_activity");

		$map = array("status"=>1, "id"=>$activityid);
		$detail = $activitymodel->where($map)->find();
		if(empty($detail)){
			E("您查看的机构活动的不存在");
		}
		
		$detail['price'] = intval($detail['price']);
		$detail["thumb"] = $this->DoUrlHandle($detail["thumb"]);
		$detail["content"] = $this->UEditorUrlReplace($detail["content"]);

		$list = $this->orgactivityrelation($detail["id"]);

		$data = array(
			"detail"=>$detail, "list"=>$list
		);

		return $data;
	}

	//根据活动id获取关联的机构列表
	private function orgactivityrelation($activityid){
		$activityrelationmodel = D("org_activity_relation");

		$map = array("ar.status"=>1, "ar.activityid"=>$activityid,'o.status'=>1);

		$list = $activityrelationmodel->alias("ar")->join("left join sj_org as o on ar.orgid=o.id")
			->field("o.*,ar.dis_price")->where($map)->select();
		foreach($list as $k=>$v){
			$v['thumb']=$this->DoUrlHandle($v['thumb']);
			$list[$k]=$v;
		}
		return $list;
	}

	public function moment(){
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;
        }else{
            $user['id'] = 0;
        }
	    $category = D('MomentCategory')->getByTag('jigou');
	    $children = D('MomentCategory')->children($category['id']);
	    if( !$children ){
	        $data = [
	            'category' =>$children,
                'moment'=> []
            ];
	        return $data;
        }
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;
	    $moment = D('Moment', 'Service')->categoryList($children[0]['id'], $user['id'], $offset, $row);

        $count = $moment['total'];
        $totalpage = ceil($count / $row);
        $this->SetPaginationHeader($totalpage, $count, $page, $row);
        $data = [
            'category' =>$children,
            'moment'=> $moment['rows'],
        ];
        return $data;
    }

    /**
     * Notes: V3机构管理
     * User: dede
     * Date: 2020/3/26
     * Time: 11:37 上午
     * @return array
     */
    public function org(){
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;
        }else{
            $user['id'] = 0;
        }

        $orgmodel = D("org");
        $map = array("status"=>1);
        $order = "top desc, ordernum asc";
        $list = $orgmodel->where($map)->order($order)->limit(4)->select();

        foreach($list as $k=>$v){
            $v["thumb"] = $this->DoUrlHandle($v["thumb"]);
            $v["images"] = $this->DoUrlListHandle($v["images"]);

            $list[$k] = $v;
        }

        $category = D('MomentCategory')->getByTag('jigou');
        $children = D('MomentCategory')->children($category['id']);
        $moment = D('Moment', 'Service')->categoryList($children[0]['id'], $user['id'], 0, 10);

        $nav_id = 15;
        $banner = D('UnivNavBanner')->nav($nav_id);
        $data = [
            'org' => $list,
            'category' => $children,
            'moment' => $moment['rows'],
            'brand' => $banner,
        ];
        return $data;
    }
}