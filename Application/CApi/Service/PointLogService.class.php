<?php
namespace CApi\Service;

class PointLogService
{
	
    /**
     * Notes: 用户积分调整
     * User: dede
     * Date: 2020/3/9
     * Time: 10:29 上午
     * @param $user_id  用户id
     * @param $point  调整积分数， 扣除为负数
     * @param $data  其他附加参数
     */
    public function append($user_id, $point, $data = []){
		$user=D('user')->find($user_id);
		$map = array('id'=>$user_id);
		if($point>0){
			D('user')->where($map)->setInc('point',$point);
		}elseif($point<0){
			if(($user['point']+$point)<0){
				return false;
			}
			D('user')->where($map)->setDec('point',abs($point));
		}
		$data['user_id'] = $user_id;
		$data['adjust'] = $point;
		$data['before'] = $user['point'];
		$data['after'] = $user['point']+$point;
		$rs = D('UserPointLog')->addOne($data);
		return true;
    }
	
	/**
	 * Notes: 检查该任务的积分领取次数是否达到限制
	 * User: lianghao
	 * Date: 2020/3/10
	 * Time: 17:24 上午
	 * @param $user_id  用户id
	 * @param $tag  tag
	 */
	public function check_tag($user_id,$tag){
		//查询任务
		$map = array('tag'=>$tag);
		$rule = D('point_rule')->where($map)->find();
		
		//查询次数
		$map = array('tag'=>$tag,'user_id'=>$user_id);
		if($rule['times']>0){ //每个账号能完成次数
			$condition=$rule['times'];
		}elseif($rule['everyday_times']>0){ //每个账号每天能完成次数
			$map['add_time']=array('gt',strtotime(date('Y-m-d')));
			$condition=$rule['everyday_times'];
		}else{
			return true;
		}
		$count=D('user_point_log')->where($map)->count();
		if($condition <= $count){
			return false;
		}else{
			return true;
		}
	}
	
	/**
	 * Notes: 该任务剩余可完成次数和可完成次数
	 * User: lianghao
	 * Date: 2020/6/29
	 * Time: 17:05 
	 * @param $user_id  用户id
	 * @param $tag  tag
	 */
	public function tag_surplus($user_id,$tag){
		//查询任务
		$map = array('tag'=>$tag);
		$rule = D('point_rule')->where($map)->find();
		
		//查询次数
		$map = array('tag'=>$tag,'user_id'=>$user_id);
		if($rule['times']>0){ //每个账号能完成次数
			$condition = $rule['times'];
		}elseif($rule['everyday_times']>0){ //每个账号每天能完成次数
			$map['add_time'] = array('gt',strtotime(date('Y-m-d')));
			$condition = $rule['everyday_times'];
		}else{
			$condition = 999;
		}
		$count=D('user_point_log')->where($map)->count();
		return array('max'=>$condition,'count'=>$count);
	}
}