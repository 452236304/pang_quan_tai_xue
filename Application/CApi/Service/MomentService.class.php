<?php
namespace  CApi\Service;

class MomentService
{
    /**
     * Notes: 发布动态
     * User: dede
     * Date: 2020/2/25
     * Time: 9:59 上午
     * @param $user_id
     * @param $data
     * @return mixed
     */
    public function publish($user_id, $data){
        $data['user_id'] = $user_id;
        $moment_id = D('Moment')->addOne($data);
        D('Topic', 'Service')->filterTopic($moment_id, $data['content']);
        if( $moment_id && $data['draft'] ){
            D('User', 'Service')->momentInc($user_id);
        }
        return $moment_id;
    }

    /**
     * Notes: 批量查询动态信息
     * User: dede
     * Date: 2020/2/26
     * Time: 5:57 下午
     * @param $moments
     * @param int $offset
     * @param int $limit
     */
    public function batch($moments, $user_id = 0, $offset =  0, $limit = 10){
        $moment = D('Moment')->batch($moments, $offset, $limit);
        foreach ( $moment as &$item ){
            $item = $this->listFormat($item);
            // 获取关注状态
            if( $user_id ){
                $concerned = D('UserFollow')->concerned($user_id, $item['author_id']);
                $item['concerned'] = $concerned;
            }else{
                $item['concerned'] = 0;
            }
        }
        return $moment;
    }

    /**
     * Notes: 关注用户动态列表（包含推荐的动态）
     * User: dede
     * Date: 2020/2/26
     * Time: 2:48 下午
     * @param $user_id
     */
    public function followList($user_id, $offset = 0, $limit = 10){
        $follow = D('UserFollow')->followUserID($user_id);
        if( $follow ){
            $moments = D('Moment')->followList($follow, $offset, $limit);
        }else{
            $moments = D('Moment')->recommend([], $offset, $limit);
        }
        foreach ( $moments['rows'] as &$item){

            $item = $this->listFormat($item);
            // 获取关注状态
            $concerned = D('UserFollow')->concerned($user_id, $item['author_id']);
            $item['concerned'] = $concerned;
        }
        return $moments;

    }

    /**
     * Notes: 推荐动态列表
     * User: dede
     * Date: 2020/2/26
     * Time: 5:29 下午
     * @param $user_id
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    public function recommend($user_id, $where = [], $offset = 0, $limit = 10){
        $moments = D('Moment')->recommend($where, $offset, $limit);
        foreach ( $moments['rows'] as &$item){
            $item = $this->listFormat($item);
            // 获取关注状态
            if( $user_id ){
                $concerned = D('UserFollow')->concerned($user_id, $item['author_id']);
                $item['concerned'] = $concerned;
            }else{
                $item['concerned'] = 0;
            }
        }
        return $moments;
    }

    /**
     * Notes: 动态列表
     * User: dede
     * Date: 2020/2/26
     * Time: 10:14 下午
     * @param $user_id
     * @param $offset
     * @param $limit
     * @return mixed
     */
    public function getList($user_id, $where = [], $offset, $limit){
        $data = D('Moment')->listed($where, $offset, $limit);
        foreach ( $data['rows'] as &$item){
            $item = $this->listFormat($item);
            // 获取关注状态
            if( $user_id ){
                $concerned = D('UserFollow')->concerned($user_id, $item['author_id']);
                $item['concerned'] = $concerned;
            }else{
                $item['concerned'] = 0;
            }
        }
        return $data;
    }

