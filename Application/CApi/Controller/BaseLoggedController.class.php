<?php
namespace CApi\Controller;
use Think\Controller;
class BaseLoggedController extends BaseController {
	
	/* 构造函数 begin */
	function __construct(){
		$this->CheckUserLogin = true;

		parent::__construct();
	}
	/* 构造函数 end */

	//获取商品订单状态
	protected function GetProductOrderStatus($order){
		$status = array("com_status"=>-2, "com_status_str"=>"未知状态");
		if(empty($order)){
			return $status;
		}

        if($order["status"] == 1 && $order["pay_status"] == 0){
			$status = array("com_status"=>1, "com_status_str"=>"待付款");
            
            //已超时
			$time = time();
			$outtime = strtotime("+30 minute", strtotime($order["createdate"]));
            if($time > $outtime){
            	$status = array("com_status"=>8, "com_status_str"=>"已超时");
            }
		} else if($order["status"] == 5 && $order["pay_status"] == 3){
			$status = array("com_status"=>5, "com_status_str"=>"申请售后中");
			
			$refundmodel = D("product_order_refund");
			$map = array("orderid"=>$order["id"], "userid"=>$order["userid"]);
			$record = $refundmodel->where($map)->find();
			if($record){
				if($record["type"] == 1){
					$status["com_status_str"] = "申请退货中";
				} else if($record["type"] == 2){
					$status["com_status_str"] = "申请退款中";
				}
			}
		} else if($order["status"] == 6 && $order["pay_status"] == 3){
			if($order['refund'] == 1){
				$status = array("com_status"=>6, "com_status_str"=>"拒绝退款");
			}else{
				$status = array("com_status"=>6, "com_status_str"=>"已退款");
			}
			
		} else if($order["status"] == 2){
			$status = array("com_status"=>7, "com_status_str"=>"已取消");
		} else if($order["status"] == 1 && $order["pay_status"] == 3){
            $status = array("com_status"=>9, "com_status_str"=>"已付款");
            //定制类和改造类的方案不需要
            if ($order["type"] == 0) {
                if($order["shipping_status"] == 0){
                    $status = array("com_status"=>2, "com_status_str"=>"待发货");
                } else if($order["shipping_status"] == 1){
                    $status = array("com_status"=>3, "com_status_str"=>"已发货");
                }
			}
			//活动订单 待审核订单和待自取订单
			if($order['is_activity']==1 && $order['examine']==0){
				 $status = array("com_status"=>10, "com_status_str"=>"待审核");
			}
			if($order['is_activity']==1 && $order['examine']==2){
				 $status = array("com_status"=>10, "com_status_str"=>"审核不通过");
			}
			if($order['is_activity']==1 && $order['examine']==1 && $order['extract']==1){
				 $status = array("com_status"=>11, "com_status_str"=>"待自取");
			}
			//验证是否为7天内可退款订单 - 申请退款按钮
			$time = time();
			$buytime = strtotime("+7 day", strtotime($order["pay_date"]));
			if($order["type"] == 0 && $buytime > $time){
				$status["refund"] = 1;
			}

        } else if($order["status"] == 4 && $order["pay_status"] == 3){
			$status = array("com_status"=>4, "com_status_str"=>"已完成");
			
			if($order["is_comment"] == 0){
				$status["com_status_str"] = "待评价";
			}

            //验证是否为7天内可退款订单 - 申请退款按钮
            $time = time();
            $buytime = strtotime("+7 day", strtotime($order["pay_date"]));
            if($order["type"] == 0 && $buytime > $time){
                $status["refund"] = 1;
            }
		}

		return $status;
	}

