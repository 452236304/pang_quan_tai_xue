<?php

require_once "Application/Payment/Weixin/Extend/log.php";

/**
 * 华为号码隐私
 */
class PNBase{

    /**
     * KEY
     * @var string
    */
    protected $key = 'j0DX3LWD4OtKe94DbkvV6e8270zk';

    /**
     * SECRET
     * @var string
    */
    protected $secret = 'iaot56wF00VE3tU8NCrDl71M1kVp';

    /**
     * @param string $key
     * @param string $secret
    */
     public function __construct($key = "", $secret = ""){
        if($key){
            $this->key = trim($key);
        }
        if($secret){
            $this->secret = trim($secret);
        }

        //初始化日志
        $logHandler= new \CLogFileHandler("logs/hwpn/".date('Y-m-d').'.log');
        $log = \Log::Init($logHandler, 15);
    }

}
