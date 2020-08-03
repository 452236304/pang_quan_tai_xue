<?php
namespace CApi\Model;

class MomentModel extends CommonModel
{

    /**
     * Notes: 动态列表
     * User: dede
     * Date: 2020/2/26
     * Time: 10:12 下午
     * @param $offset
     * @param int $limit
     * @return mixed
     */
    public function listed($where, $offset = 0, $limit = 10){
        $where['del_time'] = 0;
        $where['draft'] = 0;
        $where['M.status'] = 1;
        $data['total'] = $this->alias('M')
            ->join('__USER__ AS U ON U.id = M.user_id')
            ->where($where)
            ->count();
        $field = ['U.nickname', 'U.avatar', 'M.*'];
        $data['rows'] = $this->alias('M')
            ->join('__USER__ AS U ON U.id = M.user_id')
            ->where($where)
            ->field($field)
            ->order('is_top desc, add_time desc')
            ->limit( $offset . ',' . $limit )
            ->select();
        return $data;
    }

    /**
     * Notes: 收藏动态列表
     * User: dede
     * Date: 2020/3/26
     * Time: 12:47 上午
     * @param $user_id
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    public function collectList($user_id, $group_id = 0, $offset = 0, $limit = 10){
        $where = [
            'MC.user_id' => $user_id,
            'M.del_time' => 0,
            'M.status' => 1,
            'M.draft' => 0,
        ];
        if( $group_id ){
            $where['MC.group_id'] = $group_id;
        }
        $data['total'] = $this->alias('M')
            ->join('__MOMENT_COLLECT__ AS MC ON MC.moment_id = M.id')
            ->join('__USER__ AS U ON U.id = M.user_id')
            ->where($where)
            ->count();
        $field = ['U.nickname', 'U.avatar', 'M.*'];
        $data['rows'] = $this->alias('M')
            ->join('__MOMENT_COLLECT__ AS MC ON MC.moment_id = M.id')
            ->join('__USER__ AS U ON U.id = M.user_id')
            ->where($where)
            ->field($field)
            ->order('is_top desc, add_time desc')
            ->limit($offset . ',' . $limit)
            ->select();
        if( !$data['total'] ){
           $data['rows'] = [];
        }
        return $data;
    }

    /**
     * Notes: 收藏动态分组列表
     * User: dede
     * Date: 2020/3/26
     * Time: 12:47 上午
     * @param $user_id
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    public function groupList($user_id, $group_id, $offset = 0, $limit = 10){
        $where = [
            'MC.user_id' => $user_id,
            'MC.group_id' => $group_id,
            'M.del_time' => 0,
            'M.status' => 1,
            'M.draft' => 0,
        ];
        $data['total'] = $this->alias('M')
            ->join('__MOMENT_COLLECT__ AS MC ON MC.moment_id = M.id')
            ->join('__USER__ AS U ON U.id = M.user_id')
            ->where($where)
            ->count();
        $field = ['U.nickname', 'U.avatar', 'M.*'];
        $data['rows'] = $this->alias('M')
            ->join('__MOMENT_COLLECT__ AS MC ON MC.moment_id = M.id')
            ->join('__USER__ AS U ON U.id = M.user_id')
            ->where($where)
            ->field($field)
            ->order('is_top desc, add_time desc')
            ->limit($offset . ',' . $limit)
            ->select();
        return $data;
    }

    /**
     * Notes: 草稿箱
     * User: dede
     * Date: 2020/3/10
     * Time: 9:44 下午
     * @param $user_id
     * @param $offset
     * @param $limit
     * @return mixed
     */
    public function draft($user_id, $offset, $limit){
        $where = [
            'user_id' => $user_id,
            'draft' => 1,
        ];
        $data['total'] = $this->where($where)->count();
        $data['rows'] = $this->alias('M')
            ->where($where)
            ->order('add_time desc')
            ->limit( $offset . ',' . $limit )
            ->select();
        return $data;
    }


