<?php
namespace CApi\Controller;
use Think\Controller;
class ServiceProjectController extends BaseController {
	
	//首页
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
		
		return $detail;
	}

	public function servicedetail(){
		$type=I('get.type');
		$map['id']=$type;
		$detail=D('service_detail')->where($map)->find();
		$detail["content"] = $this->UEditorUrlReplace($detail["content"]);
		return $detail;
	}

    /**
     * Notes: V3 服务-上门照护
     * User: dede
     * Date: 2020/3/2
     * Time: 2:44 下午
     */
	public function listad(){
	    $category = D('ServiceProject', 'Service')->project();

        $nav_id = 11;
        $banner = D('UnivNavBanner')->nav($nav_id);
        $data = [
            'category' => $category,
            'banner' => $banner
        ];
        return $data;
    }

    /**
     * Notes: V3 推荐
     * User: dede
     * Date: 2020/3/26
     * Time: 10:24 上午
     */
    public function recommend(){
        //顶部广告图
        $bannermodel = D("banner");
        $map = array("status"=>1, "type"=>20);
        $banner = $bannermodel->where($map)->order("ordernum asc")->select();
        foreach ($banner as $k=>$v) {
            $v["image"] = $this->DoUrlHandle($v["image"]);
            
            if($v["param"]){
                $v["param"] = json_decode($v["param"], true);
            } else{
                $v["param"] = array("param_type"=>"-1", "param_id"=>"");
            }

            $banner[$k] = $v;
        }
        // 限时秒杀
        $seckill = D('Product', 'Service')->seckill();
        // 好物推荐
        $discounts = D('Product', 'Service')->discounts();

        // 明星家护师
        $page = I("get.page", 1);
        $row = I("get.row", 3);
        $begin = ($page-1)*$row;

        //剔除爽约
        $plane_time = date("Y-m-d H:i:s", strtotime("-3 month", time()));
		$map = array();
        $map["up.plane_time"] = array(
            array("exp", "is null"),
            array("lt", $plane_time),
            "or"
        );

        $order = "up.recommend desc, up.top desc, up.comment_percent desc";
        //好评度
        $order = "up.comment_percent desc,".$order;

        $usermodel = D("user");
        $count = $usermodel->alias("u")->join("left join sj_user_role as ur on u.id=ur.userid")->join("left join sj_user_profile as up on u.id=up.userid")->where($map)->group('u.id')->count();
        $totalpage = ceil($count/$row);
        $list = $usermodel->alias("u")->join("left join sj_user_role as ur on u.id=ur.userid")->join("left join sj_user_profile as up on u.id=up.userid")
            ->field("u.id,ur.role,u.avatar,up.realname,up.gender,up.birth,up.mobile,up.major_level,up.service_level,up.work_year,up.education,up.major,up.language,up.comment_percent")
            ->where($map)->group('u.id')->order($order)->limit($begin, $row)->select();

        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        foreach ($list as $k=>$v) {
            $v["avatar"] = $this->DoUrlHandle($v["avatar"]);

            switch($v['gender']){
                case 1:
                    $v['gender']='男';
                    break;
                case 2:
                    $v['gender']='女';
                    break;
                case 0:
                    $v['gender']='保密';
                    break;
            }

            //年龄
            $v["age"] = getAgeMonth($v["birth"]);

            $list[$k] = $v;
        }

        $nav_id = 10;
        $banner = D('UnivNavBanner')->nav($nav_id);
        $data = [
            'seckill'=> $seckill['rows'],
            'discounts' => $discounts['rows'],
            'list' => $list,
            'banner' => $banner,


        ];
        return $data;
    }

    /**
     * Notes: V3健康管理
     * User: dede
     * Date: 2020/3/26
     * Time: 10:39 上午
     * @return mixed
     */
    public function healthy(){
        $role = I("get.role", 0);
        $category_id = I('category_id', 0 , 'intval');
        $type=1;
        $model = D("service_category");
        $map = array("type"=>$type);
        if(in_array($role, [3,4,5])){
            $map["role"] = $role;
        }
        $list = $model->where($map)->order("ordernum asc")->select();
        foreach ($list as $k=>$v) {
            $v["thumb"] = $this->DoUrlHandle($v["thumb"]);
            if( !$k && !$category_id ){
                $category_id = $v['id'];
            }
            $list[$k] = $v;
        }
        $projectmodel = D("service_project");
        $where=array('categoryid'=>$category_id);
        $plist=$projectmodel->where($where)->select();
        foreach($plist as $key=>$value){
            $value["thumb"] = $this->DoUrlHandle($value["thumb"]);
            $plist[$key]=$value;
        }

        $nav_id = 14;
        $banner = D('UnivNavBanner')->nav($nav_id);
        $data = [
            'category' => $list,
            'service' => $plist,
            'brand' => $banner
        ];

        return $data;
    }

    public function search(){
        $type = I('type', 0 ,'intval');
        $keyword = I('keyword');
        $category_id = I('category_id', 0, 'intval');
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;
        $data = [];
        if( $type == 1 ){
            $data = D('User', 'Service')->searchServiceUser($keyword, $begin, $row);
            foreach ( $data['rows'] as &$item ){
                $item['type'] = 1;
            }
        }else if( $type == 2 ){
            $where = [ 'p.title' => ['Like', '%'.$keyword.'%']];
            $data = D('Product', 'Service')->search($where, $begin, $row);
            foreach ( $data['rows'] as &$item ){
                $item['type'] = 2;
            }
        }else if( $type == 3 ){
            $where = [ 'title' => ['Like', '%'.$keyword.'%']];
            if( $category_id ){
                $where['categoryid'] = $category_id;
            }
            $data = D('ServiceProject', 'Service')->search($where, $begin, $row);
            foreach ( $data['rows'] as &$item ){
                $item['type'] = 3;
            }
            $category = D('ServiceCategory')->serviceCategory();
            $data['category'] = $category;
        }else{
            $user = D('User', 'Service')->searchServiceUser($keyword);
            foreach ( $user['rows'] as &$item ){
                $item['type'] = 1;
            }
            if( $user['rows'] ){
                $data['rows'] = $user['rows'];
            }
            $where = [ 'p.title' => ['Like', '%'.$keyword.'%']];
            $product = D('Product', 'Service')->search($where);
            foreach ( $product['rows'] as &$item ){
                $item['type'] = 2;
            }
            if( $data['rows'] ){
                $data['rows'] = array_merge($data['rows'], $product['rows']);
            }else{
                $data['rows'] = $product['rows'];
            }
            $where = [ 'title' => ['Like', '%'.$keyword.'%']];
            $service = D('ServiceProject', 'Service')->search($where);
            foreach ( $service['rows'] as &$item ){
                $item['type'] = 3;
            }
            if( $data['rows'] ){
                $data['rows'] = array_merge($data['rows'], $service['rows']);
            }else{
                $data['rows'] = $service['rows'];
            }
        }
        if( $data['total'] ){
            $count = $data['total'];
            $totalpage = ceil($count/$row);
            $this->SetPaginationHeader($totalpage, $count, $page, $row);
        }
        return $data;
    }
}