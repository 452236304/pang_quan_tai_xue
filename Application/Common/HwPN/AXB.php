<?php

require_once 'PNBase.php';

/**
 * 华为隐私号码 - AXB模式
 */
class AXB extends PNBase{

    /**
     * 号码隐私 api url
     * @var string
     */
    private $requestUrl = 'https://rtcapi.cn-north-1.myhuaweicloud.com:12543/rest/caas/relationnumber/partners/v1.0';

    /**
     * MOBILE LIST
     * @var string
    */
    public $xs = ['+8617139937161']; //+8617139930665

    /**
     * MOBILE
     * @var string
    */
    public $relationNum = '';

    /**
     * @param string $key
     * @param string $secret
    */
    public function __construct($relationNum = "", $key = "", $secret = ""){
        parent::__construct($key, $secret);

        if($relationNum){
            $this->relationNum = $relationNum;
        }
    }

    /**
     * 构建X-WSSE值
     */
    private function buildWsseHeader(){
        date_default_timezone_set("UTC");
        $created = date('Y-m-d\TH:i:s\Z'); //Created
        $nonce = uniqid(); //Nonce
        $base64 = base64_encode(hash('sha256', ($nonce . $created . $this->secret), TRUE)); //PasswordDigest

        return sprintf("UsernameToken Username=\"%s\",PasswordDigest=\"%s\",Nonce=\"%s\",Created=\"%s\"", $this->key, $base64, $nonce, $created);
    }

    /**
     * AXB模式绑定接口
     *
     * @param num_a $A号码
     * @param num_b $B号码
     * @return string
     */
    public function bind($num_a, $num_b){
        // 必填,请参考"开发准备"获取如下数据,替换为实际值
        $relationNum = $this->relationNum; // X号码(隐私号码)
        $callerNum = '+86'.$num_a; // A号码
        $calleeNum = '+86'.$num_b; // B号码

        /*
        * 选填,各参数要求请参考"AXB模式绑定接口"
        */
        // $areaCode = '0755'; // 需要绑定的X号码对应的城市码
        // $callDirection = 0; // 允许呼叫的方向
        // $duration = 86400; // 绑定关系保持时间,到期后会被系统自动解除绑定关系
        // $recordFlag = 'false'; // 是否需要针对该绑定关系产生的所有通话录音
        // $recordHintTone = 'recordHintTone.wav'; // 设置录音提示音
        // $maxDuration = 60; // 设置允许单次通话进行的最长时间,通话时间从接通被叫的时刻开始计算
        // $lastMinVoice = 'lastMinVoice.wav'; // 设置通话剩余最后一分钟时的提示音
        // $privateSms = 'true'; // 设置该绑定关系是否支持短信功能

        // $callerHintTone = 'callerHintTone.wav'; // 设置A拨打X号码时的通话前等待音
        // $calleeHintTone = 'calleeHintTone.wav'; // 设置B拨打X号码时的通话前等待音
        // $preVoice = [
        //     'callerHintTone' => $callerHintTone,
        //     'calleeHintTone' => $calleeHintTone
        // ];

        // 请求Headers
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json;charset=UTF-8',
            'Authorization: WSSE realm="SDP",profile="UsernameToken",type="Appkey"',
            'X-WSSE: ' . $this->buildWsseHeader()
        ];
        // 请求Body,可按需删除选填参数
        $data = json_encode([
            'relationNum' => $relationNum,
            // 'areaCode' => $areaCode,
            'callerNum' => $callerNum,
            'calleeNum' => $calleeNum,
            // 'callDirection' => $callDirection,
            // 'duration' => $duration,
            // 'recordFlag' => $recordFlag,
            // 'recordHintTone' => $recordHintTone,
            // 'maxDuration' => $maxDuration,
            // 'lastMinVoice' => $lastMinVoice,
            // 'privateSms' => $privateSms,
            // 'preVoice' => $preVoice
        ]);

        $context_options = [
            'http' => [
                'method' => 'POST', // 请求方法为POST
                'header' => $headers,
                'content' => $data,
                'ignore_errors' => true // 获取错误码,方便调测
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ] // 为防止因HTTPS证书认证失败造成API调用失败,需要先忽略证书信任问题
        ];

