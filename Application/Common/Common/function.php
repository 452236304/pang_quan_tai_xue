<?php

function verify($authcode="",$flag=1){
    ob_clean();
    $config = array(
    	//'useZh'     =>  true,           // 使用中文验证码
        'imageH'    =>  40,               // 验证码图片高度
        'imageW'    =>  110,               // 验证码图片宽度
        'fontSize'  =>  15,              // 验证码字体大小(px)
        'length'    =>  1,               // 验证码位数
        'codeSet'   =>  '123456',
        'useCurve'	=> false,
        'useNoise'	=> false,
    );
    $verify = new \Think\Verify($config);
    if(empty($authcode)){
    	$verify->entry($flag);
    }else{
		return $verify->check($authcode,$flag);
    }
}

function getpower($thisid,$powerstr){
	$powerid = ",".$thisid;
	$power = $_SESSION[$powerstr];
	if(stristr($power,$powerid)===false){
		return 0;
	}else{
		return 1;
	}
}

function getSelect($type,$para1,$para2){
	if($type=="radio"){
		if($para1==$para2){
			return "checked=\"checked\"";
		}elseif(strstr($para2,$para1)){
			return "checked=\"checked\"";
		}
	}elseif($type=="select"){
		$arr = explode(",",$para2);
		if($para1==$para2){
			return "selected=\"selected\"";
		}elseif(strstr($para2,$para1) && is_numeric($para1)==false){
			return "selected=\"selected\"";
		}elseif(in_array($para1,$arr)){
			return "selected=\"selected\"";
		}
	}elseif($type=="check"){
		if($para1==$para2){
			return "checked=\"checked\"";
		}elseif(is_array($para2)){
			if(in_array($para1, $para2)){
				return "checked=\"checked\"";
			}
		}elseif(strstr($para2,$para1)){
			return "checked=\"checked\"";
		}
	}elseif($type=="class"){
		if($para1==$para2){
			return "class=\"active\"";
		}
	}
}

function getMax($tab,$shield,$map){
	$tabel=D($tab);
	$info = $tabel->where($map)->order("$shield desc")->find();
	return $info[$shield]+1;
	//echo $tabel->getLastSql();
}

function restr(){//($body,$length,$tags)
	$numargs = func_num_args();
	$content = func_get_arg(0);
	if($numargs==1){
		$content = strip_tags(func_get_arg(0));
		return $content;
		exit();
	}
	if($numargs==2){
		$content = strip_tags(func_get_arg(0));
	}
	if($numargs>=3){
		$content = strip_tags(func_get_arg(0),func_get_arg(2));
	}
	$strlen = strlen(func_get_arg(0));
	if(func_get_arg(1)>=$strlen && func_get_arg(1)!=0){
		return $content;
		exit();
	}else{
		$length = func_get_arg(1);
		if (function_exists('mb_substr')){
			//mb_substr按字来截取
			$newstr = trim(mb_substr($content, 0, $length, 'UTF-8'));
		 } elseif (function_exists('iconv_substr')) {
			//iconv_substr函数是针对中文的|||||mb_strcut按字节来截取不会产生半个字节的现象
			$newstr = trim(iconv_substr($content, 0, $length, 'UTF-8'));
		 } else {
			//substr有可能会出现乱码情况
			$newstr = trim(substr($content,0,$length));
		 }
		 if($newstr!=$content){
			$newstr = strip_tags($newstr,$tags)."...";
		 }
	}
	$newstr = str_replace("&nbsp;","",$newstr);
	return $newstr;
}

//验证手机号码格式
function isMobile($mobile) {
    if (!is_numeric($mobile)) {
        return false;
    }
    /*return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;*/
    return preg_match('/^1[34578]\d{9}$/', $mobile) ? true : false;
}

//验证时间格式
function checkDateTime($date, $format = "Y-m-d H:i"){
	//缺陷：以下判断方法对一般的要求足够了，但不是非常严格，对于 2010-03-00 或 2010-02-31 这种格式的日期也会返回 true
	$unixTime_1 = strtotime($date);
	//如果不是数字格式，则直接返回 
    if(!is_numeric($unixTime_1)){
		return false;
	}
    $checkDate = date($format, $unixTime_1); 
    $unixTime_2 = strtotime($checkDate); 
    if($unixTime_1 == $unixTime_2){ 
        return true; 
    }
	return false;
}

