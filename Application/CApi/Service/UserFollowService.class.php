<?php
namespace CApi\Service;

class UserFollowService
{
    /**
     * Notes: 添加关注
     * User: dede
     * Date: 2020/3/3
     * Time: 4:46 下午
     * @param $user_id
     * @param $follow_user_id
     * @throws \Think\Exception
     */
    public function append($user_id, $follow_user_id){
        // 检查被关注用户
        $follow_user = D('User')->find($follow_user_id);
        if( !$follow_user ){
            E('关注用户账号不存在');
        }
        if($follow_user["status"] == 300){
            E("关注用户账号已经被锁定");
        }
        if($follow_user["status"] == 400){
            E("关注用户账号已经被注销");
        }
        if($follow_user["status"] == 500){
            E("关注用户账号状态异常");
        }
        if($follow_user["status"] == 0){
            E("关注用户账号已经被禁用");
        }
        $where = [
            'user_id' => $user_id,
            'follow_user_id' => $follow_user_id,
        ];
        $res = D('UserFollow')->where($where)->count();
        if( $res ){
            E("您已关注该账户，请勿重复操作！");
        }
        $data = [
            'user_id' => $user_id,
            'follow_user_id' => $follow_user_id
        ];
        $res = D('UserFollow')->addOne($data);
        return $res;
    }

    /**
     * Notes: 取消关注
     * User: dede
     * Date: 2020/3/3
     * Time: 5:18 下午
     * @param $user_id
     * @param $follow_user_id
     * @return mixed
     * @throws \Think\Exception\
     */
    public function del($user_id, $follow_user_id){
        $where = [
            'user_id' => $user_id,
            'follow_user_id' => $follow_user_id,
        ];
        $res = D('UserFollow')->where($where)->count();
        if( !$res ){
            E("您还还有关注该账号！");
        }
        $res = D('UserFollow')->where($where)->delete();
        return $res;
    }

    /**
     * Notes: 添加用户到用户组
     * User: dede
     * Date: 2020/3/3
     * Time: 4:49 下午
     * @param $user_id
     * @param $follow_user_id
     * @param $group_id
     * @return bool
     */
    public function appendGroup($user_id, $follow_user_id, $group_id){
        $where = [
            'user_id' => $user_id,
            'follow_user_id' => $follow_user_id,
        ];
        $data = [
            'group_id' => $group_id
        ];
        $res  = D('UserFollow')->where($where)->save($data);
        return $res;
    }

    /**
     * Notes: 从用户分组中移除用户
     * User: dede
     * Date: 2020/3/26
     * Time: 12:12 上午
     * @param $user_id
     * @param $follow_user_id
     * @param $group_id
     * @return bool
     */
    public function removeGroup($user_id, $follow_user_id, $group_id){
        $where = [
            'user_id' => $user_id,
            'follow_user_id' => $follow_user_id,
            'group_id' => $group_id
        ];
        $data = [
            'group_id' => 0
        ];
        $res  = D('UserFollow')->where($where)->save($data);
        return $res;
    }

    /**
     * Notes: 上周关注排行榜
     * User: dede
     * Date: 2020/3/25
     * Time: 3:12 下午
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function top($offset= 0 , $limit = 5){
        $field = 'U.id, U.nickname, U.avatar, count(follow_user_id) AS number';
        $last_start = D('Moment', 'Service')->lastWeekStart();
        $where = [
            'status' => 200,
            'UF.add_time' => [
                ['egt', $last_start],
                ['lt', $last_start + 7*86400],
            ]
        ];
        $data = D('User')->alias('U')
            ->join('sj_user_follow AS UF ON u.id = UF.follow_user_id')
            ->where($where)
            ->field($field)
            ->group('follow_user_id')
            ->order('SUM(follow_user_id) DESC')
            ->limit( $offset . ',' . $limit )
            ->select();
        if( $data ){
            foreach ( $data as &$item ){
                $item['avatar'] = DoUrlHandle($item['avatar']);
            }
        }else{
            $data = [];
        }
        return $data;
    }

    /**
     * Notes: 关注用户分组
     * User: dede
     * Date: 2020/3/25
     * Time: 3:28 下午
     * @param $user_id
     */
    public function groupList($user_id){
        $where = ['user_id' => $user_id];
        $field = 'id as group_id, group_name';
        $data = D('UserGroup')->where($where)->field($field)->select();
        return $data;
    }
    
    public function userList($user_id, $group_id, $offset, $limit){
        $data = D('Moment')->groupList($user_id, $group_id, $offset, $limit);
        foreach ( $data['rows'] as &$item ){
            $item = $this->listFormat($item);
        }
        return $data;
    }
	/**
	 * Notes: 判断是否关注
	 * User: dede
	 * Date: 2020/3/25
	 * Time: 3:28 下午
	 * @param $user_id 当前用户ID
	 * @param $follow_id 判断是否关注的用户ID
	 */
	public function check_follow($user_id, $follow_id){
		$map = array('user_id'=>$user_id,'follow_user_id'=>$follow_id);
		$follow=D('UserFollow')->where($map)->find();
		if($follow){
			return true;
		}else{
			return false;
		}
	}
}