    /**
     * Notes: 动态详情
     * User: dede
     * Date: 2020/3/2
     * Time: 12:04 下午
     * @param $moment_id
     * @param int $user_id
     * @return mixed
     * @throws \Think\Exception
     */
    public function details($moment_id, $user_id = 0){
        $moment = D('Moment')->getOne($moment_id);
        if( !$moment ){
            E('请选择要查看的动态');
        }
        // 动态信息
        $moment['content'] = htmlspecialchars_decode($moment['content']);
        $moment['add_time'] = date('Y-m-d', $moment['add_time']);
        $moment['resource'] = explode(',', $moment['resource']);

        // 精选
//        $recommend = explode(',', $moment['recommend']);
//        if( $moment['recommend_type'] == 1 ){     // 优选
//            $moment['recommend'] = D('ServiceProject', 'Service')->batch($recommend);
//        }else if( $moment['recommend_type'] == 2 ){       //好物
//            $moment['recommend'] = D('Product', 'Service')->batch($recommend);
//        }else if( $moment['recommend_type'] == 3 ){       //家护师
//            $moment['recommend'] = D('Product', 'Service')->batch($recommend);
//        }
//        sort($moment['recommend']);

        // 发布用户信息
        $user = D('User')->getOne($moment['user_id']);
        $moment['user'] = [
            'user_id' => $user['id'],
            'nickname' => $user['nickname'],
            'avatar' => DoUrlHandle($user['avatar']),
            'fans' => $user['fans'],
        ];

        // 获取关注状态
        if( $user_id ){
            $concerned = D('UserFollow')->concerned($user_id, $moment['user_id']);
            $moment['concerned'] = $concerned;
        }else{
            $moment['concerned'] = 0;
        }

        // 评论
        $moment['comment'] = D('MomentComment', 'Service')->comment($moment_id);

        // 点击量
        $this->readeNum($moment_id);

        // 浏览记录
        if( $user_id ){
            D('MomentHistory', 'Service')->append($user_id, $moment_id);
        }

        unset($moment['sort'], $moment['nav_id']);
        return $moment;
    }

    /**
     * Notes: 大咖秀用户动态列表
     * User: dede
     * Date: 2020/3/2
     * Time: 12:09 下午
     * @param $users
     * @param $offset
     * @param $limit
     * @return mixed
     */
    public function userListMoment($users, $offset, $limit){
        if( !is_array($users) ){
            $users = [$users];
        }
        $data = D('Moment')->userListMoment($users, $offset, $limit);
        foreach ( $data as &$item){
            $item = $this->listFormat($item);
        }
        return $data;
    }

    /**
     * Notes: 动态列表数据格式化
     * User: dede
     * Date: 2020/2/26
     * Time: 5:26 下午
     * @param $data
     * @return array
     */
    public function listFormat($data){
        $data['resource'] = explode(',', $data['resource']);
        $data['resource'] = array_slice($data['resource'], 0, 3);
        foreach ($data['resource'] as &$item){
            $item = DoUrlHandle($item);
        }

        $recommend = [
            'author_id' => $data['user_id'],
            'author' => $data['nickname'],
            'avatar' => DoUrlHandle($data['avatar']),
            'moment_id' => $data['id'],
            'title' => $data['title'],
            'resource_type' => $data['resource_type'],
            'resource' => $data['resource'],
            'add_time' => date('Y-m-d', $data['add_time']),
            'reade_num' => $data['reade_num'],
            'thumbs_num' => $data['thumbs_num'],
            'comment_num' => $data['comment_num'],
            'share_num' => $data['share_num'],
        ];
        return $recommend;
    }

    /**
     * Notes: 阅读量
     * User: dede
     * Date: 2020/2/26
     * Time: 10:44 下午
     * @param $moment_id
     */
    public function readeNum($moment_id){
        $where['id'] = $moment_id;
        D('Moment')->where($where)->setInc('reade_num');
    }

    /**
     * Notes: 分享数量
     * User: dede
     * Date: 2020/2/26
     * Time: 10:45 下午
     * @param $moment_id
     */
    public function shareNum($moment_id){
        $where['id'] = $moment_id;
        D('Moment')->where($where)->setInc('share_num');
    }

    /**
     * Notes: 评论数量
     * User: dede
     * Date: 2020/2/26
     * Time: 10:49 下午
     * @param $moment_id
     */
    public function commentNum($moment_id){
        $where['id'] = $moment_id;
        D('Moment')->where($where)->setInc('comment_num');
    }

    /**
     * Notes: 增加点赞数量
     * User: dede
     * Date: 2020/2/26
     * Time: 10:50 下午
     * @param $moment_id
     */
    public function thumbsNumInc($moment_id, $user_id){
        $key = 'moment_thumbs_' . $moment_id;
        $users = json_decode(S($key), true);
        if( $users ){
            if( in_array($user_id, $users) ){
                return false;
            }
        }else{
            $users = [];
        }
        $where['id'] = $moment_id;
        D('Moment')->where($where)->setInc('thumbs_num');
        array_push($users, $user_id);
        S($key, json_encode($users));
        D('User', 'Service')->thumbsInc($user_id);
    }

    /** 减少点赞数量
     * Notes:
     * User: dede
     * Date: 2020/2/26
     * Time: 10:52 下午
     * @param $moment_id
     */
    public function thumbsNumDec($moment_id, $user_id){
        $key = 'moment_thumbs_' . $moment_id;
        $users = json_decode(S($key), true);
        if( !$users || !in_array($user_id, $users) ){
            return false;
        }
        $where['id'] = $moment_id;
        D('Moment')->where($where)->setDec('thumbs_num');
        $users = array_diff($users, [$user_id]);
        S($key, json_encode($users));
        D('User', 'Service')->thumbsDec($user_id);
    }

