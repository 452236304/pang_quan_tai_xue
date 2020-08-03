<?php
namespace CApi\Controller;

use mysql_xdevapi\Exception;
use Think\Controller;

class CrontabController extends Controller
{

    /**
     * Notes: 分销结算
     * User: dede
     * Date: 2020/6/19
     * Time: 7:01 下午
     */
    public function settle(){
        try{
            D('Brokerage', 'Service')->settle();
        }catch ( Exception $e ){
            echo $e->getMessage();
        }

    }
}