/**
 * 
 *函数名称:encrypt
 *函数作用:加密解密字符串
 *加密     :encrypt('str','E','123245');
 *解密     :encrypt('被加密过的字符串','D','123245');
 *$string   :需要加密解密的字符串
 *$operation:判断是加密还是解密:E:加密   D:解密
 *$key      :加密的钥匙(密匙);
*********************************************************************/
function _encrypt($string,$operation,$key=''){
    $key = $key ? $key : C('encrypt_key');
    $key=md5($key);
    $key_length=strlen($key);
    $string=$operation=='D'?base64_decode($string):substr(md5($string.$key),0,16).$string;
    $string_length=strlen($string);
    $rndkey=$box=array();
    $result='';
    for($i=0;$i<=255;$i++)
    {
    $rndkey[$i]=ord($key[$i%$key_length]);
    	$box[$i]=$i;
    }
    for($j=$i=0;$i<256;$i++)
    {
    	$j=($j+$box[$i]+$rndkey[$i])%256;
    	$tmp=$box[$i];
    	$box[$i]=$box[$j];
    	$box[$j]=$tmp;
    }
    for($a=$j=$i=0;$i<$string_length;$i++)
    {
    	$a=($a+1)%256;
    	$j=($j+$box[$a])%256;
    	$tmp=$box[$a];
    	$box[$a]=$box[$j];
    	$box[$j]=$tmp;
    	$result.=chr(ord($string[$i])^($box[($box[$a]+$box[$j])%256]));
    }
    if($operation=='D')
    {
		if(substr($result,0,16)==substr(md5(substr($result,16).$key),0,16))
		{
			return substr($result,16);
		}else{
			return'';
		}
	}else{
		return str_replace('=','',base64_encode($result));
	}
}

// 隐藏部分字符串
function func_substr_replace($str, $start = 1, $length = 3, $replacement = '*') {
    $len = mb_strlen($str,'utf-8');
    if ($len > intval($start+$length)) {
        $str1 = mb_substr($str,0,$start,'utf-8');
        $str2 = mb_substr($str,intval($start+$length),NULL,'utf-8');
    } else {
        $str1 = mb_substr($str,0,1,'utf-8');
        $str2 = mb_substr($str,$len-1,1,'utf-8');    
        $length = $len - 2;        
    }
    $new_str = $str1;
    for ($i = 0; $i < $length; $i++) { 
        $new_str .= $replacement;
    }
    $new_str .= $str2;
    return $new_str;
}

function GetFileName(){
	$filename = time().'_'.rand(111111,999999);
	return $filename;
}

function time_tranx($the_time){  
   $now_time = date("Y-m-d H:i:s",time());  
   $now_time = strtotime($now_time);  
   $show_time = strtotime($the_time);  
   $dur = $now_time - $show_time;  
   if($dur < 0){  
		$dur = abs($dur);

		if($dur < 60){
			return $dur.'秒后';
		} else{
			if($dur < 3600){  
				return floor($dur/60).'分钟后';  
			}else{  
				if($dur < 86400){  
					return floor($dur/3600).'小时后';  
				}else{  
					if($dur < (864000*3)){ //30天内  
						return floor($dur/86400).'天后';  
					}else{  
						return date('Y-m-d', $show_time);  
					}
				}  
			}
		}
   }else{
   		if($dur < 60){
   			return '刚刚';
   		} else{
	        if($dur < 3600){  
	          	return floor($dur/60).'分钟前';  
	         }else{  
	          	if($dur < 86400){  
	             	return floor($dur/3600).'小时前';  
	          	}else{  
	               	if($dur < 864000){ //10天内  
	                    return floor($dur/86400).'天前';  
	               	}else{  
	                    return date('Y-m-d', $show_time);  
	               	}  
	          	}  
	        }  
		}
   }  
}

function DiffDate($date1, $date2) { 
  	if (strtotime($date1) > strtotime($date2)) { 
	    $ymd = $date2; 
	    $date2 = $date1; 
	    $date1 = $ymd; 
	} 
	list($y1, $m1, $d1) = explode('-', $date1); 
	list($y2, $m2, $d2) = explode('-', $date2); 
	$y = $m = $d = $_m = 0; 
	$math = ($y2 - $y1) * 12 + $m2 - $m1;
	$y = floor($math / 12); 
	$m = intval($math % 12); 
	$d = (mktime(0, 0, 0, $m2, $d2, $y2) - mktime(0, 0, 0, $m2, $d1, $y2)) / 86400; 
	if ($d < 0) { 
	    $m -= 1; 
	    $d += date('j', mktime(0, 0, 0, $m2, 0, $y2)); 
	} 
	$m < 0 && $y -= 1; 
	return array($y, $m, $d); 
} 

