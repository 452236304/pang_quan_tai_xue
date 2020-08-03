<?php
namespace CApi\Service;

use function GuzzleHttp\Psr7\str;

class UserService
{

    /**
     * Notes: 关注推荐用户
     * User: dede
     * Date: 2020/2/26
     * Time: 6:29 下午
     * @param $user_id
     * @return array
     */
    public function recommend($user_id){
        $data = D('User')->recommend();
        $recommend = [];
        foreach ($data as &$item){
            // 过滤已关注用户
            $follow = D('UserFollow')->followUserID($user_id);
            if( in_array($item['id'], $follow) ){
                continue;
            }
            $interest = explode(',', $item['interest']);
            $recommend[] = [
                'user_id' => $item['id'],
                'nickname' => $item['nickname'],
                'avatar' => DoUrlHandle($item['avatar']),
                'tag' => $interest[0],
            ];
        }
        return $recommend;
    }

    /**
     * Notes: 粉丝数排行榜
     * User: dede
     * Date: 2020/3/10
     * Time: 2:04 下午
     * @param int $limit
     */
    public function fansTop($limit= 10){
        $users = D('User')->fansTop($limit);
        if( !$users ){
            return [];
        }
        $data = [];
        foreach ( $users as $item){
            $data[] = [
                'id' => $item['id'],
                'nickname' => $item['nickname'],
                'avatar' => DoUrlHandle($item['avatar'])
            ];
        }
        return $data;
    }

    /**
     * Notes: 直推合伙人
     * User: dede
     * Date: 2020/3/10
     * Time: 9:58 下午
     * @param $user_id
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    public function children($user_id, $where = [], $offset = 0, $limit = 10,$order = 'id desc'){
        $where['u.team_parent'] = $user_id;
		//$where['u.del_time'] = 0;
		
		$data['total'] = D('User')->alias('u')->where($where)->count();
        $data['rows'] = D('User')->alias('u')->field('u.*,SUM(broke.amount) achievement')->join('left join sj_brokerage_log broke on u.id=broke.from_id')->group('u.id')->where($where)->limit($offset . ',' . $limit)->order($order)->select();
		
		$data['rows'] = $this->listFormat($data['rows']);
        return $data;
    }
	
	public function childrenNum($user_id, $where=[]){
	    $where = [ 'team_parent' => $user_id ];
	    $count = D('User')->where($where)->count();
	    return $count;
	}
    /**
     * Notes:间推合伙人
     * User: dede
     * Date: 2020/3/10
     * Time: 9:59 下午
     * @param $user_id
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    public function grandChildren($user_id, $where=[], $offset = 0, $limit = 10){
        $map[] = [ 'team_parent' => $user_id ];
        $children = D('User')->where($map)->getField('id', true);
        if( !$children ){
            $data['rows'] = [];
            $data['total'] = 0;
            return $data;
        }
        $where['team_parent'] = ['IN', $children];
        $data = D('User')->getList($where, $offset, $limit);
        $data['rows'] = $this->listFormat($data['rows']);
        return $data;
    }

    public function grandChildrenNum($user_id, $where=[]){
        $map[] = [ 'team_parent' => $user_id ];
        $children = D('User')->where($map)->getField('id', true);
        $where['team_parent'] = ['IN', $children];
        if( !$children ){
            return 0;
        }
        $count = D('User')->alias('u')->where($where)->count();
        return $count;
    }

    public function listFormat($data){
        $list = [];
        foreach ( $data as $item ){
            $list[] = [
                'id' => $item['id'],
                'nickname' => $item['nickname'],
                'avatar' => DoUrlHandle($item['avatar']),
                'registertime' => date('Y-m-d', strtotime($item['registertime'])),
                'team_children_num' => $item['team_children_num'],
                'mobile' => $item['mobile'],
				'province' => $item['province']?:'',
				'city' => $item['city']?:'',
				'region' => $item['region']?:'',
            ];
        }
        return $list;
    }

    /**
     * Notes: 增加发布动态数量
     * User: dede
     * Date: 2020/3/10
     * Time: 9:28 下午
     * @param $user_id
     * @return bool
     */
    public function momentInc($user_id){
        return D('User')->where(['id' => $user_id])->setInc('moments');
    }

    /**
     * Notes: 减少发布动态数量
     * User: dede
     * Date: 2020/3/10
     * Time: 9:29 下午
     * @param $user_id
     * @return bool
     */
    public function momentDec($user_id){
        return D('User')->where(['id' => $user_id])->setDec('moments');
    }

    /**
     * Notes: 增加获赞数
     * User: dede
     * Date: 2020/3/10
     * Time: 9:37 下午
     * @param $user_id
     * @return bool
     */
    public function thumbsInc($user_id){
        return D('User')->where(['id' => $user_id])->setInc('thumbs');
    }

