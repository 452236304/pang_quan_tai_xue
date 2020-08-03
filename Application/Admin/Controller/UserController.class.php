<?php
namespace Admin\Controller;
use Think\Controller;
class UserController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $role = I("get.role", 0);
        $order = "registertime desc";
        $param = $this->getMap();
        $map = array("r.role"=>$role);
        if($param["keyword"]){
            $where["u.mobile"] = array("like","%".$param["keyword"]."%");
            $where["u.nickname"] = array("like","%".$param["keyword"]."%");
            $where["_logic"] = "or";
            $map["_complex"] = $where;
        }
        $count = D("user")->alias("u")->join("left join sj_user_role as r on u.id=r.userid")->where($map)->count();
        $model = D("user")->alias("u")->join("left join sj_user_role as r on u.id=r.userid")->join('left join sj_user_profile up on u.id=up.userid')->field("u.*,up.status upstatus,up.be_referral_code up_be_referral_code");
        $data = $this->pager(array("mo"=>$model, "count"=>$count), "10", $order, $map);
		foreach($data['data'] as $k=>$v){
			switch($v['upstatus']){
				case 0:
					$v['upstatus']='禁用';
					break;
				case 1:
					$v['upstatus']='审核通过';
					break;
				case 2:
					$v['upstatus']='待审核';
					break;
				default:
					$v['upstatus']='禁用';
			}
			if($v['be_referral_code'] || $v['up_be_referral_code']){
				if($param['role']==1){
					$parent_info=D('user')->field('nickname')->where('referral_code="'.$v['be_referral_code'].'"')->find();
					$v['parentname']=$parent_info['nickname'];
				}else{
					$parent_info=D('user_profile')->alias('up')->where('up.referral_code="'.$v['up_be_referral_code'].'"')->join('sj_user u on up.userid=u.id')->field('u.nickname')->find();
					$v['parentname']=$parent_info['nickname'];
				}
			}else{
				$v['parentname']='无上级';
			}
			
			$data['data'][$k]=$v;
		}
		switch($param['role']){
			case 1:
				$role_name='用户';
				break;
			case 2:
				$role_name='送餐员';
				break;
			case 3:
				$role_name='家护师';
				break;
			case 4:
				$role_name='康复师';
				break;
			case 5:
				$role_name='医生';
				break;
			case 6:
				$role_name='护士';
				break;
		}
		
		$this->assign('role_name',$role_name);
        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->display();
    }

     /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);

        $param = $this->getMap();

        $role = $param["role"];
        if(!in_array($role, [1,2,3,4,5,6])){
            $this->error("角色类型异常");
        }
    	$doinfo = I("get.doinfo");
        $model = D("user");
        $profilemodel = D("user_profile");
        $info = $model->find($id);

        if($doinfo == "modify"){
            $data = I("post.");
            $u_d["status"] = $data["status"];
            if(empty($u_d["status"])){
                $u_d["status"] = 0;
            }
            $p_d["status"] = $data["role_status"];
            if(empty($p_d["status"])){
                $p_d["status"] = 0;
            }
            $p_d["mobile"] = $u_d["mobile"] = $u_d["account"] = $data["mobile"];
            if(!isMobile($data["mobile"])){
                $this->error("手机号码格式不正确");
            }
            if($id > 0){
                if($info["account"] != $u_d["account"]){
                    $map = array("account"=>$u_d["account"], "id"=>array("neq", $id));
                    $checkmobile = $model->where($map)->find();
                    if($checkmobile){
                        $this->error("当前手机号码已经被其它用户注册");
                    }
                }
            } else{
                $map = array("account"=>$u_d["account"], "mobile"=>$u_d["mobile"], "_logic"=>"or");
                $checkmobile = $model->where($map)->find();
                if($checkmobile){
                    if(in_array($role, [2,3,4,5,6])){
                        $rolemodel = D("user_role");
                        $map = array("userid"=>$checkmobile["id"], "role"=>array("gt", 1));
                        $checkrole = $rolemodel->where($map)->find();
                        if($checkrole){
                            switch ($checkrole["role"]) {
                                case 2: $rolename = "送餐员"; break;
                                case 3: $rolename = "家护师"; break;
                                case 4: $rolename = "康复师"; break;
                                case 5: $rolename = "医生"; break;
                                case 6: $rolename = "护士"; break;
                            }

                            $this->error("当前手机号码已经注册".$rolename."，无法注册其它服务角色");
                        }

                        $id = $checkmobile["id"];
                    } else{
                        $this->error("当前手机号码已经被其它用户注册");
                    }
                }
            }
            $password = $data["password"];
            if($id <= 0 && empty($password)){
                $this->error("请输入登录密码");
            }
            if($password){
                $u_d["password"] = md5(strtolower($password) . C('pwd_key'));
            }
            $u_d["nickname"] = $data["nickname"];
            if (empty($u_d["nickname"])) {
                $u_d["nickname"] = func_substr_replace($u_d["account"], 3, 4);
            }
            $p_d["realname"] = $data["realname"];
            $p_d["idcard"] = $data["idcard"];
            $u_d["avatar"] = $data["avatar"];
            if(empty($u_d["avatar"])){
                $u_d["avatar"] = "/upload/default/default_avatar.png";
            }
            $u_d["gender"] = $p_d["gender"] = $data["gender"];
            $u_d["province"] = $p_d["province"] = $data["province"];
            $u_d["city"] = $p_d["city"] = $data["city"];
            $u_d["region"] = $p_d["region"] = $data["region"];
            $u_d["address"] = $p_d["address"] = $data["address"];
            $u_d["sign"] = $data["sign"];
            $u_d["interest"] = $data["interest"];
            $p_d["resid"] = $data["resid"];
            if(empty($p_d["resid"])){
                $p_d["resid"] = 0;
            }
            $p_d["birth"] = $data["birth"];
            $p_d["height"] = $data["height"];
            if(empty($p_d["height"])){
                $p_d["height"] = 0;
            }
            $p_d["weight"] = $data["weight"];
            if(empty($p_d["weight"])){
                $p_d["weight"] = 0;
            }
            $p_d["major_level"] = $data["major_level"];
            if(empty($p_d["major_level"])){
                $p_d["major_level"] = 0;
            }
            $p_d["service_level"] = $data["service_level"];
            if(empty($p_d["service_level"])){
                $p_d["service_level"] = 0;
            }
            $p_d["work_year"] = $data["work_year"];
            if(empty($p_d["work_year"])){
                $p_d["work_year"] = 0;
            }
            $p_d["education"] = $data["education"];
            if(empty($p_d["education"])){
                $p_d["education"] = 0;
            }
            $p_d["language"] = $data["language"];
			$p_d['address'] = $data['address'];
            $p_d["major"] = $data["major"];
            $p_d["intro"] = $data["intro"];
            $p_d["comment_percent"] = $data["comment_percent"];
            if(empty($p_d["comment_percent"])){
                $p_d["comment_percent"] = 0;
            }
            $p_d["top"] = $data["top"];
            if(empty($p_d["top"])){
                $p_d["top"] = 0;
            }
            $p_d["recommend"] = $data["recommend"];
            if(empty($p_d["recommend"])){
                $p_d["recommend"] = 0;
            }
            $p_d["sign_image"] = $data["sign_image"];
            $p_d["test_image"] = $data["test_image"];
			$p_d["idcard_image"] = $data["idcard_image"];
            $p_d["face"] = $data["face"];
            $p_d["idcard_image"] = $data["idcard_image"];

            if(empty($u_d["province"]) || empty($u_d["city"]) || empty($u_d["region"])){
                $this->error("请选择省市区");
            }

            if($id <= 0 || empty($info["referral_code"])){
                //邀请码
                while(true){
                    $u_code = random(10, "all");
                    $check = $model->where(array('referral_code'=>$u_code))->find();
                    if(empty($check)){
                        $u_d["referral_code"] = $u_code; break;
                    }
                }
            }

            if(in_array($role, [2,3,4,5,6])){
                if($id <= 0){
                    //邀请码
                    while(true){
                        $p_code = random(10, "all");
                        $check = $profilemodel->where(array('referral_code'=>$p_code))->find();
                        if(empty($check)){
                            $p_d["referral_code"] = $u_cp_codeode; break;
                        }
                    }

                    if($role == 2){ //送餐员
                        if(empty($p_d["resid"])){
                            $this->error("请选择所属餐厅");
                        }
                    }
                    if(empty($p_d["major_level"])){
                        $this->error("请选择专业等级");
                    }
                    if(empty($p_d["service_level"])){
                        $this->error("请选择服务等级");
                    }
                    if(empty($p_d["education"])){
                        $this->error("请选择学历");
                    }
                    if(empty($p_d["sign_image"])){
                        $this->error("签名照片不能为空");
                    }
                    if(empty($p_d["test_image"])){
                        $this->error("体检照片不能为空");
                    }
                    if(empty($p_d["face"])){
                        $this->error("人脸识别照片不能为空");
                    }
                    if(empty($p_d["idcard_image"])){
                        $this->error("身份证正反照片不能为空");
                    }
                } else{
                    $map = array("userid"=>$id);
                    $checkprofile = $profilemodel->where($map)->find();
                    if(empty($checkprofile) || ($checkprofile && empty($checkprofile["referral_code"]))){
                        //邀请码
                        while(true){
                            $p_code = random(10, "all");
                            $check = $profilemodel->where(array('referral_code'=>$p_code))->find();
                            if(empty($check)){
                                $p_d["referral_code"] = $p_code; break;
                            }
                        }
                    }
                }
            }

            if($id > 0){
                $map = array("id"=>$id);
                $model->where($map)->save($u_d);

                if (in_array($role, [2,3,4,5,6])) {
                    $map = array("userid"=>$id);
                    $checkprofile = $profilemodel->where($map)->find();
                    if(empty($checkprofile)){
                        $p_d["userid"] = $id;
                        $p_d["role"] = $role;
                        $p_d["service_level_update_time"] = date("Y-m-d H:i");
                        $p_d["service_level_check_time"] = date("Y-m-d H:i");
                        $p_d["updatetime"] = date("Y-m-d H:i");
                        $profilemodel->add($p_d);
                    } else{
                        $map = array("userid"=>$id);
                        $profilemodel->where($map)->save($p_d);
                    }

                    //绑定用户角色
                    $this->BindUserRole($id, $role);
                }

            } else{
				$u_d['registertime']=date('Y-m-d H:i:s');
				$u_d['updatetime']=date('Y-m-d H:i:s');
				$u_d['logintime']=date('Y-m-d H:i:s');
                $userid = $model->add($u_d);

                $p_d["userid"] = $userid;
                $p_d["role"] = $role;
                $p_d["service_level_update_time"] = date("Y-m-d H:i");
                $p_d["service_level_check_time"] = date("Y-m-d H:i");
                $profilemodel->add($p_d);

                //绑定用户角色
                $this->BindUserRole($userid, 1);
                if (in_array($role, [2,3,4,5,6])) {
                    $this->BindUserRole($userid, $role);
                }
            }
            
            $this->redirect("User/listad", $param);
        }

        if($id > 0 && $role > 1){
            $map = array("userid"=>$info["id"]);
            $profile = $profilemodel->where($map)->find();

            $info["role_status"] = $profile["status"];
            $info["mobile"] = $profile["mobile"];
            $info["realname"] = $profile["realname"];
            $info["idcard"] = $profile["idcard"];
            $info["gender"] = $profile["gender"];
            $info["province"] = $profile["province"];
            $info["city"] = $profile["city"];
            $info["region"] = $profile["region"];
            $info["address"] = $profile["address"];
            $info["resid"] = $profile["resid"];
            $info["birth"] = $profile["birth"];
            $info["height"] = $profile["height"];
            $info["weight"] = $profile["weight"];
            $info["major_level"] = $profile["major_level"];
            $info["service_level"] = $profile["service_level"];
            $info["work_year"] = $profile["work_year"];
            $info["education"] = $profile["education"];
            $info["language"] = $profile["language"];
            $info["major"] = $profile["major"];
            $info["intro"] = $profile["intro"];
            $info["comment_percent"] = $profile["comment_percent"];
            $info["top"] = $profile["top"];
            $info["recommend"] = $profile["recommend"];
            $info["sign_image"] = $profile["sign_image"];
            $info["test_image"] = $profile["test_image"];
			$info["idcard_image"] = $profile["idcard_image"];
            $info["face"] = $profile["face"];
            $info["idcard_image"] = $profile["idcard_image"];
            $info["referral_code"] = $profile["referral_code"];
            $info["be_referral_code"] = $profile["be_referral_code"];
        }

        if ($role == 2) {
            //餐厅列表
            $restaurant = D("restaurant")->select();
            $this->assign("restaurant", $restaurant);
        }
		if($id > 0){
			$map = array('userid'=>$id,'type'=>0,'status'=>1);
			$accept_address=D('user_address')->where($map)->select();
			$info['accept_address']='';
			foreach($accept_address as $k=>$v){
				$info['accept_address'].="收货人:{$v['consignee']},手机号:{$v['mobile']},收货地址:{$v['province']}{$v['city']}{$v['region']}{$v['address']};\n";
			}
		}
        $this->assign("info", $info);
        $this->assign("map", $param);
    	$this->show();
    }

    public function accountchange(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $user_model = D("user");
        $user = $user_model->find($id);
        if(empty($user)){
            alert('用户不存在，操作失败');
        }
        $data['info'] = $user;

        if($doinfo == 'change'){
            
            $d['userid'] = $id;
            $d['type'] = I('post.type', 0);
            $d['amount'] = I('post.amount', 0);
            $d['remark'] = I('post.remark');

            if($d['type'] == 1){
                $user['user_money'] += $d['amount'];
            } else if($d['type'] == -1){
                $user['user_money'] -= $d['amount'];
            }
            $user_model->where('id='.$id)->save($user);

            //记录账户变动日志
            $user_account_log_model = D('user_account_log');

            $d['user_money'] = $user['user_money'];
            $d['createdate'] = date("Y-m-d H:i:s");
            $d['adminid'] = $_SESSION["manID"];
            $d['admin'] = $_SESSION['username'];
            $user_account_log_model->add($d);

            $this->redirect("User/listad", $this->getMap());
        }

        $this->assign($data);
        $this->assign("map", $this->getMap());
    	$this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("user");
    	$userrolemodel = D("user_role");
    	$id = I("get.id");
    	$role = I("get.role");
        if ($role == 1) {
            $model->delete($id);
            //如果是删除用户 则删除对应的角色
            $userrolemodel->where('userid='.$id)->delete();
        }else{
            $userrolemodel->where(array('userid'=>$id,'role'=>$role))->delete();
        }
    	$this->redirect("User/listad", $this->getMap());
    }

    /**爽约用户
     * [listad]
     * @return [type] [description]
     */
    public function breaklistad(){
        $order = "up.plane_time desc";
        $param = $this->getMap();

        $time = date("Y-m-d H:i:s", strtotime("-3 month", time()));
        $map = array(
            'r.role'=>array('gt',1),
            'up.plane_time'=>array(
                array("exp", "is not null"),
                array("egt", $time),
                "and"
            )
        );
        if($param["keyword"]){
            $where["u.mobile"] = array("like","%".$param["keyword"]."%");
            $where["u.nickname"] = array("like","%".$param["keyword"]."%");
            $where["_logic"] = "or";
            $map["_complex"] = $where;
        }
        $count = D("user")->alias("u")->join("left join sj_user_role as r on u.id=r.userid")->join("left join sj_user_profile as up on u.id=up.userid")->group('u.id')->where($map)->getField('u.id',true);
        $count = count($count);

        $model = D("user")->alias("u")->join("left join sj_user_role as r on u.id=r.userid")->join("left join sj_user_profile as up on u.id=up.userid")->field("u.*,up.plane_time,r.role")->group('u.id');
        $data = $this->pager(array("mo"=>$model, "count"=>$count), "10", $order, $map);

        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->show();
    }
    
    public function RemoveBreak(){
        $id = I("get.id", 0);

        $user = D("user")->find($id);
        if(empty($user)){
            alert_back("解绑爽约用户不存在，操作失败");
        }

        $entity = array("plane_time"=>'1970-01-01 08:00:00');
        D("user_profile")->where("userid=".$id)->save($entity);

    	alert("用户解绑爽约成功", U("User/breaklistad", $this->getMap()));
    }

	//邀请海报
	public function invitation(){
		$doinfo = I("get.doinfo");
		$id=I('get.id');
		$this->assign('id',$id);
		$model=D('user_invitation');
		if($doinfo == "modify"){
            $d['images']=I('post.images');
            $d['bg_color'] = I("post.bg_color");
            $d['word_color'] = I("post.word_color");
			$d['title'] = I("post.title");
			$d['share_title'] = I("post.share_title");
			$d['content'] = htmlspecialchars_decode(I("post.content"));
			$model->where('id='.$id)->save($d);
		}
		$data['info']=$model->where('id='.$id)->find();
		$this->assign($data);
		$this->display();
    }
    
    //邀请用户列表
    public function referral(){
        $code = I("get.code");
        if(empty($code)){
            $this->error("请选择查看邀请列表的用户");
        }
        
        $order = "registertime desc";
        $map = array("be_referral_code"=>$code);
        $data = $this->pager("user", "10", $order, $map);

        $this->assign($data);
        $this->show();
    }

    public function getMap(){
        $p = I("get.p");
        $role = I("get.role");
        $status = I("get.status");
        $keyword = I("post.keyword");
        if(empty($keyword)){
            $keyword = mb_convert_encoding($_GET["keyword"], "UTF-8", "gb2312");
        }
        $map = array("p"=>$p, "keyword"=>$keyword, "role"=>$role, "status"=>$status);
        return $map;
    }

    /**
     * Notes: 分佣团队查询
     * User: dede
     * Date: 2020/6/12
     * Time: 4:30 下午
     */
    public function team(){
        $user_id = I('user_id', 0, 'intval');
        $user = D('User')->find($user_id);
        if( !$user ){
            $this->error("请选择查看分佣层级的用户");
        }

        $where = [
            'u.id' => $user['team_parent']
        ];
        // 上级
        $parent = $this->listFormat($where);
        $this->assign('parent', $parent);

        $where = [
            'u.id' => $parent[0]['team_parent']
        ];
        // 上上级
        $grandpa = $this->listFormat($where);
        $this->assign('grandpa', $grandpa);

        $where = [
            'u.team_parent' => $user_id
        ];
        // 下级
        $children = $this->listFormat($where);
        foreach ( $children as &$item ){
            $where = [
                'u.team_parent' => $item['id']
            ];
            // 下下级
            $item['children'] = $this->listFormat($where);
        }
        $this->assign('children', $children);
        $this->display();
    }


    /**
     * Notes: 列表数据格式化
     * User: dede
     * Date: 2020/6/12
     * Time: 4:31 下午
     * @param $where
     * @return mixed
     */
    public function listFormat($where){
        $data = D("user")->alias("u")
            ->join("left join sj_user_role as r on u.id=r.userid")
            ->join('left join sj_user_profile up on u.id=up.userid')
            ->where($where)
            ->field("u.*,up.status upstatus,up.be_referral_code up_be_referral_code")
            ->select();
        foreach($data as &$v){
            switch($v['upstatus']){
                case 0:
                    $v['upstatus']='禁用';
                    break;
                case 1:
                    $v['upstatus']='审核通过';
                    break;
                case 2:
                    $v['upstatus']='待审核';
                    break;
                default:
                    $v['upstatus']='禁用';
            }
        }
        return $data;
    }
}