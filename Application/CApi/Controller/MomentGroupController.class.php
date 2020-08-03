<?php
namespace CApi\Controller;

class MomentGroupController extends BaseLoggedController
{

    /**
     * Notes: 添加动态分组
     * User: dede
     * Date: 2020/3/26
     * Time: 1:02 上午
     * @return array
     * @throws \Think\Exception
     */
    public function increase(){
        $user = $this->AuthUserInfo;
        $group_name = I('group_name');
        if( !$group_name ){
            E('动态分组名称');
        }
        $data = [
            'user_id' => $user['id'],
            'group_name' => $group_name,
        ];
        // 判重
        $group = D('MomentGroup')->where($data)->find();
        if( $group ){
            E('动态分组已存在， 不能重复添加！');
        }
        $group_id = D('MomentGroup')->addOne($data);
        if( $group_id ){
            return ['group_id' => $group_id];
        }
        E('操作失败！');
    }

    /**
     * Notes: 删除动态分组
     * User: dede
     * Date: 2020/3/26
     * Time: 1:02 上午
     * @return bool
     * @throws \Think\Exception
     */
    public function del(){
        $user = $this->AuthUserInfo;
        $group_id = I('group_id', 0, 'intval');
        $where = [
            'id' => $group_id,
            'user_id' => $user['id']
        ];
        $group = D('MomentGroup')->where($where)->find();
        if( !$group ){
            E('动态分组不存在！');
        }
        $where = [
            'group_id' => $group_id,
        ];
        $count = D('MomentCollect')->where($where)->count();
        if( $count ){
            E('该动态分组下还有收藏的动态，不能删除！');
        }
        $res = D('UserGroup')->remove($group_id);
        if( $res ){
            return true;
        }
        E('操作失败！');
    }

    /**
     * Notes: 添加动态到分组
     * User: dede
     * Date: 2020/3/3
     * Time: 4:45 下午
     * @throws \Think\Exception
     */
    public function appendUser(){
        $user = $this->AuthUserInfo;
        $append_moment_id = I('append_moment_id');
        $group_id = I('group_id', 0, 'intval');
        $where = [
            'id' => $group_id,
            'user_id' => $user['id']
        ];
        $group = D('MomentGroup')->where($where)->find();
        if( !$group ){
            E('动态分组不存在！');
        }
        if( !is_array($append_moment_id) ){
            $append_moment_id = explode(',', $append_moment_id);
        }
        D()->startTrans();
        foreach ($append_moment_id as $id ){
            $where = [
                'user_id' => $user[0],
                'moment_id' => $id,
            ];
            $collect = D('MomentCollect')->where($where)->find();
            if( !$collect ){
                D()->rollback();
                E('请先收藏该动态！');
            }
            if( !$collect['group_id'] ){
                D()->rollback();
                E('改动态已有分组！');
            }
            $follow_user = D('Moment')->find($id);
            if( !$follow_user ){
                D()->rollback();
                E('动态不存在！');
            }
            $data = [
                'group_id' => $group_id
            ];
            $res = D('MomentCollect')->where($where)->save($data);
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
     * Notes: 移除动态分组中的动态
     * User: dede
     * Date: 2020/3/26
     * Time: 12:19 上午
     * @return bool
     * @throws \Think\Exception
     */
    public function removeUser(){
        $user = $this->AuthUserInfo;
        $append_moment_id = I('append_moment_id');
        $group_id = I('group_id', 0, 'intval');
        $where = [
            'id' => $group_id,
            'user_id' => $user['id']
        ];
        $group = D('MomentGroup')->where($where)->find();
        if( !$group ){
            E('动态分组不存在！');
        }
        if( !is_array($append_moment_id) ){
            $append_moment_id = explode(',', $append_moment_id);
        }
        D()->startTrans();
        foreach ($append_moment_id as $id ){
            $where = [
                'user_id' => $user[0],
                'moment_id' => $id,
            ];
            $collect = D('MomentCollect')->where($where)->find();
            if( !$collect ){
                D()->rollback();
                E('请先收藏该动态！');
            }
            if( $collect['group_id'] != $group_id ){
                D()->rollback();
                E('改动态不在当前分组！');
            }
            $follow_user = D('Moment')->find($id);
            if( !$follow_user ){
                D()->rollback();
                E('动态不存在！');
            }
            $res = D('MomentCollect')->where($where)->delete();
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
     * Notes: 动态分组动态列表
     * User: dede
     * Date: 2020/3/26
     * Time: 1:09 上午
     * @return array
     * @throws \Think\Exception
     */
    public function momentList(){
        $user = $this->AuthUserInfo;
        $group_id = I('group_id', 0, 'intval');
        if( !$group_id ){
            E('请选选择分组');
        }
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;

        $data = D('Moment', 'Service')->groupList($user['id'], $group_id, $offset, $row);
        $count = $data['total'];
        $totalpage = ceil($count / $row);
        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        return ['moment' => $data['rows'] ];
    }
}