        try {
            \Log::INFO("bind - 绑定请求数据：".$data);

            $response = file_get_contents($this->requestUrl, false, stream_context_create($context_options)); // 发送请求

            \Log::INFO("bind - 绑定结果：".$response);

            return $response;
        } catch (Exception $e) {
            \Log::ERROR("bind - 请求错误：".$e->getMessage());

            return array("resultcode"=>"-1", "resultdesc"=>"服务请求异常，请稍后重试");
        }
    }
    
    /**
     * AXB模式解绑接口
     *
     * @param subscriptionId $绑定ID
     * @return string
     */
    public function unbind($subscriptionId){
        /*
        * 选填,各参数要求请参考"AXB模式解绑接口"
        * subscriptionId和relationNum为二选一关系,两者都携带时以subscriptionId为准
        */
        $relationNum = $this->relationNum;

        // 请求Headers
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json;charset=UTF-8',
            'Authorization: WSSE realm="SDP",profile="UsernameToken",type="Appkey"',
            'X-WSSE: ' . $this->buildWsseHeader()
        ];
        // 请求URL参数
        $data = http_build_query([
            'subscriptionId' => $subscriptionId,
            'relationNum' => $relationNum
        ]);
        // 完整请求地址
        $fullUrl = $this->requestUrl . '?' . $data;

        $context_options = [
            'http' => [
                'method' => 'DELETE', // 请求方法为DELETE
                'header' => $headers,
                'ignore_errors' => true // 获取错误码,方便调测
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ] // 为防止因HTTPS证书认证失败造成API调用失败,需要先忽略证书信任问题
        ];

        try {
            \Log::INFO("unbind - 绑定请求数据：".$data);
            
            $response = file_get_contents($fullUrl, false, stream_context_create($context_options)); // 发送请求

            \Log::INFO("unbind - 绑定请求数据：".$response);

            return $response;
        } catch (Exception $e) {
            \Log::ERROR("unbind - 请求错误：".$e->getMessage());

            return array("resultcode"=>"-1", "resultdesc"=>"服务请求异常，请稍后重试");
        }
    }

    /**
     * AXB模式绑定信息修改接口
     *
     * @param subscriptionId $绑定ID
     * @param num_a $A号码
     * @param num_b $B号码
     * @return string
     */
    public function update($subscriptionId, $num_a, $num_b){
        /*
        * 选填,各参数要求请参考"AXB模式绑定信息修改接口"
        */
        $callerNum = '+86'.$num_a; // A号码
        $calleeNum = '+86'.$num_b; // B号码
        // $callDirection = 0; //允许呼叫的方向
        // $duration = 86400; //绑定关系保持时间,到期后会被系统自动解除绑定关系
        // $maxDuration = 60; //设置允许单次通话进行的最长时间,通话时间从接通被叫的时刻开始计算
        // $lastMinVoice = 'lastMinVoice.wav'; //设置通话剩余最后一分钟时的提示音
        // $privateSms = 'true'; //设置该绑定关系是否支持短信功能

        // $callerHintTone = 'callerHintTone.wav'; // 设置A拨打X号码时的通话前等待音
        // $calleeHintTone = 'calleeHintTone.wav'; // 设置B拨打X号码时的通话前等待音
        // $preVoice = [
        //     'callerHintTone' => $callerHintTone,
        //     'calleeHintTone' => $calleeHintTone
        // ];

        // 请求Headers
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json;charset=UTF-8',
            'Authorization: WSSE realm="SDP",profile="UsernameToken",type="Appkey"',
            'X-WSSE: ' . $this->buildWsseHeader()
        ];
        // 请求Body,可按需删除选填参数
        $data = json_encode([
            'subscriptionId' => $subscriptionId,
            'callerNum' => $callerNum,
            'calleeNum' => $calleeNum,
            // 'callDirection' => $callDirection,
            // 'duration' => $duration,
            // 'maxDuration' => $maxDuration,
            // 'lastMinVoice' => $lastMinVoice,
            // 'privateSms' => $privateSms,
            // 'preVoice' => $preVoice
        ]);

        $context_options = [
            'http' => [
                'method' => 'PUT', // 请求方法为PUT
                'header' => $headers,
                'content' => $data,
                'ignore_errors' => true // 获取错误码,方便调测
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ] // 为防止因HTTPS证书认证失败造成API调用失败,需要先忽略证书信任问题
        ];

        try {
            \Log::INFO("update - 绑定请求数据：".$data);

            $response = file_get_contents($this->requestUrl, false, stream_context_create($context_options)); // 发送请求

            \Log::INFO("update - 绑定请求数据：".$response);

            return $response;
        } catch (Exception $e) {
            \Log::ERROR("update - 请求错误：".$e->getMessage());

            return array("resultcode"=>"-1", "resultdesc"=>"服务请求异常，请稍后重试");
        }
    }
    
    /**
     * AXB模式绑定信息查询接口
     *
     * @param subscriptionId $绑定ID
     * @return string
     */
    public function query($subscriptionId){
        /*
        * 选填,各参数要求请参考"AXB模式绑定信息查询接口"
        * subscriptionId和relationNum为二选一关系,两者都携带时以subscriptionId为准
        */
        $relationNum = $this->relationNum; // 指定X号码(隐私号码)进行查询
        // $pageIndex = 1; //查询的分页索引,从1开始编号
        // $pageSize = 20; //查询的分页大小,即每次查询返回多少条数据

        // 请求Headers
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json;charset=UTF-8',
            'Authorization: WSSE realm="SDP",profile="UsernameToken",type="Appkey"',
            'X-WSSE: ' . $this->buildWsseHeader()
        ];
        // 请求URL参数
        $data = http_build_query([
            'subscriptionId' => $subscriptionId,
            'relationNum' => $relationNum
            // 'pageIndex' => $pageIndex,
            // 'pageSize' => $pageSize
        ]);
        // 完整请求地址
        $fullUrl = $this->requestUrl . '?' . $data;

        $context_options = [
            'http' => [
                'method' => 'GET', // 请求方法为GET
                'header' => $headers,
                'ignore_errors' => true // 获取错误码,方便调测
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ] // 为防止因HTTPS证书认证失败造成API调用失败,需要先忽略证书信任问题
        ];

        try {
            \Log::INFO("query - 绑定请求数据：".$data);

            $response = file_get_contents($fullUrl, false, stream_context_create($context_options)); // 发送请求

            \Log::INFO("query - 绑定查询结果：".$response);
            
            return $response;
        } catch (Exception $e) {
            \Log::ERROR("query - 请求错误：".$e->getMessage());

            return array("resultcode"=>"-1", "resultdesc"=>"服务请求异常，请稍后重试");
        }
    }

}
