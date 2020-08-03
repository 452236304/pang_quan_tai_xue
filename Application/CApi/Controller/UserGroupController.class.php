<?php
namespace CApi\Controller;

class UserGroupController extends BaseLoggedController
{

    /**
     * Notes: 添加用户组
     * User: dede
     * Date: 2020/3/3
     * Time: 4:35 下午
     * @return array
     * @throws \Think\Exception
     */
    public function increase(){
        $user = $this->AuthUserInfo;
        $group_name = I('group_name');
        if( !$group_name ){
            E('请输入用户组名称');
        }
        $data = [
            'user_id' => $user['id'],
            'group_name' => $group_name,
        ];
        // 判重
        $group = D('UserGroup')->where($data)->find();
        if( $group ){
            E('用户组已存在， 不能重复添加！');
        }
        $group_id = D('UserGroup')->addOne($data);
        if( $group_id ){
            return ['group_id' => $group_id];
        }
        E('操作失败！');
    }

    /**
     * Notes: 删除用户组
     * User: dede
     * Date: 2020/3/3
     * Time: 4:52 下午
     */
    public function del(){
        $user = $this->AuthUserInfo;
        $group_id = I('group_id', 0, 'intval');
        $where = [
            'id' => $group_id,
            'user_id' => $user['id']
        ];
        $group = D('UserGroup')->where($where)->find();
        if( !$group ){
            E('用户组不存在！');
        }
        $where = [
            'group_id' => $group_id,
        ];
        $count = D('UserFollow')->where($where)->count();
        if( $count ){
            E('该用户组下还有用户，不能删除！');
        }
        $res = D('UserGroup')->remove($group_id);
        if( $res ){
            return true;
        }
        E('操作失败！');
    }

    /**
     * Notes: 修改用户所属用户组
     * User: dede
     * Date: 2020/3/3
     * Time: 4:45 下午
     * @throws \Think\Exception
     */
    public function appendUser(){
        $user = $this->AuthUserInfo;
        $append_user_id = I('append_user_id');
        $group_id = I('group_id', 0, 'intval');
        $where = [
            'id' => $group_id,
            'user_id' => $user['id']
        ];
        $group = D('UserGroup')->where($where)->find();
        if( !$group ){
            E('用户组不存在！');
        }
        if( !is_array($append_user_id) ){
            $append_user_id = explode(',', $append_user_id);
        }
        D()->startTrans();
        foreach ($append_user_id as $id ){
            $concerned = D('UserFollow')->concerned($user['id'], $id);
            if( !$concerned ){
                D()->rollback();
                E('请先关注该用户！');
            }
            $follow_user = D('User')->find($id);
            if( !$follow_user ){
                D()->rollback();
                E('用户不存在！');
            }
            $res = D('UserFollow', 'Service')->appendGroup($user['id'], $id, $group_id);
            if( $res ){
                D()->commit();
                return true;
            }else{
                D()->rollback();
            }
        }

        E('操作失败！');
    }

    /**
     * Notes: 移除用户组中的用户
     * User: dede
     * Date: 2020/3/26
     * Time: 12:19 上午
     * @return bool
     * @throws \Think\Exception
     */
    public function removeUser(){
        $user = $this->AuthUserInfo;
        $append_user_id = I('append_user_id');
        $group_id = I('group_id', 0, 'intval');
        $where = [
            'id' => $group_id,
            'user_id' => $user['id']
        ];
        $group = D('UserGroup')->where($where)->find();
        if( !$group ){
            E('用户组不存在！');
        }
        if( !is_array($append_user_id) ){
            $append_user_id = explode(',', $append_user_id);
        }
        D()->startTrans();
        foreach ($append_user_id as $id ){
            $concerned = D('UserFollow')->concerned($user['id'], $id);
            if( !$concerned ){
                D()->rollback();
                E('请先关注该用户！');
            }
            $follow_user = D('User')->find($id);
            if( !$follow_user ){
                D()->rollback();
                E('用户不存在！');
            }
            $res = D('UserFollow', 'Service')->removeGroup($user['id'], $id, $group_id);
            if( $res ){
                D()->commit();
                return true;
            }else{
                D()->rollback();
            }
        }

        E('操作失败！');
    }

    public function userList(){
        $user = $this->AuthUserInfo;
        $group_id = I('group_id', 0, 'intval');
        if( !$group_id ){
            E('请先选择分组');
        }
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;

        $data = D('UserFollow', 'Service')->userList($user['id'], $group_id, $offset, $row);
        $count = $data['total'];
        $totalpage = ceil($count / $row);
        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        return ['moment' => $data['rows'] ];
    }
}