<?php
namespace SApi\Controller;
use Think\Controller;
use Think\Exception;
class BaseController extends Controller {

	/* 构造函数 begin */
	protected $CheckUserLogin = false;

	function __construct(){
		parent::__construct();

		$method = ACTION_NAME;
		
		return $this->ApiRequest($method);
	}

	protected function ApiRequest($method){
		
		if(method_exists($this, $method)){
			try{
				if($this->CheckUserLogin){
					$this->UserAuthCheck();
				}
				$result = $this->$method();
				return $this->Json($result);
			} catch(Exception $e){
				return $this->Error($e->getMessage(), $e->getCode());
			}
		}

		return $this->Error("您请求的接口不存在");
	}
	/* 构造函数 end */
    
	/* api 接口 begin */

	protected function Json($data = null){
		if(!is_array($data)){
			$data = array("updatetime"=>date("Y-m-d H:i:s"));
		}
		$this->ajaxReturn($data, "json");
	}

	protected function Error($message, $code = 0){
		header("HTTP/1.1 400 Error");
		$this->ajaxReturn(array("message"=>$message, "code"=>$code), "json");
	}
    
   	/* api 接口 end */
   	
   	/* Http Header begin */
   	
   	protected function SetPaginationHeader($totalpage, $count, $page, $row){
		$pagination = array('totalpage'=>$totalpage, 'count'=>$count, 'more'=>($totalpage>$page?1:0), 'page'=>$page, 'row'=>$row);
		$header = array('pagination'=>json_encode($pagination));
		$this->SetHttpHeader($header);
	}
	
	protected function GetHttpHeader($key){
		return $_SERVER["HTTP_".strtoupper($key)];
	}
	
	protected function SetHttpHeader($array){
		
		foreach($array as $key => $value){
			header($key.": ".$value);
		}
		
	}
	
	/* Http Header end */

	/* 用户相关处理 begin */

	protected $AuthUserInfo;
   	
   	protected function UserAuthCheckLogin(){
   		$authorization = $this->GetHttpHeader("UserAuth");
   		if(empty($authorization)){
   			return false;
   		}
   		
   		$str = _encrypt($authorization, "D");
   		$str_arr = split(",", $str);
   		
   		$this->UserCheckLogin($str_arr[0], $str_arr[1], true, false, $str_arr[2]);
   		
   		return true;
   	}
   	
   	protected function UserAuthCheck(){
   		
   		$authorization = $this->GetHttpHeader("UserAuth");
   		if(empty($authorization)){
   			E("您还未登录，请点击确定前往登录", 21);
   		}
   		
   		$str = _encrypt($authorization, "D");
   		$str_arr = split(",", $str);

		$userid = $str_arr[0];
		$password = $str_arr[1];
		$logintime = $str_arr[2];

		//检查用户是否多端登录
		$this->CheckUserLoginError($userid, $logintime);
			
		$this->UserCheckLogin($userid, $password, true);
   	}
	
	// $logintype：登录方式 0=密码登录，1=非密码登录
	protected function UserCheckLogin($account, $password, $encrypt = false, $login = false, $logintype = 0){
   		
		if(empty($account)){
			E("账号不能为空");
		}
		if(empty($password) && $logintype == 0){
			E("密码不能为空");
		}
		
		if(!$encrypt){
			$password = md5(strtolower($password) . C("pwd_key"));
		}
		
		$model = D("user");
		
		if($logintype == 0){
			$map["password"] = $password;
		}

		if(isMobile($account)){
			$map["account"] = $account;
			
			$checkmobile = $model->where(array("account"=>$account))->find();
			if(empty($checkmobile)){
				E("账号不存在",44);
			}
			
		} else if(is_numeric($account)){
			$map["id"] = $account;	
		} else{
			E("请输入正确的账号");
		}

		$user = $model->where($map)->find();
		if(empty($user)){
			E("账号密码错误");
		}
		if($user["status"] == 300){
			E("账号已经被锁定，请联系客服");
		}
		if($user["status"] == 400){
			E("账号已经被注销，请联系客服");
		}
		if($user["status"] == 500){
			E("账号状态异常，请联系客服");
		}
		if($user["status"] == 0){
			E("账号已经被禁用，请联系客服");
		}
		//检查绑定用户角色
		$this->CheckUserRole($user["id"]);
		
		// 检查header中clientid和system参数信息
		$this->CheckHeaderInfo($user);
		
		if($login){
			$current_time = time();
			if(empty($password)){
				$password = $user["password"];
			}
			//设置用户加密信息返回
			$this->SetUserHeader($user["id"], $password, $current_time);
			
			//设置用户登录缓存
			$this->SetUserLoginCache($user["id"], $current_time);
		}
	 
	 	$user = $this->UserHandle($user);
		
		$this->AuthUserInfo = $user;
		
		return $user;
	}

