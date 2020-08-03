<?php
namespace CApi\Model;

require_once "Application/Payment/Weixin/Extend/WxPay.Config.php";
require_once "Application/Payment/Weixin/Extend/log.php";

class XcxHandleModel{
	public $AppID = '';
	public $AppSecret = '';
	public $access_token = '';

	function __construct($hybrid='xcx'){
        $config = new \WxPayConfig();
		$config->hybrid = $hybrid;
        $this->AppID = $config->GetAppId();
        $this->AppSecret = $config->GetAppSecret();
        
        //初始化日志
        $logHandler= new \CLogFileHandler("logs/weixin/".date('Y-m-d').'.log');
        $log = \Log::Init($logHandler, 15);
    }
    
    //获取小程序access_token
    private function GetAccessToken(){
        $file = "./Public/access_token.txt"; 
        if(file_exists($file) && time() - filemtime($file) < 7200){
            $access_token = file_get_contents($file);
        }else{
			$config = new \WxPayConfig();
			$config->hybrid = 'xcx';
			$this->AppID = $config->GetAppId();
			$this->AppSecret = $config->GetAppSecret();
			$curl ='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->AppID.'&secret='.$this->AppSecret;
    		//$curl = 'https://api.weixin.qq.com/cgi-bin/token?appid='.$this->AppID.'&secret='.$this->AppSecret.'&grant_type=client_credential';  
    		$content = $this->http_curl($curl); 
    		$result = json_decode($content[1], true);
            file_put_contents($file,$result["access_token"]);     //把access_token放到文件中
    		$access_token = $result['access_token'];	//小程序access_token
        }

        return $access_token;
    }
	//获取小程序码带参数
	public function GetXcxCode($ext = ''){
		$AccessToken = $this->GetAccessToken();
		$curl = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$AccessToken;
		$content = $this->send_json($curl,$ext);
		//$result = json_decode($content, true);
		$savepath = './upload/xcxcode/'.md5($ext['scene']).'.jpg';
		$path = '/upload/xcxcode/'.md5($ext['scene']).'.jpg';
		$file = fopen($savepath,"w");//打开文件准备写入
		fwrite($file,$content);//写入
		fclose($file);//关闭
		//file_put_contents($file,$content);     //把access_token放到文件中
		return $path;
		
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
	//http json格式请求
	private function send_json($url,$data){
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data,JSON_UNESCAPED_UNICODE));
	    $output = curl_exec($ch);
	    curl_close($ch);
	    
	    return $output;
	}	
}
?>