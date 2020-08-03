<?php
namespace CApi\Controller;

class UserFollowController extends BaseController
{

    /**
     * Notes: 添加/取消关注
     * User: dede
     * Date: 2020/2/26
     * Time: 11:00 下午
     */
    public function concerned(){
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;
        }else{
            E('请先登录');
        }
        $follow = I('follow', 0, 'intval');
        if( !$follow ){
            E('请选择用户');
        }
        $add = D('UserFollow')->concerned($user['id'], $follow);
        if( !$add ){
            $res = D('UserFollow', 'Service')->append($user['id'], $follow);
            if( $res ){
				D('Point','Service')->append($user['id'],'follow');
                return ['status' => 1];
            }
        }else{
            $res = D('UserFollow', 'Service')->del($user['id'], $follow);
            if( $res ){
                return ['status' => 0];
            }
        }
        E('操作失败！');
    }

    /**
     * Notes: 我的粉丝
     * User: dede
     * Date: 2020/3/11
     * Time: 10:00 上午
     * @return array
     */
    public function fans(){
        $user_id = I('user_id', 0, 'intval');
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;
        $data = D('User', 'Service')->fans($user_id, $begin, $row);
        $count = $data['total'];
        $totalpage = ceil($count / $row);
        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        $data = [ 'users' => $data['rows'] ];
        return $data;
    }

    /**
     * Notes: 我的关注
     * User: dede
     * Date: 2020/3/25
     * Time: 2:24 下午
     * @return array
     */
    public function follow(){
        $user_id = I('user_id', 0, 'intval');
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $group_id = I('group_id', 0, 'intval');
        $begin = ($page-1)*$row;
        $data = D('User', 'Service')->follow($user_id, $group_id, $begin, $row);
        $count = $data['total'];
        $totalpage = ceil($count / $row);
        $this->SetPaginationHeader($totalpage, $count, $page, $row);
        $group = D('UserFollow', 'Service')->groupList($user_id);
        $data = [ 'users' => $data['rows'], 'group' => $group ];
        return $data;
    }
}