	//设置用户登录缓存
	private function SetUserLoginCache($userid, $current_time){
		$cache_info = array(
			"userid"=>$userid, "hybrid"=>"system", "time"=>$current_time
		);

		S("user_login_cache_".$userid, json_encode($cache_info), 0);
	}

	//检查用户是否多端登录
	private function CheckUserLoginError($userid, $logintime){
		$cache_info = S("user_login_cache_".$userid);
		if(empty($cache_info)){
			return;
		}
		$cache_info = json_decode($cache_info, true);

		if($cache_info["time"] > $logintime){
			if($cache_info["hybrid"] == "system"){
				E("您需要重新登录,是否前往登录?", 21);
			} else if($cache_info["hybrid"] == "client"){
				E("您的账号已经在一点椿用户端，已被迫下线", 21);
			} else{
				E("您需要重新登录,是否前往登录?", 21);
			}
		}
	}

	//检查用户是否存在服务角色
	protected function CheckUserRole($userid){
        //用户角色表
		$model = D("user_role");
        $map = array("userid"=>$userid, "status"=>1, "role"=>array("in", [2,3,4,5,6]));
        $count = $model->where($map)->count();
        if($count <= 0){
            E("当前用户为非服务人员，登录异常");
		}
	}

    //用户数据处理
    protected function UserHandle($user){
        $entity = array(
            "id"=>$user["id"],"nickname"=>$user["nickname"], "realname"=>$user["realname"], "mobile"=>$user["mobile"],
            "avatar"=>$this->DoUrlHandle($user["avatar"]), "gender"=>$user["gender"],
            "position"=>$user["position"], "work_year"=>$user["work_year"],
            "province"=>$user["province"], "city"=>$user["city"], "region"=>$user["region"],
			"address"=>$user["address"], "sign"=>$user["sign"], "logintime"=>$user["logintime"]
		);

		//用户附加信息
		$profilemodel = D("user_profile");
		$map = array("userid"=>$user["id"]);
		$profile = $profilemodel->where($map)->find();
		if($profile){
			$entity["profile"] = array(
				"realname"=>$profile["realname"], "idcard"=>$profile["idcard"], "gender"=>$profile["gender"],
				"birth"=>$profile["birth"], "mobile"=>$profile["mobile"], "height"=>$profile["height"], "weight"=>$profile["weight"],
				"major_level"=>$profile["major_level"], "service_level"=>$profile["service_level"], "work_year"=>$profile["work_year"],
				"education"=>$profile["education"], "major"=>$profile["major"], "language"=>$profile["language"], "resid"=>$profile["resid"],
				"province"=>$profile["province"], "city"=>$profile["city"], "region"=>$profile["region"], "intro"=>$profile["intro"],
				"service_level_update_time"=>$profile["service_level_update_time"], "service_level_check_time"=>$profile["service_level_check_time"],
				"plane_time"=>$profile["plane_time"], "updatetime"=>$profile["updatetime"], "status"=>$profile["status"],'money'=>$profile['money'],
				"comment_percent"=>$profile["comment_percent"], "face"=>$profile["face"],'status'=>$profile['status']
			);
		}else{
			$entity['profile'] = array();
		}

		//用户角色信息
		$rolemodel = D("user_role");
		$map = array("userid"=>$user["id"]);
		$roles = $rolemodel->where($map)->order('role desc')->select();
		foreach($roles as $k=>$v){
			$entity["role"][] = $v["role"];
		}
		
        return $entity;
    }
	
