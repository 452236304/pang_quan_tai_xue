<?php
namespace CApi\Controller;

class TestController extends BaseController
{

    public function index(){
        $order_type = I('order_type');
        $order_id = I('order_id');
        D('Brokerage', 'Service')->orderSettle($order_type, $order_id);
        exit('success');
    }

    public function settle(){
        D('Brokerage', 'Service')->settle();
    }

    public function callback(){
       $attach = array(
           "type"=>1,
           "logsn"=>'20200710162035678966',
           "logid"=>'445',
           "hybrid"=>'xcx'
       );
        D('OrderCallbackHandle')->OrderHandle($attach);
    }

    public function moor(){
        $user_id = 322;
        $order_id = 197;
        $content = D('Moor', 'Service')->orderMessage($order_id, 3);
        $redult = D('Moor', 'Service')->createContext($user_id);
        var_dump($redult);
        $redult = D('Moor', 'Service')->sendRobotTextMessage($user_id, $content);
        var_dump($redult);
    }
}