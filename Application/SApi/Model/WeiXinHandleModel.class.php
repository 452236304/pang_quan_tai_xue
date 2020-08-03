<?php
namespace Api\Model;

require_once "Application/Payment/Weixin/Extend/WxPay.Config.php";
require_once "Application/Payment/Weixin/Extend/log.php";

class WeiXinHandleModel{
	public $AppID = '';
	public $AppSecret = '';
	public $access_token = '';

	function __construct(){
        $config = new \WxPayConfig();
        $this->AppID = $config->GetAppId();
        $this->AppSecret = $config->GetAppSecret();
        
        //初始化日志
        $logHandler= new \CLogFileHandler("logs/weixin/".date('Y-m-d').'.log');
        $log = \Log::Init($logHandler, 15);
    }
    
    //获取用户openid
    public function GetOpenID($code){
        //获取用户openid
        $curl1 = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->AppID.'&secret='.$this->AppSecret.'&code='.$code.'&grant_type=authorization_code';  
        $content1 = $this->http_curl($curl1); 
        $result1 = json_decode($content1[1],true);

        \Log::INFO("获取用户openid：".json_encode($result1));

        if($result1['errcode']){
        	return null;
        }
        $openid = $result1['openid'];
        $this->access_token = $result1['access_token'];

        return $openid;
    }

    //获取用户基本信息
    public function GetWxUser($openid){
        $access_token = $this->access_token;

        //获取用户基本信息
		$userInfourl = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN ';
        $recontent = $this->http_curl($userInfourl);
        $wxuser = json_decode($recontent[1], true);

        \Log::INFO("获取用户基本信息：".json_encode($wxuser));

        if($wxuser['errcode']){
        	return null;
        }
        
        return $wxuser;
    }

    //获取公众号access_token
    private function GetAccessToken(){
        $file = "./access_token.txt"; 
        if(file_exists($file) && time() - filemtime($file) < 7200){
            $access_token = file_get_contents($file);
        }else{ 
    		$curl = 'https://api.weixin.qq.com/cgi-bin/token?appid='.$this->AppID.'&secret='.$this->AppSecret.'&grant_type=client_credential';  
    		$content = $this->http_curl($curl); 
    		$result = json_decode($content[1], true);
            file_put_contents($file,$result["access_token"]);     //把access_token放到文件中
    		$access_token = $result['access_token'];	//公众号access_token
        }

        return $access_token;
    }

    //获取公众号Ticket
    private function GetTicket(){
        $access_token = $this->GetAccessToken();

        //获取公众号getTicket
        $file1 = "./jsapi_ticket.txt"; 
        if(file_exists($file1) && (time()-filemtime($file1)) < 7200){
            $ticket = file_get_contents($file1);
        }else{ 
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$access_token."&type=jsapi";
            $resultArr = $this->http_curl($url);
            $resultArr = json_decode($resultArr[1], true);
            file_put_contents($file1, $resultArr["ticket"]);     //把getTicket放到文件中

            $ticket = $resultArr["ticket"];
        }

        return $ticket;
    }
	
	//http 键值对请求
	private function http_curl($url, $method='POST', $postfields = null, $headers = array(), $debug = false){
        $ci = curl_init();
        /* Curl settings */
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ci, CURLOPT_TIMEOUT, 30);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        switch ($method) {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, true);
                if (!empty($postfields)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
                    $this->postdata = $postfields;
                }
                break;
        }
        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ci, CURLINFO_HEADER_OUT, true);

        $response = curl_exec($ci);
        $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);

        if ($debug) {
            echo "=====post data======\r\n";
            var_dump($postfields);

            echo '=====info=====' . "\r\n";
            print_r(curl_getinfo($ci));

            echo '=====$response=====' . "\r\n";
            print_r($response);
        }
        curl_close($ci);
        return array($http_code, $response);  
    }
    
    //发送消息模板
    public function TemplatePush($data){
    	
        $openid = session('openid');
        $url = session('now_url');
        
        //获取access_token
        $file = "./access_token.txt"; 
        $access_token = file_get_contents($file);
        $template = array(
	        'touser'=>$openid,
	        'template_id'=>"BH2SL7utEo662ISfxOyeA0zXdWwWl9ShKWtC9cdazms",//模板的id
	        'url'=>$url,
	        'data'=>array(
	            'first'=>array('value'=>urlencode(""), 'color'=>"#00008B"),
	            'keyword1'=>array('value'=>urlencode(""), 'color'=>"#00008B"),
	            'keyword2'=>array('value'=>urlencode(""), 'color'=>"#00008B"),
	            'remark'=>array('value'=>urlencode("感谢您的关注，点击继续现场游戏活动"), 'color'=>"#00008B"),
            )
        );
        
        $json_template = json_encode($template);
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;
        $res = $this->http_request($url,urldecode($json_template));
        
    }
    
    //http json格式请求
    private function http_request($url,$data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        
        return $output;
    }	
}
?>