<?php
namespace CApi\Controller;
use Think\Controller;
class ServiceUserController extends BaseController {
	
	//家护师列表
	public function lists(){
		$data = I("get.");
		$map = array("ur.role"=>3, "up.status"=>1);

		//服务项目
		$categoryid = $data["categoryid"];
		if($categoryid){
			$relationmodel = D("user_project_relation");

			$where = array("type"=>1, "projectid"=>$categoryid);
			$relations = $relationmodel->where($where)->select();
			foreach($relations as $k=>$v){
				$userids[] = $v["userid"];
			}
			if(count($userids) <= 0){
				return [];
			}

			$map["u.id"] = array("in", $userids);
		}
		//护理年限
		$year = $data["year"];
		if($year){
			if(!in_array($year, [1,2,3,4])){
				E("请选择正确的护理年限");
			}

			switch ($year) {
				case 1: // 2年以下
					$map["up.work_year"] = array("lt", 2);
					break;
				case 2: // 2-5年
					$map["up.work_year"] = array("exp", "between 2 and 5");
					break;
				case 3: // 5-10年
					$map["up.work_year"] = array("exp", "between 5 and 10");
					break;
				case 4: // 10以上
					$map["up.work_year"] = array("gt", "10");
					break;
			}
		}
		//性别
		$gender = $data["gender"];
		if($gender){
			if(!in_array($gender, ["男", "女"])){
				E("请选择正确的性别筛选");
			}
			if($gender=='男'){
				$gender=1;
			}else{
				$gender=2;
			}
			$map["up.gender"] = $gender;
		}
		//年龄
		$age = $data["age"];
		if($age){
			if(!in_array($age, [1,2,3,4])){
				E("请选择正确的年龄筛选");
			}
			$time = strtotime(date("Y-m-d", time()));
			switch($age){
				case 1: //25-30岁
					$btime = date("Y-m-d", strtotime("-30 year", $time));
					$etime = date("Y-m-d", strtotime("-25 year", $time));
					$map["up.birth"] = array("exp", "between '".$btime."' and '".$etime."'");
					break;
				case 2: //30-40岁
					$btime = date("Y-m-d", strtotime("-40 year", $time));
					$etime = date("Y-m-d", strtotime("-30 year", $time));
					$map["up.birth"] = array("exp", "between '".$btime."' and '".$etime."'");
					break;
				case 3: //40-50岁
					$btime = date("Y-m-d", strtotime("-50 year", $time));
					$etime = date("Y-m-d", strtotime("-40 year", $time));
					$map["up.birth"] = array("exp", "between '".$btime."' and '".$etime."'");
					break;
				case 4: //50-60岁
					$btime = date("Y-m-d", strtotime("-60 year", $time));
					$etime = date("Y-m-d", strtotime("-50 year", $time));
					$map["up.birth"] = array("exp", "between '".$btime."' and '".$etime."'");
					break;
			}
		}
		//学历
		$education = $data["education"];
		if($education){
			$map["up.education"] = $education;
		}
		//语言
		$language = $data["language"];
		if($language=='其他'){
			$map["up.language"] = array("not in", ['普通话','粤语','英语']);
		}elseif($language){
			$map["up.language"] = array("like", "%".$language."%");
		}
		//姓名
		$keyword = $data["keyword"];
		if($keyword){
			$map["up.realname"] = array("like", "%".$keyword."%");
		}
		
		$order = "up.recommend desc, up.top desc, up.comment_percent desc";
		//好评度
		$comment = $data["comment"];
		if($comment == 1){
			$order = "up.comment_percent desc,".$order;
		}elseif($comment == 2){
			$order = "up.comment_percent asc,".$order;
		}
		
		$page = I("get.page", 1);
		$row = I("get.row", 10);
		$begin = ($page-1)*$row;
		
		//区
		$area=I("get.area");
		if($area){
			$map["up.region"]=array('like','%'.$area.'%');
		}

		//剔除爽约
		$plane_time = date("Y-m-d H:i:s", strtotime("-3 month", time()));
		$map["up.plane_time"] = array(
			array("exp", "is null"),
			array("lt", $plane_time),
			"or"
		);
		
		//星选家护师
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
			$v["age"] = getAge($v["birth"]).'岁';

			$list[$k] = $v;
		}
		return $list;
	}
	
	//服务人员详情
	public function detail(){
		$id = I("get.id");
		
		$usermodel = D("user_role");
		$map = array("ur.role"=>array('in','3,4'), "up.status"=>1, "u.status"=>200, "u.id"=>$id);
		$user = $usermodel->alias("ur")->join("left join sj_user as u on ur.userid=u.id")->
			join("left join sj_user_profile as up on ur.userid=up.userid")->field("u.*,ur.role roles")->where($map)->find();
		if(empty($user)){
			E("服务人员不存在");
		}

		//附加信息
		$profilemodel = D("user_profile");
		$map = array("userid"=>$user["id"]);
		$profile = $profilemodel->where($map)->find();
		if(empty($profile)){
			E("服务人员不存在");
		}
		
		switch($profile["gender"]){
			case 2:
				$profile["gender"]='女';
				break;
			case 1:
				$profile["gender"]='男';
				break;
			case 0:
				$profile["gender"]='保密';
				break;
		}
		
		$detail = array(
			"id"=>$user["id"], "nickname"=>$user["nickname"], "avatar"=>$this->DoUrlHandle($user["avatar"]),
			"realname"=>$profile["realname"], "gender"=>$profile["gender"], "birth"=>$profile["birth"], "age"=>getAge($profile["birth"]).'岁',
			"mobile"=>$profile["mobile"], "height"=>$profile["height"], "weight"=>$profile["weight"],
			"major_level"=>$profile["major_level"], "service_level"=>$profile["service_level"], "work_year"=>$profile["work_year"],
			"education"=>$profile["education"], "major"=>$profile["major"], "language"=>$profile["language"], "comment_percent"=>$profile["comment_percent"],
			"province"=>$profile["province"], "city"=>$profile["city"], "region"=>$profile["region"], "intro"=>$profile["intro"], "papers"=>array(), "is_sc"=>0,'role'=>$user['roles']
		);
		
		$papersmodel = D("user_papers");
		$map = array("status"=>1, "userid"=>$user["id"]);
		$papers = $papersmodel->WHERE($map)->group('type')->order('updatetime desc')->select();
		foreach($papers as $k=>$v){
			$item = array(
				"name"=>$v["name"], "images"=>$this->DoUrlListHandle($v["images"]),
				"begintime"=>$v["begintime"], "endtime"=>$v["validtime"],'type'=>$v['type']
			);
			
			$detail["papers"][] = $item;
		}
		//是否收藏
        if ($this->UserAuthCheckLogin()) {
            $yhuser = $this->AuthUserInfo;
            $map = array('userid'=>$yhuser['id'],'source'=>3,'type'=>1,'objectid'=>$user['id']);
            $sc = D('user_record')->where($map)->find();
            if ($sc) {
                $detail['is_sc'] = 1;
            }

        }
		return $detail;
	}
	
	//服务人员评价列表
	public function comment(){
		$serviceid = I("get.serviceid", 0);
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;

        $model = D("service_comment");
        
        $order = "createdate desc";
        $map = array("status"=>1, "service_userid"=>$serviceid);
        $count = $model->where($map)->count();
        $totalpage = ceil($count/$row);
        $list = $model->where($map)->limit($begin, $row)->order($order)->select();
		
		$this->SetPaginationHeader($totalpage, $count, $page, $row);

		foreach($list as $k=>$v){
			$map = array('id'=>$v['userid']);
			$avatar=D('user')->field('avatar')->where($map)->find();
			if($avatar){
				$v["avatar"] = $this->DoUrlHandle($avatar["avatar"]);
			}else{
				$v["avatar"] = $this->DoUrlHandle('/upload/default/default_avatar.png');
			}
			
			$v["service_avatar"] = $this->DoUrlHandle($v["service_avatar"]);
			$v["images"] = $this->DoUrlListHandle($v["images"]);

            $v["star"] = $this->calcstar($v["score"]);
			$map = array('id'=>$v['orderid']);
			$v['order']=D('service_order')->where($map)->find();
			if($v['order']){
				$list[$k] = $v;
			}else{
				unset($list[$k]);
			}
		}

		return $list;
	}

	//预约服务人员获取项目列表
    public function serviceProject(){
        $serviceuserid = I('get.serviceuserid',0);
        if (empty($serviceuserid)) {
            E('请选择服务人员');
        }
        $model = D('user_project_relation');
        $map = array('userid'=>$serviceuserid, 'type'=>2);
        $projectid = $model->where($map)->getField('projectid',true);
        if (empty($projectid)) {
            return;
        }

        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;

        $model = D('service_project');

        $order = "sp.top desc, sp.ordernum asc, sp.sales desc, sp.browser_count desc";

        //需要关联了服务项目星级价格才显示
        $map = array("sp.status"=>1, "sp.id"=>array('in',$projectid), "lp.status"=>1);


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
            $v["price"] = $v["price"];
			$v['type'] = 1;
            $list[$k] = $v;
        }

        return $list;
    }

    //服务人员更多信息
    public function more(){
        $id = I("get.id");

        $usermodel = D("user_role");
        $map = array("ur.role"=>array('in','3,4'), "up.status"=>1, "u.status"=>200, "u.id"=>$id);
        $user = $usermodel->alias("ur")->join("left join sj_user as u on ur.userid=u.id")->
        join("left join sj_user_profile as up on ur.userid=up.userid")->field("u.*")->order('role desc')->where($map)->find();
        if(empty($user)){
            E("服务人员不存在");
        }

        //附加信息
        $profilemodel = D("user_profile");
        $map = array("userid"=>$user["id"]);
        $profile = $profilemodel->where($map)->find();
        if(empty($profile)){
            E("服务人员不存在");
        }
		switch($profile["gender"]){
			case 2:
				$profile["gender"]='女';
				break;
			case 1:
				$profile["gender"]='男';
				break;
			case 0:
				$profile["gender"]='保密';
				break;
		}
        $detail = array(
            "id"=>$user["id"], "nickname"=>$user["nickname"], "avatar"=>$this->DoUrlHandle($user["avatar"]),
            "realname"=>$profile["realname"], "gender"=>$profile["gender"], "birth"=>$profile["birth"], "age"=>getAge($profile["birth"]).'岁',
            "mobile"=>$profile["mobile"], "height"=>$profile["height"], "weight"=>$profile["weight"],
            "major_level"=>$profile["major_level"], "service_level"=>$profile["service_level"], "work_year"=>$profile["work_year"],
            "education"=>$profile["education"], "major"=>$profile["major"], "language"=>$profile["language"], "comment_percent"=>$profile["comment_percent"],
            "province"=>$profile["province"], "city"=>$profile["city"], "region"=>$profile["region"], "intro"=>$profile["intro"], "papers"=>array(),'role'=>$user['role'],'address'=>$profile['address']
        );
		
        $papersmodel = D("user_papers");
        $map = array("status"=>1, "userid"=>$user["id"], 'type'=>array('in',[1,2,4]));
        $papers = $papersmodel->WHERE($map)->order('type ASC')->select();
        foreach($papers as $k=>$v){
            $item = array(
                "name"=>$v["name"], "images"=>$this->DoUrlListHandle($v["images"]),
                "begintime"=>$v["begintime"], "endtime"=>$v["validtime"]
            );

            $detail["papers"][] = $item;
        }
        return $detail;
    }


    public function index(){
	    // 晓椿到家
        $go_home = D('ServerUserVideo')->order('sort')->limit(3)->select();

	    // 明星家护师
        $page = I("get.page", 1);
        $row = I("get.row", 3);
        $begin = ($page-1)*$row;

        //剔除爽约
        $plane_time = date("Y-m-d H:i:s", strtotime("-3 month", time()));
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
            $v["age"] = getAge($v["birth"]).'岁';

            $list[$k] = $v;
        }

        $nav_id = 13;
        $banner = D('UnivNavBanner')->nav($nav_id);

        $data = [
            'go_home' => $go_home,
            'star' => $list,
            'banner' => $banner
        ];
        return $data;
    }


    /**
     * Notes: 服务人员详情
     * User: dede
     * Date: 2020/3/17
     * Time: 6:02 下午
     * @return array
     * @throws \Think\Exception
     */
    public function project(){
        $id = I("post.id");
        $page = I("get.page", 1);
        $row = I("get.row", 3);
        $offset = ($page-1)*$row;
        $where = [
            'SPR.userid' => $id,
        ];
        $data['total'] = D('user_project_relation')->alias('SPR')
            ->join('__SERVICE_PROJECT__ AS SP ON SP.id = SPR.projectid')
            ->where($where)
            ->count();
        $field = 'SP.id, SP.title, subtitle, thumb, price, market_price, time_type, time';
        $data['rows'] = D('user_project_relation')->alias('SPR')
            ->join('__SERVICE_PROJECT__ AS SP ON SP.id = SPR.projectid')
            ->where($where)
            ->field($field)
            ->order('ordernum')
            ->limit($offset, $row)
            ->select();
        foreach ( $data['rows'] as &$item ){
            $item['thumb'] = DoUrlHandle($item['thumb']);
        }
        $count = $data['total'];
        $totalpage = ceil($count / $row);
        $this->SetPaginationHeader($totalpage, $count, $page, $row);
        return ['project' => $data['rows']];
    }
}