	//设置用户加密信息返回
	protected function SetUserHeader($userid, $password, $current_time){
		$str = _encrypt($userid.",".$password.",".$current_time, "E");
	 	$authorization = array("uid"=>$userid."|".$current_time, "auth"=>$str, "sid"=>session_id());
	 	$this->SetHttpHeader(array("authsession"=>json_encode($authorization)));
	}
	
	// 检查Header参数信息
	private function CheckHeaderInfo($user){		
		$clientid = $this->GetHttpHeader("clientid");
		if($clientid && $clientid != $user["sclientid"]){
			$entity["sclientid"] = $clientid;
		}
		$system = $this->GetHttpHeader("system");
		if($system && strtolower($system) != $user["ssystem"]){
			$entity["ssystem"] = strtolower($system);
		}
		
		if($entity){
			D("user")->where("id=".$user["id"])->save($entity);
		}
	}
	
	/* 用户相关处理 end */
	
	/* 验证码 begin */

	//检查验证码
    protected function CheckSmsCode($mobile, $action, $code){
   		
        if(empty($action) || empty($code) || empty($mobile) || !isMobile($mobile)){
            E("缺少验证码验证参数");
        }
        
        $smscode = json_decode(S("RequestSms-".$mobile), true);
        if(empty($smscode)){
			E("请发送验证码");
        }
        if($smscode["mobile"] != $mobile){
            E("手机号码与发送验证码不匹配");
        }
        if($smscode["action"] != $action){
            E("验证码操作类型不一致");
        }
        if($smscode["code"] != $code){
            E("验证码错误");
        }
    }

	/* 验证码 end */

   	/* 图片上传 begin */
   	
