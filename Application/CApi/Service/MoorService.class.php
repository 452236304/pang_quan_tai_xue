<?php

namespace CApi\Service;

use Think\Exception;

class MoorService
{

    //请求接口地址
    protected $url = "https://im-api.7moor.com";

    protected $accessId = "d305c350-c048-11ea-8393-8383f66884b7";

    protected $token = "t2WYrb0Z";

    //时间戳
    protected $timestamp = "";

    //设置随机数
    protected $nonce = "";

    //错误信息
    protected $err = "";

    //设置错误信息
    public function setError($err){
        $this->err = $err;
    }

    /**
     * 返回数据格式
     * @return $resp array
     */
    protected function respFormat($response)
    {
        $resp = json_decode($response, true);
        return $resp;
    }

    /**
     * 返回错误信息
     */
    public function getError()
    {
        return $this->err;
    }

    /**
     * [post post提交]
     * @param string $url 访问页面所需uri
     * @param array $params 传参数据
     * @param array $data 传参数据
     * @param array $headers HTTP报头
     * @return json $response 返回参数
     */
    protected function post($url, $data = '', $params = '', $headers = [])
    {
        if ($params) {
            $url .= '?' . http_build_query($params);
        }

        $url = $this->url . '' . $url;
        $ch = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 10,
        ];
        $headers[] = 'Content-Type: application/json';
        if ($headers) {
            $options[CURLOPT_HTTPHEADER] = $headers;
        }
        if ($data) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $resp_status = curl_getinfo($ch);
        curl_close($ch);
        if (intval($resp_status["http_code"]) == 200) {
            return $response;
        } else {
            throw new Exception('请求超时[' . $resp_status["http_code"] . ']');
        }
    }

    protected function http_curl($url, $data = null){
        $url = $this->url . $url;
        $header[] = "Accept: application/json";
        $header[] = "content-type: application/json";
        $header[] = "Content-Length: ".strlen( json_encode($data) );

        $ch = curl_init ();
        curl_setopt($ch, CURLOPT_URL, ($url) );//地址
        curl_setopt($ch, CURLOPT_POST, 1);   //请求方式为post
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($data)); //post传输的数据。
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $return = curl_exec ( $ch );
        $this->log($url, $data, $return);

        if($return === FALSE ){
            echo "CURL Error:".curl_error($ch);exit;
        }
        curl_close ( $ch );
        return $return;
    }

    //获取随机数
    protected function setNonce()
    {
        $this->nonce = rand(1000000, 9999999);
    }

    //获取签名
    protected function getMsgSignature($userId)
    {
        $token = $this->token;
        $timestamp = $this->timestamp;
        $nonce = $this->nonce;
        $array = array($token, $timestamp, $nonce, $userId);
        //按照字典排序
        usort($array, 'strcmp');
        $str = implode($array);
        return sha1($str);
    }

    //设置时间戳
    protected function setTimestamp()
    {
        $this->timestamp = time();
    }

    protected function getMsgId($userId)
    {
        return $this->timestamp . $userId . $this->nonce;
    }

    /**
     * 创建会话
     * @param   $userId 用户ID
     * @param   $nickName 用户昵称
     * @return
     * @version 1.0
     * @date    2020-07-09
     * @author miss
     */
    public function createContext($userId, $platform = 'pc')
    {
        $user = D('User')->find($userId);
        //设置时间戳
        $this->setTimestamp();
        //设置随机数
        $this->setNonce();
        try {
            $data = [
                'accessId' => $this->accessId,
                'userId' => $userId,
                'nickName' => $user['nickname'],
                'platform' => $platform,//网站接口
                'timestamp' => $this->timestamp,
                'nonce' => $this->nonce,
                'msg_signature' => $this->getMsgSignature($userId),
                'msgId' => $this->getMsgId($userId)
            ];

            $resp = $this->http_curl('/visitor/session', $data);
            $resp = $this->respFormat($resp);
            return $resp;
        } catch (Exception $e) {
            $this->setError($e);
        }
        return false;
    }

    /**
     * 发送文本消息
     * @param String userId 用户ID
     * @param String nickName 用户昵称
     * @param String content 消息内容
     * @return array $resp 返回参数
     */
    public function sendRobotTextMessage($userId, $content, $platform = 'pc')
    {
        //设置时间戳
        $this->setTimestamp();
        //设置随机数
        $this->setNonce();
        try {
            $data = [
                'accessId' => $this->accessId,
                'userId' => $userId,
                'platform' => $platform,//网站接口
                'msgType' => 'text',//消息类型
                'content' => $content,
                'timestamp' => $this->timestamp,
                'nonce' => $this->nonce,
                'msg_signature' => $this->getMsgSignature($userId),
                'msgId' => $this->getMsgId($userId)
            ];
            $resp = $this->http_curl('/visitor/message', $data);
            $resp = $this->respFormat($resp);
            return $resp;
        } catch (Exception $e) {
            $this->setError($e);
        }
        return false;
    }

    public function sendLeaveMessage($userId, $content, $platform = 'pc'){
        $user = D('User')->find($userId);
        //设置时间戳
        $this->setTimestamp();
        //设置随机数
        $this->setNonce();
        try {
            $data = [
                'accessId' => $this->accessId,
                'userId' => $userId,
                'token' => $this->token,
                'platform' => $platform,//网站接口
                'message' => $content,
                'timestamp' => $this->timestamp,
                'nonce' => $this->nonce,
                'msg_signature' => $this->getMsgSignature($userId),
                'phone' => $user['mobile'],
            ];
            $resp = $this->post('/visitor/leaveMsg', $data);
            $resp = $this->respFormat($resp);
            return $resp;
        } catch (Exception $e) {
            $this->setError($e);
        }
        return false;
    }

    /**
     * Notes:
     * User: dede
     * Date: 2020/7/10
     * Time: 6:48 下午
     * @param $order_id
     * @param $type  1:商城订单 2：服务订单
     * @return string
     */
    public function orderMessage($order_id, $type = 1){
        if( $type == 1 ){
            // 商城订单
            $order = D('ProductOrder')->find($order_id);
            $create_time = $order['createdate'];
            $title = $order['title'];
            $sn = $order['sn'];
            $status = $order['pay_date'] ? '已支付' : '待支付';
            $amount = $order['pay_date'] ? $order['amount'] : 0;
            $nickname = $order['nickname'];
            $time = '';
            $header = '商城';
        }else if( $type == 2 ){
            // 服务订单
            $order = D('ServiceOrder')->find($order_id);
            $create_time = $order['createdate'];
            $title = $order['title'];
            $sn = $order['sn'];
            $status = $order['pay_date'] ? '待审核' : '待支付';
            $amount = $order['pay_date'] ? $order['amount'] : 0;
            $nickname = $order['nickname'];
            if( $order['time_type'] == 0 ){
                $time = $order['time'] . '分钟';
            }else if( $order['time_type'] == 1 ){
                $time = $order['time'] . '小时';
            }else if( $order['time_type'] == 2 ){
                $time = $order['time'] . '天';
            }else if( $order['time_type'] == 3 ){
                $time = $order['time'] . '个月';
            }
            $header = '服务';
        }else if( $type == 3 ){
            // 服务订单
            $order = D('PensionActivityOrder')->find($order_id);
            $create_time = $order['createtime'];
            $title = $order['title'];
            $sn = $order['sn'];
            $status = $order['pay_date'] ? '待接单' : '待支付';
            $amount = $order['pay_date'] ? $order['amount'] : 0;
            $nickname = $order['nickname'];
            $header = '机构';
        }
        $content = $header . "订单内容：" . $title . "\n";
        $content .= "订单创建时间：" . $create_time . "\n";
        $content .= "订单状态：" . $status . "\n";
        $content .= "用户昵称：" . $nickname . "\n";
        $content .= "订单号：" . $sn . "\n";
        if( $time ){
            $content .= "服务时长：" . $time . "\n";
        }
        $content .= "实付款：" . $amount . "\n";
        return $content;
    }

    protected function log($url, $data, $return){
        $log = [
            'url' => $url,
            'data' => json_encode($data),
            'result' => $return,
            'add_time' => date('Y-m-d H:i:s')
        ];
        D('MessageLog')->add($log);
    }


}