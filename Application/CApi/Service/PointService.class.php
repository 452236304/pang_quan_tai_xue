<?php
namespace CApi\Service;

class PointService{
    /**
     * Notes: 做任务获取积分
     * User: dede
     * Date: 2020/3/9
     * Time: 10:30 上午
     * @param $user_id    用户id
     * @param $tag          任务唯一标识
	 * @param $remark       备注
     */
    public function append($user_id, $tag, $remark=''){
        $where = [
            'tag' => $tag,
        ];
        $rule = D('PointRule')->where($where)->find();
		
		if(empty($rule)){
			return false;
		}
		
        $point = $rule['point'];
        // 次数限制筛选
		$check_result=D('PointLog','Service')->check_tag($user_id,$tag);
		if(!$check_result){
			return false;
		}
		
        $data = [
            'tag' => $tag
        ];
		if($remark!=''){
			$data['remark']=$remark;
		}elseif(!empty($tag)){
			$map = array('tag'=>$tag);
			$point_rule=D('point_rule')->field('title')->where($map)->find();
			if($point_rule){
				$data['remark']=$point_rule['title'];
			}
		}
        $res = D('PointLog','Service')->append($user_id,$point,$data);
        return $res;
    }

    /*-------------------------以下为任务规则筛选----------------------------*/

    /*注册*/
    private function _register($user_id){
		
    }
}