   	//单图上传
   	protected function ImageUpload($key, $folder = "images", $exts = array('gif','bmp','jpg','jpeg','png')){
   		
		/* 接收图片 begin */
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     204800000 ;// 设置附件上传大小
        $upload->exts      =     $exts;// 设置附件上传类型
        $upload->autoSub   =     false; // 自动子目录
        $upload->rootPath  =     './upload/'; // 设置附件根目录
        $upload->savePath  =     './'.$folder.'/'; // 设置附件上传目录
        // 上传文件 
        $info = $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            return false;
        }
        return $info[$key]['savename'];
		/* 接收图片 end */
		
   	}
   	
   	//多图上传
   	protected function ImageBatchUpload($folder = "images", $exts = array('gif','bmp','jpg','jpeg','png')){
   		
		/* 接收图片 begin */
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     20480000 ;// 设置附件上传大小
        $upload->exts      =     $exts;// 设置附件上传类型
        $upload->autoSub   =     false; // 自动子目录
        $upload->rootPath  =     './upload/'; // 设置附件根目录
        $upload->savePath  =     './'.$folder.'/'; // 设置附件上传目录
        $upload->saveName  =     array(GetFileName);
        // 上传文件 
        $info = $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            return false;
        }
        foreach($info as $key=>$value){
        	$data[] = '/upload/'.$folder.'/'.$info[$key]['savename'];
        }
        
        return $data;
        
        /* 接收图片 end */
       
   	}
   	
   	/* 图片上传 end */
   	
   	/* 敏感字处理 begin */
   	
   	protected function FilterBadWords($str){
   		
   		$badword = D('badwords')->field('keyword')->find();
   		if(empty($badword)){
   			return $str;
   		}
   		
   		$badstr = array_combine($badword, array_fill(0,count($badword), '*'));
   		
   		return strtr($str, $badstr);
   	}
   	
   	/* 敏感字处理 end */
	
    //生成订单流水号
    protected function BuildOrderSN(){
        
        list($msec, $sec) = explode(' ', microtime());
        $time =  ((float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000)) * 0.001;
        
        if(strstr($time,'.')){
            sprintf("%01.3f",$time); //小数点。不足三位补0
            list($usec, $sec) = explode(".",$time);
            $sec = str_pad($sec,3,"0",STR_PAD_RIGHT); //不足3位。右边补0
        }else{
            $usec = $time;
            $sec = "000"; 
        }
        $date = date("YmdHisx",$usec);

        $sn = str_replace('x', $sec, $date);
        $sn .= rand(100, 999);

        return $sn;
    }
	
	//补全访问链接地址
    protected function DoUrlHandle($thumb){
		if(!empty($thumb) && (strpos(strtolower($thumb), 'http://') === false && strpos(strtolower($thumb), 'https://') === false)){
			$http_type = "http://";
			if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')){
				$http_type = "https://";
			}
			return $http_type.$_SERVER['HTTP_HOST'].$thumb;
		}else{
			return $thumb;
		}
	}

	//补全访问链接地址 - 集合
	protected function DoUrlListHandle($images){
		if(empty($images)){
			return array();
		}

		$images = str_replace("\r\n", "|", $images);
		$images = str_replace(",", "|", $images);
		$list = explode("|", $images);
		foreach($list as $k=>$v){
            $v = $this->DoUrlHandle($v);
            $list[$k] = $v;
            if (empty($v)) {
                unset($list[$k]);
            }
		}

		return $list;
	}

	//删除不存在链接地址 - 集合
	protected function DelUrlListHandle($images){
		if(empty($images)){
			return array();
		}
	
		$images = str_replace("\r\n", "|", $images);
		$images = str_replace(",", "|", $images);
		$list = explode("|", $images);
		$return=array();
		foreach($list as $k=>$v){
	        $list[$k] = $v;
			if(!is_http($v)){
				if(is_file('.'.$v)){
					$return[]=$v;
				}
			}else{
				$return[]=$v;
			}
		}
		$return=implode(',',$return);
		return $return;
	}

	//百度编辑器图片路径替换
	protected function UEditorUrlReplace($content){
		$domainsite = $this->GetDomainSite();
		//return preg_replace('/(<img.+?src=")(.*?)/','$1'.$domainsite.'$2', $detail["content"]);
		
		//设置内容中的图片尺寸最大为100%;
		$content = str_replace("<img", "<img style=\"max-width:100%;\"", $content);

		return str_replace("/upload/edit/image/", $domainsite."/upload/edit/image/", $content);
	}

	//获取站点域名
	protected function GetDomainSite(){
		$http_type = "http://";
		if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')){
			$http_type = "https://";
		}
		return $http_type.$_SERVER['HTTP_HOST'];
	}

	//获取服务订单状态
    protected function GetServiceOrderStatus($order){
        $status = array("com_status"=>-2, "com_status_str"=>"未知状态");
        if(empty($order)){
            return $status;
        }
		if($order["admin_status"] == 0){
            $status = array("com_status"=>14, "com_status_str"=>"待审核");
		} else if($order["status"] == 5){
            $status = array("com_status"=>5, "com_status_str"=>"退款中");
        } else if($order["status"] == 6){
            $status = array("com_status"=>6, "com_status_str"=>"已退款");
        } else if($order["status"] == 2){
            $status = array("com_status"=>7, "com_status_str"=>"已取消");
        } else if($order["status"] == 1){
            if($order["execute_status"] == 0){
				$status = array("com_status"=>10, "com_status_str"=>"待服务");
                if($order["type"] == 1){
                    $status["com_status_str"] = "待配送";
				}
				
				if($order["service_userid"] <= 0){
					$status["com_status_str"] = "待抢单";
				} else if($order["assess"] == 1){
					$status["com_status_str"] = "待上门评估";
				}

            } else if(in_array($order["execute_status"], [1,2])){
				$status = array("com_status"=>11, "com_status_str"=>"服务中");
				
				$againmodel = D("service_order_again_record");

				//续费状态
				$map = array("orderid"=>$order["id"], "type"=>1);
				$again_record = $againmodel->where($map)->find();
				if($again_record && $again_record["pay_status"] != 3){
					if($again_record["is_agree"] == 0){
						$status = array("com_status"=>18, "com_status_str"=>"等待确认续费");
					}else if($again_record["is_agree"] == 1){
						$status = array("com_status"=>19, "com_status_str"=>"已同意续费");
					}else if($again_record["is_agree"] == 2){
						$status = array("com_status"=>21, "com_status_str"=>"拒绝续费");
					}
				} else if($order["type"] == 1){
                    $status["com_status_str"] = "配送中";
                } else if ($order["execute_status"] == 1){
					$status = array("com_status"=>13, "com_status_str"=>"待客户确认开始服务");
					
					if($order["assess"] == 1){
						if($order["assess_status"] == 1){
							$status = array("com_status"=>16, "com_status_str"=>"评估中");
						} else if($order["assess_status"] == 2 && $order["again_status"] == 1){
							$status = array("com_status"=>17, "com_status_str"=>"待缴付尾款");
						}
					}
                }
            } else if($order["execute_status"] == 3){
                $status = array("com_status"=>12, "com_status_str"=>"待确认完成");
            } else if($order["execute_status"] == 4){
                $status = array("com_status"=>4, "com_status_str"=>"已完成");
            } else if($order["execute_status"] == 7){
				$status = array("com_status"=>20, "com_status_str"=>"已爽约");
			}
        } else if($order["status"] == 4){
			$status = array("com_status"=>4, "com_status_str"=>"已完成");
			
			if($order["execute_status"] == 8){
				$status = array("com_status"=>30, "com_status_str"=>"已关闭");
			}
		}
		
		if($order["doctor"] == 1){ //医嘱
			$status["record_status"] = 1;
		}
		if($order["assess"] == 1){
			$recordmodel = D("service_order_assess_record");

			$map = array("orderid"=>$order["id"], "careid"=>$order["careid"]);
			$record = $recordmodel->where($map)->find();
			if($record){
				if($order["service_role"] == 3){ //家护师 - 线下评估表单
					$status["record_status"] = 2;
					if($order["doctor"] == 1){ //医嘱 + 家护师 - 线下评估表单
						$status["record_status"] = 4;
					}
				} else if($order["service_role"] == 4){ //康复师 - 线下评估
					$status["record_status"] = 3;
					if($order["doctor"] == 1){ //医嘱 + 康复师 - 线下评估
						$status["record_status"] = 5;
					}

					//已进行线下评估
					$status["assess_record_status"] = 1;
				}
			} else{
				if($order["service_role"] == 4){
					//未进行线下评估
					$status["assess_record_status"] = 0;
				}
			}
		}

        return $status;
    }
	
	//获取服务订单进度条状态
	protected function GetProgress($order){
		$com_status = $order["com_status"]["com_status"];

		switch ($com_status) {
			case 10: 
				return 1; //接收订单
			case 13:
			case 16: 
				return 2; //上门服务
			case 11:
			case 17:
				return 3; //待支付余额
			case 12:
				return 4; //待客户确认结束
			case 4:
				return 5; //完成服务
		}

		return 0;
	}

    //计算评分星级
    protected function calcstar($score){
        $star = 0;
        if ($score >= 80) {
            $star = 5;
        } else if ($score < 80 && $score >= 60) {
            $star = 4;
        } else if ($score < 60 && $score >= 40) {
            $star = 3;
        } else if ($score < 40 && $score >= 20) {
            $star = 2;
        } else if ($score < 20 && $score >= 0) {
            $star = 1;
        }

        return $star;
    }
	
	//检查绑定用户角色
	protected function BindUserRole($userid, $role){
	    //用户角色表
	    $model = D("user_role");
	
	    $add = array(
	        "userid"=>$userid, "status"=>1, "role"=>$role,
	        "remark"=>"", "createdate"=>date("Y-m-d H:i:s")
	    );
	    $map = array("userid"=>$userid, "role"=>$role);
	    $checkrole = $model->where($map)->find();
	    if($checkrole){
	        return;
	    }
	
	    $model->add($add);
	}
}