	//获取机构订单状态
	protected function GetOrgOrderStatus($order){
		$status = array("com_status"=>-2, "com_status_str"=>"未知状态");
		if(empty($order)){
			return $status;
		}
        if($order["status"] == 1 && $order["pay_status"] == 0){
			$status = array("com_status"=>1, "com_status_str"=>"待付款");
            
            //已超时
             $time = time();
             $outtime = strtotime("+30 minute", strtotime($order["createdate"]));
             if($time > $outtime){
             	$status = array("com_status"=>8, "com_status_str"=>"已超时");
             }
		} else if($order["status"] == 5 && $order["pay_status"] == 3){
			$status = array("com_status"=>5, "com_status_str"=>"申请退款中");
		} else if($order["status"] == 6 && $order["pay_status"] == 3){
			$status = array("com_status"=>6, "com_status_str"=>"已退款");
		} else if($order["status"] == 2){
			$status = array("com_status"=>7, "com_status_str"=>"已取消");
		} else if($order["status"] == 1 && $order["pay_status"] == 3){
			$status = array("com_status"=>2, "com_status_str"=>"已付款");
			
			//验证是否为7天内可退款订单 - 申请退款按钮
			$time = time();
			$buytime = strtotime("+7 day", strtotime($order["pay_date"]));
			if($buytime > $time){
				$status["refund"] = 1;
			}
		} else if($order["status"] == 4 && $order["pay_status"] == 3){
			$status = array("com_status"=>4, "com_status_str"=>"已完成");
			if($order["type"] == 3){
				if($order["commentid"] > 0){
					$status["com_status_str"] = "已评价";
				} else{
					$status["com_status_str"] = "待评价";
				}
			}
			
			//验证是否为7天内可退款订单 - 申请退款按钮
			$time = time();
			$buytime = strtotime("+7 day", strtotime($order["pay_date"]));
			if($buytime > $time){
				$status["refund"] = 1;
			}
		}

		return $status;
	}

