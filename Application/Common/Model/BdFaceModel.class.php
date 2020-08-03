<?php
namespace Common\Model;

require_once "Application/Common/BdAip/AipFace.php";
require_once "Application/Payment/Weixin/Extend/log.php";

class BdFaceModel{

    //构造函数
    function __construct(){
        //初始化日志
        $logHandler= new \CLogFileHandler("logs/bdaip/".date('Y-m-d').'.log');
        $log = \Log::Init($logHandler, 15);
    }

    public function FaceMatch($checkface, $userface){
        if(empty($checkface) || empty($userface)){
            \Log::INFO("data empty or count eq 0");
            return array("result"=>"FAIL", "message"=>"人脸识别参数不能为空");
        }

        $checkface = $this->FaceUrlRemoveHost($checkface);
        if(!file_exists($checkface)){
            return array("result"=>"FAIL", "message"=>"人脸识别用户照片不存在");
        }
        $userface = $this->FaceUrlRemoveHost($userface);
        if(!file_exists($userface)){
            return array("result"=>"FAIL", "message"=>"人脸识别认证用户照片不存在");
        }

        $client = new \AipFace();

        $images = array(
            array(
                "image"=>base64_encode(file_get_contents($checkface)),
                "image_type"=>"BASE64",
                //"liveness_control"=>"HIGH"
            ),
            array(
                "image"=>base64_encode(file_get_contents($userface)),
                "image_type"=>"BASE64",
                //"liveness_control"=>"HIGH"
            )
        );

        $data = $client->match($images);

        \Log::INFO("match result：".json_encode($data));

        if($data["error_code"] == "222202"){
            return array("result"=>"FAIL", "message"=>"图片中没有人脸");
        } else if($data["error_code"] == "222203"){
            return array("result"=>"FAIL", "message"=>"无法解析人脸");
        } else if($data["error_code"] == "223113"){
            return array("result"=>"FAIL", "message"=>"人脸有被遮挡");
        } else if($data["error_code"] == "223114"){
            return array("result"=>"FAIL", "message"=>"人脸模糊");
        } else if($data["error_code"] == "223115"){
            return array("result"=>"FAIL", "message"=>"人脸光照不好");
        }  else if($data["error_code"] == "223116"){
            return array("result"=>"FAIL", "message"=>"人脸不完整");
        } else if($data["error_code"] != "0"){
            return array("result"=>"FAIL", "message"=>"人脸识别失败，请检查日志");
        }

        $data = array(
            "result"=>"TRUE", "score"=>$data["result"]["score"], "result"=>$data
        );

        return $data;
    }

    private function FaceUrlRemoveHost($face){
        if(!empty($face) && (strpos(strtolower($face), 'http://') === false && strpos(strtolower($face), 'https://') === false)){
			return ".".$face;
        }

        // $http_type = "http://";
        // if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')){
        //     $http_type = "https://";
        // }

        $face = str_replace("http://", "", $face);
        $face = str_replace("https://", "", $face);
        $host = $_SERVER["HTTP_HOST"];
        $face = ".".str_replace($host, "", $face);

        return $face;
    }

}