    /**
     * Notes: 浏览记录
     * User: dede
     * Date: 2020/3/3
     * Time: 3:24 下午
     * @param $user_id
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    public function history($user_id, $offset = 0, $limit = 10){
        $where = [ 'MH.user_id' => $user_id ];
        $data = D('MomentHistory')->getList($where, $offset, $limit);
        foreach ( $data['rows'] as &$item ){
            $item = $this->listFormat($item);
        }
        return $data;
    }

    /**
     * Notes: 分类动态列表
     * User: dede
     * Date: 2020/3/10
     * Time: 2:15 下午
     * @param $category_id
     * @param int $offset
     * @param int $limit
     */
    public function categoryList($category_id, $user_id = 0, $offset = 0, $limit = 10){
        $where = [
            'category_path' => ['like', '%-' . $category_id . '-%'],
        ];
        $data = D('Moment')->listed($where, $offset, $limit);
        foreach ( $data['rows'] as &$item){
            $item = $this->listFormat($item);
            // 获取关注状态
            if( $user_id ){
                $concerned = D('UserFollow')->concerned($user_id, $item['author_id']);
                $item['concerned'] = $concerned;
            }else{
                $item['concerned'] = 0;
            }
        }
        return $data;
    }

    /**
     * Notes: 我的动态列表
     * User: dede
     * Date: 2020/3/10
     * Time: 2:33 下午
     * @param $user_id
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    public function myMoment($user_id, $offset = 0, $limit = 10){
        $where = [
            'user_id' => $user_id,
        ];
        $data = D('Moment')->listed($where, $offset, $limit);
        foreach ( $data['rows'] as &$item){
            $item = $this->listFormat($item);
        }
        return $data;
    }

    /**
     * Notes: 查看某个用户的动态列表
     * User: dede
     * Date: 2020/3/10
     * Time: 7:04 下午
     * @param $select_user_id
     * @param int $user_id
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    public function userMoment($select_user_id, $resource_type=0, $user_id = 0, $offset = 0, $limit = 10){
        $where = [
            'user_id' => $select_user_id,
        ];
        if( $resource_type ){
            $where['resource_type'] = $resource_type;
        }
        $data = D('Moment')->listed($where, $offset, $limit);
        foreach ( $data['rows'] as &$item){
            $item = $this->listFormat($item);
            // 获取关注状态
            if( $user_id ){
                $concerned = D('UserFollow')->concerned($user_id, $item['author_id']);
                $item['concerned'] = $concerned;
            }else{
                $item['concerned'] = 0;
            }
        }
        return $data;
    }

    /**
     * Notes: 草稿箱
     * User: dede
     * Date: 2020/3/10
     * Time: 9:48 下午
     * @param $user_id
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    public function draft($user_id, $offset= 0 , $limit = 10){
        $data = D('Moment')->draft($user_id, $offset, $limit);
        foreach ( $data['rows'] as &$item){
            $item = $this->listFormat($item);
        }
        return $data;
    }


    /**
     * Notes: 一周获赞榜
     * User: dede
     * Date: 2020/3/25
     * Time: 2:34 下午
     * @param int $offset
     * @param int $limit
     */
    public function thumbsTop($offset= 0 , $limit = 5){
        $field = 'U.id, U.nickname, U.avatar, SUM(thumbs_num) AS number';
        $last_start = $this->lastWeekStart();
        $where = [
            'M.status' => 1,
            'M.draft' => 0,
            'M.del_time' => 0,
            'M.add_time' => [
                ['egt', $last_start],
                ['lt', $last_start + 7*86400],
            ]
        ];
        $data = D('User')->alias('U')
            ->join('sj_moment AS M ON U.id = M.user_id')
            ->where($where)
            ->field($field)
            ->group('user_id')
            ->order('SUM(thumbs_num) DESC')
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
     * Notes: 上周热议榜
     * User: dede
     * Date: 2020/3/25
     * Time: 3:02 下午
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function commentTop($offset= 0 , $limit = 5){
        $field = 'U.id, U.nickname, U.avatar, SUM(comment_num) AS number';
        $last_start = $this->lastWeekStart();
        $where = [
            'M.status' => 1,
            'M.draft' => 0,
            'M.del_time' => 0,
            'M.add_time' => [
                ['egt', $last_start],
                ['lt', $last_start + 7*86400],
            ]
        ];
        $data = D('User')->alias('U')
            ->join('sj_moment AS M ON U.id = M.user_id')
            ->where($where)
            ->field($field)
            ->group('user_id')
            ->order('SUM(comment_num) DESC')
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
     * Notes: 上周分享榜
     * User: dede
     * Date: 2020/3/25
     * Time: 3:06 下午
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function shareTop($offset= 0 , $limit = 5){
        $field = 'U.id, U.nickname, U.avatar, SUM(share_num) AS number';
        $last_start = $this->lastWeekStart();
        $where = [
            'M.status' => 1,
            'M.draft' => 0,
            'M.del_time' => 0,
            'M.add_time' => [
                ['egt', $last_start],
                ['lt', $last_start + 7*86400],
            ]
        ];
        $data = D('User')->alias('U')
            ->join('sj_moment AS M ON U.id = M.user_id')
            ->where($where)
            ->field($field)
            ->group('user_id')
            ->order('SUM(share_num) DESC')
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
     * Notes: 获取上周开始时间
     * User: dede
     * Date: 2020/3/25
     * Time: 2:49 下午
     * @param int $first
     * @return false|int
     */
    public function lastWeekStart($first = 1){
        $defaultDate = date("Y-m-d");
        $w = date('w',strtotime($defaultDate));
        $week_start = strtotime("$defaultDate -".($w ? $w - $first : 6).' days');
        $last_start = $week_start - 7 * 86400;  //上周开始日期
        return $last_start;

    }


    /**
     * Notes: 添加收藏
     * User: dede
     * Date: 2020/3/26
     * Time: 12:29 上午
     * @param $user_id
     * @param $moment_id
     */
    public function appendCollect($user_id, $moment_id){
        $moment = D('Moment')->find($moment_id);
        if( !$moment || $moment['del_time'] ){
            E('不存在的动态');
        }
        $res = $this->collect($user_id, $moment_id);
        if( $res ){
            E('已收藏');
        }
        $data = [
            'user_id' => $user_id,
            'moment_id' => $moment_id,
        ];
        $res = D('MomentCollect')->addone($data);
        return $res;
    }

    /**
     * Notes: 取消收藏
     * User: dede
     * Date: 2020/3/26
     * Time: 12:35 上午
     * @param $user_id
     * @param $moment_id
     * @return mixed
     * @throws \Think\Exception
     */
    public  function removeCollect($user_id, $moment_id){
        $moment = D('Moment')->find($moment_id);
        if( !$moment || $moment['del_time'] ){
            E('不存在的动态');
        }
        $res = $this->collect($user_id, $moment_id);
        if( $res ){
            E('未收藏');
        }
        $where = [
            'user_id' => $user_id,
            'moment_id' => $moment_id,
        ];
        $res = D('MomentCollect')->where($where)->delete();
        return $res;
    }

    /**
     * Notes: 查询收藏状态
     * User: dede
     * Date: 2020/3/26
     * Time: 12:35 上午
     * @param $user_id
     * @param $moment_id
     * @return mixed
     */
    public function collect($user_id, $moment_id){
        $where = [
            'user_id' => $user_id,
            'moment_id' => $moment_id,
        ];
        $res = D('MomentCollect')->where($where)->count();
        return $res;
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
        $data = D('Moment')->collectList($user_id, $group_id, $offset, $limit);
        foreach ( $data['rows'] as &$item ){
            $item = $this->listFormat($item);
        }
        return $data;
    }

    /**
     * Notes: 动态分组列表
     * User: dede
     * Date: 2020/7/20
     * Time: 11:56 上午
     * @param $user_id
     * @param $group_id
     * @param $offset
     * @param $limit
     * @return mixed
     */
    public function groupList($user_id, $group_id, $offset, $limit){
        $data = D('Moment')->groupList($user_id, $group_id, $offset, $limit);
        foreach ( $data['rows'] as &$item ){
            $item = $this->listFormat($item);
        }
        return $data;
    }

    /**
     * Notes: 查询点赞状态
     * User: dede
     * Date: 2020/3/26
     * Time: 9:48 上午
     * @param $user_id
     * @param $moment_id
     */
    public function thumbs($user_id, $moment_id){
        $key = 'moment_thumbs_' . $moment_id;
        $users = json_decode(S($key), true);
        if( !$users || !in_array($user_id, $users) ){
            return false;
        }else{
            return true;
        }
    }

    public function banner($category_id){

    }

}