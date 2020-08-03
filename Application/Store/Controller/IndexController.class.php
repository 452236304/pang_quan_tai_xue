<?php
namespace Store\Controller;
use Think\Controller;
class IndexController extends BaseController {
	public function __construct(){
        header("content-type:text/html; charset=utf-8");
        parent::__construct();
    }
	

    public function index(){
		$sysitem = D("SysitemStore");
		$sysitemlist = $sysitem->getMenuListByUserid($_SESSION["storeID"]);
		
		$sysuser = D("SysuserStore");
		$sysuserinfo = $sysuser->getOneById($_SESSION["manID"]);
		
		foreach ($sysitemlist as $key => $value) {
			if($value["bid"]==0){
				if($value["url"]!=""){
					$urlArr = explode("|",$value["url"]);
					if(count($urlArr)==2){
						$sysitemlist[$key]["url"] = U($urlArr[0],$urlArr[1]);
					}else{
						$url = $urlArr[0];
						if((strpos(strtolower($urlArr[0]), 'http://') === false && strpos(strtolower($urlArr[0]), 'https://') === false)){
						    $url = U($urlArr[0]);;
						}
						$sysitemlist[$key]["url"] = $url;
					}
				}
				$sysitemArr[$key] = $sysitemlist[$key];
				foreach ($sysitemlist as $k => $v) {
					if($v["bid"]==$value["id"]){
                        $urlArr = explode("|",$v["url"]);
                        if(count($urlArr)==2){
							$sysitemlist[$k]["url"] = U($urlArr[0],$urlArr[1]);
						}else{
                            $url = $urlArr[0];
                            if((strpos(strtolower($urlArr[0]), 'http://') === false && strpos(strtolower($urlArr[0]), 'https://') === false)){
                                $url = U($urlArr[0]);;
                            }
							$sysitemlist[$k]["url"] = $url;
						}
						// $sysitemlist[$k]["url"] = U($v["url"]);
						$sysitemArr[$key]["smenu"][]=$sysitemlist[$k];
					}
				}
			}
		}
		$data["sysitem"] = $sysitemArr;
		
		$this->assign($data);
		$this->assign('sysuserinfo', $sysuserinfo);

		$sysuserclassmodel = D("sysuserclassStore");
		$sysuserclass = $sysuserclassmodel->getOneById($sysuserinfo["typeid"]);
		$this->assign("sysuserclass", $sysuserclass);

        $this->display();
    }

    public function indexdefault(){
    	$this->display("index_default");
    }

    public function login(){
    	$doinfo = I("get.doinfo");
    	if($doinfo=="userlogin"){
			$sysuser = D("SysuserStore");
			
			if(getenv("HTTP_CLIENT_IP")){
				$ip = getenv("HTTP_CLIENT_IP"); 
			}else if(getenv("HTTP_X_FORWARDED_FOR")){
				$ip = getenv("HTTP_X_FORWARDED_FOR");
			}else if(getenv("REMOTE_ADDR")){
				$ip = getenv("REMOTE_ADDR");
			}
			$ipArr=explode('.',$ip);
			if($ip!='119.130.105.184'){
				//$this->assign('return','该ip地址不能访问');
				//$this->show();
				//exit;
			}
			if($sysuser->autoCheckToken($_POST)){
				$username = I("post.username");
				$password = I("post.password");
				$authcode = I("post.authcode");
				if(!empty($authcode) && verify($authcode)){
					$curuser = $sysuser->getByUsername($username);
					if($curuser["password"]==md5($password)){
						if($curuser["ispass"]==1){
							session_cache_expire(3600);
							$_SESSION["storeID"] = $curuser["id"];
							$_SESSION["businessID"] = $curuser["business_id"];
							$_SESSION['username']=$curuser['username'];
							$_SESSION["classID"] = $curuser["typeid"];
							$_SESSION["power"] = $curuser["power"];
							$data["logintime"] = time();
							$sysuser->where("id=".$curuser["id"])->save($data);
							redirect(U('Index/index'));
							exit();
						}else{
							$this->assign("return","此用户正等待审核");
						}
					}else{
						$this->assign("return","密码不正确");
					}
				}else{
					$this->assign("return","验证码不正确");
				}
			}
		}
		$this->display();
    }

    public function logout(){
    	header("content-type:text/html; charset=utf-8");
    	session_destroy();
    	redirect(U('Index/login'));
    }


}