function alert($str, $link = ""){
	if(empty($link)){
		echo "<script>alert('".$str."');</script>";
	} else{
		echo "<script>alert('".$str."');window.location.href='".$link."';</script>";
	}
    exit;
}

function alert_back($str){
	echo "<script>alert('".$str."');history.back();</script>";
	exit;
}

function valid_date($date){
    //匹配日期格式
    if (preg_match ("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $date, $parts)){
        //检测是否为日期,checkdate为月日年
        if(checkdate($parts[2],$parts[3],$parts[1])){
            return true;	
        }
    }
    
    return false;
}

function getNumberFormat($num){
	if(!is_numeric($num)){
		return 0;
	}

	if(($num*100%100) == 0){
		return intval($num);
	} else if(($num*100%10) == 0){
		return round($num,1);
	} else{
		return round($num,2);
	}
}

function getIntValue($value, $default = 0){
    if($value){
        return $value;
    }
    return $default;
}

function getDecimalValue($value, $default = 0){
    if($value){
        return $value;
    }
    return $default;
}

function getStringValue($value, $default = ""){
    if($value){
        return $value;
    }
    return $default;
}

function modifyDateTime($date, $number = 0, $format = null, $type = 'month', $prefix = '+'){
    if(empty($date)){
        return new \DateTime();
    }

    $date->modify($prefix.$number.' '.$type);

    if(empty($format)){
        return $date;
    }
    return $date->format($format);
}

function calc($num1, $num2, $symbol){
	if($symbol == '+'){
		return $num1 + $num2;
	}
	if($symbol == '-'){
		return $num1 - $num2;
	}
	if($symbol == '*'){
		return $num1 * $num2;
	}
	if($symbol == '/'){
		return $num1 / $num2;
	}

	return 0;
}

/**
 * 随机字符
 * @param number $length 长度
 * @param string $type 类型
 * @param number $convert 转换大小写
 * @return string
 */
function random($length=6, $type='string', $convert=0){
    $config = array(
        'number'=>'1234567890',
        'letter'=>'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'string'=>'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789',
        'all'=>'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
    );
 
    if(!isset($config[$type])) $type = 'string';
    $string = $config[$type];
 
    $code = '';
    $strlen = strlen($string) -1;
    for($i = 0; $i < $length; $i++){
        $code .= $string{mt_rand(0, $strlen)};
    }
    if(!empty($convert)){
        $code = ($convert > 0)? strtoupper($code) : strtolower($code);
    }
    return $code;
}

//获取ip
function getClientIP(){
	$cip = "unknown";
	if($_SERVER["REMOTE_ADDR"]){
		$cip = $_SERVER["REMOTE_ADDR"];
	} else if(getenv("REMOTE_ADDR")){
		$cip = getenv("REMOTE_ADDR");
	}
	return $cip;
}

//返回用户状态
function getUserStatus($status){
	$str = "禁用";
	switch($status){
		case 200: $str = "正常"; break;
		case 300: $str = "锁定"; break;
		case 400: $str = "注销"; break;
		case 500: $str = "异常"; break;
	}
	return $str;
}

//返回性别
function getGender($gender){
	$str = "保密";
	switch($gender){
		case 1: $str = "男"; break;
		case 2: $str = "女"; break;
	}
	return $str;
}

//返回支付类型
function getPayType($type){
	$str = "未知";
	switch($type){
		case 1: $str = "微信"; break;
		case 2: $str = "支付宝"; break;
	}
	return $str;
}

//返回课程订单状态
function getOrderClassStatus($status, $paystatus){
	$str = "未知";
	switch($status){
		case -1:
			$str = "已删除";
			break;
		case 0: 
			$str = "待确认";
			break;
		case 1:
			if($paystatus == 3){
				$str = "已付款";
			} else{
				$str = "已确认";
			}
			break;
		case 2:
			$str = "已取消";
			break;
		case 4:
			if($paystatus == 1){
				$str = "已完成";
			}
			break;
		case 5:
			$str = "申请退款";
			break;
		case 6:
			$str = "已退款";
			break;
	}
	return $str;
}

function alert_close($str){
    echo "<script>alert('".$str."');window.opener=null;window.open('','_self');window.close();</script>";
    exit;
}

/**
 * 计算年龄
 * @param  $birthday 出生时间
 **/
function getAge($birthday){
    $age = 0;
    if(!empty($birthday)){
        $age = strtotime($birthday);
        if($age === false){
            return 0;
        }

        list($y1,$m1,$d1) = explode("-",date("Y-m-d", $age));

        list($y2,$m2,$d2) = explode("-",date("Y-m-d"), time());

        $age = $y2 - $y1;
        if((int)($m2.$d2) < (int)($m1.$d1)){
            $age -= 1;
        }
    }
    return $age;
}

/**
 * 计算年龄包括月份
 * @param  $birthday 出生时间
 **/
function getAgeMonth($date,$tags='-'){
	$date1= explode($tags,date('Y-m-d'));
	$date2 = explode($tags,$date);
	$month = abs($date1[0] - $date2[0]) * 12 + ($date1[1] - $date2[1]);
	$age=intval($month/12);
	$agemonth=$month%12;
	$agestr = $age.'岁';
	if($agemonth>=0){
		$agestr.=$agemonth.'个月';
	}
	return $agestr;
}

//返回消息时间格式
function getMessageDateFormat($time, $date_format = "Y/m/d日", $time_format = "H:i"){
	if(is_string($time)){
		$time = strtotime($time);
	}

	$w = date("w", $time);
	switch ($w) {
		case 0:
			$week = " 星期日 ";
			break;
		case 1:
			$week = " 星期一 ";
			break;
		case 2:
			$week = " 星期二 ";
			break;
		case 3:
			$week = " 星期三 ";
			break;
		case 4:
			$week = " 星期四 ";
			break;
		case 5:
			$week = " 星期五 ";
			break;
		case 6:
			$week = " 星期六 ";
			break;
		default:
			$week = " ";
			break;
	}
	$a = date("a", $time);
	switch ($a) {
		case 'am':
			$meridiem = " 上午 ";
			break;
		case 'pm':
			$meridiem = " 下午 ";
			break;
		default:
			$meridiem = " ";
			break;
	}
	
	return date($date_format, $time).$week.date($time_format, $time).$meridiem;
}

//获取时间周期
function getWeek($time){
	if(is_string($time)){
		$time = strtotime($time);
	}

	$w = date("w", $time);
	switch ($w) {
		case 0:
			$week = " 星期日 ";
			break;
		case 1:
			$week = " 星期一 ";
			break;
		case 2:
			$week = " 星期二 ";
			break;
		case 3:
			$week = " 星期三 ";
			break;
		case 4:
			$week = " 星期四 ";
			break;
		case 5:
			$week = " 星期五 ";
			break;
		case 6:
			$week = " 星期六 ";
			break;
		default:
			$week = " ";
			break;
	}
	
	return $week;
}

//返回信息状态
function getMessageStatus($status){
    $str = "未知";
    switch($status){
        case 1: $str = "未查看"; break;
        case 2: $str = "已查看"; break;
    }
    return $str;
}

//返回信息类型
function getMessageType($status){
    $str = "未知";
    switch($status){
        case 1: $str = "订单消息"; break;
        case 2: $str = "其他"; break;
    }
    return $str;
}

//返回用户角色
function getServiceRole($status){
    $str = "未知";
    switch($status){
        case 2: $str = "送餐员"; break;
        case 3: $str = "家护师"; break;
        case 4: $str = "康复师"; break;
        case 5: $str = "医生"; break;
        case 6: $str = "护士"; break;
    }
    return $str;
}

//返回时间类型
function getTimeType($status){
    $str = "";
    switch($status){
        case 0: $str = "分"; break;
        case 1: $str = "小时"; break;
        case 2: $str = "天"; break;
        case 3: $str = "月"; break;
    }
    return $str;
}

//返回专业类型
function getPapersType($type){
    $str = "未知";
    switch($type){
        case 1: $str = "身份证"; break;
        case 2: $str = "健康证"; break;
        case 3: $str = "学历证"; break;
        case 4: $str = "专业证"; break;
        case 5: $str = "从业证"; break;
		case 6: $str = "体检表"; break;
    }
    return $str;
}

//返回餐次
function getMealLevel($type){
    $str = "未知";
    switch($type){
        case 1: $str = "中"; break;
        case 2: $str = "晚"; break;
        case 3: $str = "中晚"; break;
    }
    return $str;
}

/**
 *    身份证验证
 *
 *    @param    string    $id
 *    @return   boolean
 */
function is_idcard($id)
{
    $id = strtoupper($id);
    $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
    $arr_split = array();
    if(!preg_match($regx, $id))
    {
        return FALSE;
    }
    if(15==strlen($id)) //检查15位
    {
        $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";

        @preg_match($regx, $id, $arr_split);
        //检查生日日期是否正确
        $dtm_birth = "19".$arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
        if(!strtotime($dtm_birth))
        {
            return FALSE;
        } else {
            return TRUE;
        }
    }
    else           //检查18位
    {
        $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
        @preg_match($regx, $id, $arr_split);
        $dtm_birth = $arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
        if(!strtotime($dtm_birth))  //检查生日日期是否正确
        {
            return FALSE;
        }
        else
        {
            //检验18位身份证的校验码是否正确。
            //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
            $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
            $arr_ch = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
            $sign = 0;
            for ( $i = 0; $i < 17; $i++ )
            {
                $b = (int) $id{$i};
                $w = $arr_int[$i];
                $sign += $b * $w;
            }
            $n  = $sign % 11;
            $val_num = $arr_ch[$n];
            if ($val_num != substr($id,17, 1))
            {
                return FALSE;
            }
            else
            {
                return TRUE;
            }
        }
    }
}

/**
 *    身份证和生日验证
 *
 *    @param    string    $id
 *    @param    string    $birth
 *    @return   boolean
 */
function check_idcard_birth($id, $birth){

	$arr_split = array();
	
	if(15==strlen($id)) //15位
    {
        $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";

		@preg_match($regx, $id, $arr_split);
		
        //生日日期
        $dtm_birth = "19".$arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
        
    }
    else //18位
    {
		$regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
		
		@preg_match($regx, $id, $arr_split);
		
		//生日日期
        $dtm_birth = $arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
	}
	
	//检查生日日期是否正确
	if(strtotime($dtm_birth) && strtotime($dtm_birth) == strtotime($birth))
	{
		return TRUE;
	}

	return FALSE;
}
/* 排序
 * $array array 	数组 需要排序的数组
 * $field string 	字段  根据这个字段排序
 * $sort  string 	升序还是倒叙  asc升序 
 */
function orderby(&$array,$field,$sort){
	for ($i = 0; $i < count($array) ; $i++) {
		for ($j = $i+1; $j < count($array); $j++) {
			if($sort == 'desc'){
				if ($array[$i][$field] < $array[$j][$field]) {
				    $tem = $array[$i]; 
				    $array[$i] = $array[$j]; 
				    $array[$j] = $tem; 
				}
			}elseif('asc'){
				if ($array[$i][$field] > $array[$j][$field]) {
				    $tem = $array[$i]; 
				    $array[$i] = $array[$j];
				    $array[$j] = $tem; 
				}
			}
	    }        
	}
	return array('updatetime'=>date('Y-m-d H:i:s'));
}

function check_hkid($id)
{
    if (!preg_match("/^([A-Z]\d{6,10}(\(\w{1}\))?)$/", $id)) {
        return false;
    }
    return true;
}

//检查路径是否含有http://或https://
function is_http($link){
	if(!empty($link) && (strpos(strtolower($link), 'http://') === false && strpos(strtolower($link), 'https://') === false)){
		//无http
		return false;
	}else{
		//有http
		return true;
	}
}
function imgtobase64($img=''){
	$imageInfo = getimagesize($img);
	$base64 = "" . chunk_split(base64_encode(file_get_contents($img)));
	$head = 'data:' . $imageInfo['mime'] . ';base64,';
	return array('head'=>$head,'content'=>$base64);
}
//https请求(支持GET和POST)
function http_request($url,$data = null){
	$curl = curl_init();
	/* curl_setopt($curl,CURLOPT_SAFE_UPLOAD,false); */
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
	if(!empty($data)){
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	}
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$output = curl_exec($curl);
	//var_dump(curl_error($curl));
	curl_close($curl);
	return $output;
}
//application/json请求
function send_json($url,$data){
	$headers = array("Content-type: application/json;charset=\'utf-8\'","Accept: application/json","Cache-Control: no-cache","Pragma: no-cache");
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60); //设置超时
	if(0 === strpos(strtolower($url), 'https')) {
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //对认证证书来源的检查
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); //从证书中检查SSL加密算法是否存在
	}
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data,JSON_UNESCAPED_UNICODE)); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
	$return = curl_exec($ch);//CURLOPT_RETURNTRANSFER 不设置  curl_exec返回TRUE 设置  curl_exec返回json(此处) 失败都返回FALSE
	curl_close($ch);
	return $return;
}