	//获取服务订单状态
	protected function GetServiceOrderStatus($order){
		$status = array("com_status"=>-2, "com_status_str"=>"未知状态");
		if(empty($order)){
			return $status;
		}

        if($order["status"] == 1 && $order["pay_status"] == 0){
			$status = array("com_status"=>1, "com_status_str"=>"待付款");
            
            //已超时
             $time = time();
             $outtime = strtotime("+30 minute", strtotime($order["createdate"]));
             if($time > $outtime){
             	$status = array("com_status"=>8, "com_status_str"=>"已超时");
             }
		} else if($order["status"] == 5 && $order["pay_status"] == 3){
			$status = array("com_status"=>5, "com_status_str"=>"申请退款中");
		} else if($order["status"] == 6 && $order["pay_status"] == 3){
			if($order['refund'] == 1){
				$status = array("com_status"=>6, "com_status_str"=>"拒绝退款");
			}else{
				$status = array("com_status"=>6, "com_status_str"=>"已退款");
			}
		} else if($order["status"] == 2){
			$status = array("com_status"=>7, "com_status_str"=>"已取消");
		} else if($order["status"] == 1 && $order["pay_status"] == 3){
			$status = array("com_status"=>2, "com_status_str"=>"已付款");
			if ($order['admin_status'] == 0) {
                $status = array("com_status"=>14, "com_status_str"=>"待审核");
            } else if($order["admin_status"] == 2){
				$status = array("com_status"=>15, "com_status_str"=>"审核不通过");
			} else if($order["execute_status"] == 0){
				$status = array("com_status"=>10, "com_status_str"=>"等待服务");
				if($order["type"] == 1){
					$status["com_status_str"] = "等待配送";
				}
				if($order["service_userid"] <= 0){
					$status["com_status_str"] = "待接单";
				} else if($order["assess"] == 1){
					$status["com_status_str"] = "待上门评估";
				}
			} else if(in_array($order["execute_status"], [1,2])){
				$status = array("com_status"=>11, "com_status_str"=>"服务中");
				
				$againmodel = D("service_order_again_record");

				//续费状态
				$map = array("orderid"=>$order["id"], "type"=>1);
				$again_record = $againmodel->where($map)->find();
				if($again_record && $again_record["pay_status"] != 3){
					if($again_record["is_agree"] == 0){
						$status = array("com_status"=>18, "com_status_str"=>"等待确认续费");
					}else if($again_record["is_agree"] == 1){
						$status = array("com_status"=>19, "com_status_str"=>"已同意续费");
					}else if($again_record["is_agree"] == 2){
						$status = array("com_status"=>21, "com_status_str"=>"拒绝续费");
					}
				} else if($order["type"] == 1){
                    $status["com_status_str"] = "配送中";
                } else if ($order["type"] == 2 && $order["execute_status"] == 1){
					$status = array("com_status"=>13, "com_status_str"=>"待确认开始服务");
					
					if($order["assess"] == 1){
						if($order["assess_status"] == 1){
							$status = array("com_status"=>16, "com_status_str"=>"评估中");
						} else if($order["assess_status"] == 2 && $order["again_status"] == 1){
							$status = array("com_status"=>17, "com_status_str"=>"待缴付尾款");
						}
					}
                }
			} else if($order["execute_status"] == 3){
				$status = array("com_status"=>12, "com_status_str"=>"待确认完成");
			} else if($order["execute_status"] == 7){
				$status = array("com_status"=>20, "com_status_str"=>"已爽约");
			}
			
			//续费要求：订单服务结束30分钟前，订单为服务中，订单续费价格大于0
			$endtime = strtotime($order["endtime"]);
			$time = strtotime("+30 minute", time());
			if ($endtime <= $time && in_array($order["execute_status"], [1,2,3])
				&& $order["time_type"] != 3 && $order["again_price"] > 0) {
				$status["again"] = 1;
			}
			
			//订单非服务中 且 订单支付金额大于0
			if(!in_array($order["execute_status"], [1,2]) && $order["amount"] > 0){
				//验证是否为7天内可退款订单 - 申请退款按钮
				$time = time();
				$buytime = strtotime("+7 day", strtotime($order["pay_date"]));
				if($buytime > $time){
					$status["refund"] = 1;
				}
			}
		} else if($order["status"] == 4 && $order["pay_status"] == 3){
			$status = array("com_status"=>4, "com_status_str"=>"已完成");
			
			if($order["execute_status"] == 8){
				$status = array("com_status"=>30, "com_status_str"=>"已关闭");
			}

			//订单支付金额大于0
			if($order["execute_status"] != 8 && $order["amount"] > 0){
				//验证是否为7天内可退款订单 - 申请退款按钮
				$time = time();
				$buytime = strtotime("+7 day", strtotime($order["pay_date"]));
				if($buytime > $time){
					$status["refund"] = 1;
				}
			}
		}

		if($order["time_type"] != 3 && $order["again_count"] > 0){
			$status["again"] = 4;
		} else if(empty($status["again"])){
			$status["again"] = 0;
		}

		if($order["doctor"] == 1){ //医嘱
			$status["record_status"] = 1;
		}
		if($order["assess"] == 1){
			$recordmodel = D("service_order_assess_record");

			$map = array("orderid"=>$order["id"], "careid"=>$order["careid"]);
			$record = $recordmodel->where($map)->find();
			if($record){
				if($order["service_role"] == 3){ //家护师 - 线下评估表单
					$status["record_status"] = 2;
					if($order["doctor"] == 1){ //医嘱 + 家护师 - 线下评估表单
						$status["record_status"] = 4;
					}
				} else if($order["service_role"] == 4){ //康复师 - 线下评估
					$status["record_status"] = 3;
					if($order["doctor"] == 1){ //医嘱 + 康复师 - 线下评估
						$status["record_status"] = 5;
					}
				}
			}
		}

		return $status;
	}
	
	//获取服务订单进度条状态
	protected function GetProgress($order){
		$com_status = $order["com_status"]["com_status"];

		switch($com_status){
			case 1: 
				return 1; //提交预约
			case 2: 
			case 14:
				return 2; //支付订金
			case 10:
				return 3; //审核通过
			case 16:
				return 4; //线下评估
			case 17:
				return 5; //支付余额
			case 11:
			case 13:
			case 12:
				return 6; //正在服务
			case 4:
				if($order["commentid"] > 0){
					return 8; //完成评价
				}
				return 7; //完成服务
		}

		return 0;
	}
	
}