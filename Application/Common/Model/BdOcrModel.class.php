<?php
namespace Common\Model;

require_once "Application/Common/BdAip/AipOcr.php";
require_once "Application/Payment/Weixin/Extend/log.php";

class BdOcrModel{

    //构造函数
    function __construct(){
        //初始化日志
        $logHandler= new \CLogFileHandler("logs/bdaip/".date('Y-m-d').'.log');
        $log = \Log::Init($logHandler, 15);
    }

    public function IdcardMatch($image, $id_card_side){
        if(empty($image) || empty($id_card_side)){
            \Log::INFO("data empty or count eq 0");
            return array("result"=>"FAIL", "message"=>"参数不能为空");
        }

        /* $image = $this->UrlRemoveHost($image);
        if(!file_exists($image)){
            return array("result"=>"FAIL", "message"=>"照片不存在");
        } */
        $client = new \AipOcr();


        $data = $client->idcard($image,$id_card_side);

        \Log::INFO("idcard result：".json_encode($data));

        return $data;
    }

    private function UrlRemoveHost($url){
        if(!empty($url) && (strpos(strtolower($url), 'http://') === false && strpos(strtolower($url), 'https://') === false)){
			return ".".$url;
        }

        // $http_type = "http://";
        // if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')){
        //     $http_type = "https://";
        // }

        $url = str_replace("http://", "", $url);
        $url = str_replace("https://", "", $url);
        $host = $_SERVER["HTTP_HOST"];
        $url = ".".str_replace($host, "", $url);

        return $url;
    }

}