    /**
     * Notes: 减少或赞数
     * User: dede
     * Date: 2020/3/10
     * Time: 9:37 下午
     * @param $user_id
     * @return bool
     */
    public function thumbsDec($user_id){
        return D('User')->where(['id' => $user_id])->setDec('thumbs');
    }

    /**
     * Notes: 我的粉丝
     * User: dede
     * Date: 2020/3/10
     * Time: 10:04 下午
     * @param $user_id
     * @param int $offset
     * @param int $limit
     */
    public function fans($user_id, $offset = 0, $limit = 10){
        $data = D('UserFollow')->fans($user_id, $offset, $limit);
        $list = [];
        foreach ( $data['rows'] as $item ){
            $concerned= D('UserFollow')->concerned($user_id, $item['id']);
            $list[] = [
                'id' => $item['id'],
                'nickname' => $item['nickname'],
                'avatar' => DoUrlHandle($item['avatar']),
                'moments' => $item['moments'],
                'fans' => $item['fans'],
                'concerned' => $concerned,
            ];
        }
        $data['rows'] = $list;
        return $data;
    }

    /**
     * Notes: 我的关注
     * User: dede
     * Date: 2020/3/10
     * Time: 10:07 下午
     * @param $user_id
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    public function follow($user_id, $group_id = 0, $offset = 0, $limit = 10 ){
        $data = D('UserFollow')->follow($user_id, $group_id, $offset, $limit);
        $list = [];
        foreach ( $data['rows'] as $item ){
            $list[] = [
                'id' => $item['id'],
                'nickname' => $item['nickname'],
                'avatar' => DoUrlHandle($item['avatar']),
                'moments' => $item['moments'],
                'fans' => $item['fans'],
                'concerned' => 1,
            ];
        }
        $data['rows'] = $list;
        return $data;
    }

    /**
     * Notes: 今天邀请注册用户数量
     * User: dede
     * Date: 2020/3/10
     * Time: 10:18 下午
     * @param $user_id
     */
    public function inviteToday($user_id){
        $where = [
            'team_parent' => $user_id,
            'registertime' => ['egt', date('Y-m-d')],
        ];
        $count = D('User')->where($where)->count();
        return intval($count);
    }

    /**
     * Notes: 家护师搜索
     * User: dede
     * Date: 2020/3/26
     * Time: 12:08 下午
     * @param $keyword
     * @param $offset
     * @param $limit
     * @return mixed
     */
    public function searchServiceUser($keyword, $offset = 0, $limit = 0){
        //家护师
        $usermodel = D("user_role");
        $order = "up.recommend desc, up.top desc, up.comment_percent desc";
        $map = array("ur.role"=>3, "u.status"=>200, "up.status"=>1);
        if($keyword){
            $map["up.realname"] = array("like", "%".$keyword."%");
        }
        //剔除爽约
        $plane_time = date("Y-m-d H:i:s", strtotime("-3 month", time()));
        $map["up.plane_time"] = array(
            array("exp", "is null"),
            array("lt", $plane_time),
            "or"
        );
        $data['total'] = $usermodel->alias("ur")->join("left join sj_user as u on ur.userid=u.id")->join("left join sj_user_profile as up on ur.userid=up.userid")
            ->where($map)->count();
        if( $limit == 0 ){
            $data['rows'] = $usermodel->alias("ur")->join("left join sj_user as u on ur.userid=u.id")->join("left join sj_user_profile as up on ur.userid=up.userid")
                ->field("u.id,ur.role,u.avatar,up.realname,up.gender,up.birth,up.mobile,up.major_level,up.service_level,up.work_year,up.education,up.major,up.language,up.comment_percent")->where($map)->order($order)->select();
        }else{
            $data['rows'] = $usermodel->alias("ur")->join("left join sj_user as u on ur.userid=u.id")->join("left join sj_user_profile as up on ur.userid=up.userid")
                ->field("u.id,ur.role,u.avatar,up.realname,up.gender,up.birth,up.mobile,up.major_level,up.service_level,up.work_year,up.education,up.major,up.language,up.comment_percent")->where($map)->order($order)->limit( $offset . ',' . $limit  )->select();
        }
        foreach ($data['rows'] as &$v) {
            $v["avatar"] = DoUrlHandle($v["avatar"]);

            //性别
            switch($v['gender']){
                case 0:
                    $v['gender']='保密';
                    break;
                case 1:
                    $v['gender']='男';
                    break;
                case 2:
                    $v['gender']='女';
                    break;
            }

            //年龄
            $v["age"] = getAgeMonth($v["birth"]);
        }
        return $data;
    }
}
