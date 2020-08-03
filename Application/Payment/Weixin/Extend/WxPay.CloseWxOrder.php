<?php

use Think\Exception;
/**
*
* example目录下为简单的支付样例，仅能用于搭建快速体验微信支付使用
* 样例的作用仅限于指导如何使用sdk，在安全上面仅做了简单处理， 复制使用样例代码时请慎重
* 请勿直接直接使用样例对外提供服务
* 
**/
require_once "Application/Payment/Weixin/Base/WxPay.Api.php";
require_once "Application/Payment/Weixin/Extend/WxPay.Config.php";
require_once 'Application/Payment/Weixin/Extend/log.php';

/**
 * 
 * 关闭微信订单
 * @author widyhu
 *
 */
class CloseWxOrder
{
	
	/**
	 * 
	 * 关闭订单
	 * @param UnifiedOrderInput $input
	 */
	public function CloseOrder($input)
	{
		try{
			$config = new \WxPayConfig();
			$result = \WxPayApi::closeOrder($config, $input);
			return $result;
		} catch(\Exception $e){
			\Log::ERROR($e->getMessage());
		}
		return false;
	}
}