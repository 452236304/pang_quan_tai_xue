<?php
namespace CApi\Model;

class UserFollowModel extends CommonModel
{

    /**
     * Notes: 关注用户id集合
     * User: dede
     * Date: 2020/2/26
     * Time: 3:17 下午
     * @param $user_id
     * @return mixed
     */
    public function followUserID($user_id){
        $where = [
            'user_id' => $user_id
        ];
        return $this->where($where)->getField('follow_user_id', true);
    }

    /**
     * Notes: 获取用户关注状态
     * User: dede
     * Date: 2020/2/26
     * Time: 3:18 下午
     * @param $user_id
     * @param $follow_user_id
     */
    public function concerned($user_id, $follow_user_id){
        $where = [
            'user_id' => $user_id,
            'follow_user_id' => $follow_user_id
        ];
        return $this->where($where)->count();
    }

    /**
     * Notes: 用户粉丝列表
     * User: dede
     * Date: 2020/3/10
     * Time: 9:53 下午
     * @param $user_id
     * @param int $offset
     * @param int $limit
     */
    public function fans($user_id, $offset = 0, $limit = 10){
        $where = ['follow_user_id' => $user_id];
        $data['total'] = $this->alias('UF')
            ->join('__USER__ AS U ON U.id = UF.user_id')
            ->where($where)
            ->count();
        $data['rows'] = $this->alias('UF')
            ->join('__USER__ AS U ON U.id = UF.user_id')
            ->where($where)
            ->order('UF.add_time DESC')
            ->limit( $offset . ',' . $limit )
            ->select();
        return $data;
    }

    /**
     * Notes: 用户关注列表
     * User: dede
     * Date: 2020/3/10
     * Time: 9:53 下午
     * @param $user_id
     * @param int $offset
     * @param int $limit
     */
    public function follow($user_id, $group_id = 0, $offset = 0, $limit = 10){
        $where = ['UF.user_id' => $user_id];
        if( $group_id ){
            $where['UF.group_id'] = $group_id;
        }
        $data['total'] = $this->alias('UF')
            ->join('__USER_GROUP__ UG ON UF.user_id = UG.user_id', 'left')
            ->join('__USER__ AS U ON U.id = UF.user_id')
            ->where($where)
            ->count();
        $data['rows'] = $this->alias('UF')
            ->join('__USER_GROUP__ UG ON UF.user_id = UG.user_id', 'left')
            ->join('__USER__ AS U ON U.id = UF.user_id')
            ->where($where)
            ->order('UF.add_time DESC')
            ->limit( $offset . ',' . $limit )
            ->select();
        return $data;
    }

}