    /**
     * Notes: 获取指定用户动态列表
     * User: dede
     * Date: 2020/2/26
     * Time: 3:02 下午
     * @param $follow
     * @param $offset
     * @param $limit
     * @return mixed
     */
    public function followList($follow, $offset = 0, $limit = 10){
        if( !is_array($follow) ){
            $follow = [$follow];
        }
        $where = [
            'user_id' => ['IN', $follow],
            'M.del_time' => 0,
        ];
        $data['total'] = $this->alias('M')
            ->join('__USER__ AS U ON U.id = M.user_id')
            ->where($where)
            ->count();
        $field = ['U.nickname', 'U.avatar', 'M.*'];
        $data['rows'] = $this->alias('M')
            ->join('__USER__ AS U ON U.id = M.user_id')
            ->field($field)
            ->where($where)
            ->order('add_time desc')
            ->limit( $offset . ',' . $limit )
            ->select();
        return $data;
    }

    //获取话题
    public function getTopic($moment_id){
        $where=[
            'MT.id'=>$moment_id,
        ];
        $topic = M('moment_topic')->alias('MT')
            ->join('sj_topic AS T ON MT.topic_id = T.id')
            ->field('T.title')
            ->where($where)
            ->find();
            return $topic['title'];
         
    }
    /**
     * Notes: 推荐动态列表
     * User: dede
     * Date: 2020/2/26
     * Time: 3:06 下午
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    public function recommend($where, $offset = 0, $limit = 10){
        $where['M.is_top'] = 1;
        $where['M.status'] = 1;
        $where['M.del_time'] = 0;
        $data['total'] = $this->alias('M')
            ->join('__USER__ AS U ON U.id = M.user_id')
            ->where($where)
            ->count();
        $field = ['U.nickname', 'U.avatar', 'M.*'];
        $data['rows'] = $this->alias('M')
            ->join('__USER__ AS U ON U.id = M.user_id')
            ->field($field)
            ->where($where)
            ->order('add_time desc')
            ->limit( $offset . ',' . $limit )
            ->select();
        return $data;
    }

    public function batch($moments, $offset = 0, $limit = 10){
        if( !is_array($moments) ){
            $moments = [$moments];
        }
        $where = [
            'M.id' => ['IN', $moments],
            'M.del_time' => 0,
        ];
        $field = 'M.id, U.nickname, U.avatar, M.*';
        $data = $this->alias('M')
            ->join('__USER__ AS U ON U.id = M.user_id')
            ->where($where)
            ->order('add_time desc')
            ->limit( $offset . ',' . $limit )
            ->getField($field, true);
        $item = [];
        foreach ($moments as $id){
            if( $data[$id] ){
                $item[$id] = $data[$id];
            }
        }
        return $item;
    }

    public function getOne($id)
    {
        $where['del_time'] = 0;
        return $this->where($where)->find($id);
    }

    /**
     * Notes: 用户动态列表
     * User: dede
     * Date: 2020/3/2
     * Time: 12:15 下午
     * @param $users
     * @param $offset
     * @param $limit
     * @return array
     */
    public function userListMoment($users, $offset, $limit){
        $where = [
            'U.id' => ['IN', $users]
        ];
        $field = 'U.nickname, U.avatar, M.*';
        $data = $this->alias('M')
            ->join('__USER__ AS U ON U.id = M.user_id')
            ->field($field)
            ->where($where)
            ->order('add_time desc')
            ->limit( $offset . ',' . $limit )
            ->select();
        return $data ? $data : [];
    }

    public function topicListMoment($topic, $offset, $limit){
        $where = [
            'T.id' => ['IN', $topic]
        ];
        $field = 'U.nickname, U.avatar, M.*';
        $data = $this->alias('M')
            ->join('__Moment_Topic__ AS MT ON M.id = M.user_id')
            ->where($where)
            ->order('add_time desc')
            ->limit( $offset . ',' . $limit )
            ->getField($field, true);
        return $data ? $data : [];
    }

}