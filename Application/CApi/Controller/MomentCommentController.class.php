<?php
namespace CApi\Controller;

class MomentCommentController extends BaseController
{

    /**
     * Notes: 发布评论
     * User: dede
     * Date: 2020/2/27
     * Time: 4:28 下午
     * @return array
     * @throws \Think\Exception
     */
    public function publish(){
        if( !$this->UserAuthCheckLogin() ){
            E('请先登录！');
        };
        $user = $this->AuthUserInfo;
        $moment_id = I('moment_id', 0, 'intval');
        if( !$moment_id ){
            E('请选择要评论的动态！');
        }
        $content = I('content');
        if( !$content ){
            E('请输入评论内容！');
        }
        $replay_id = I('replay_id', 0, 'intval');
        $data = [
            'moment_id' => $moment_id,
            'content' => $content,
            'replay_id' => $replay_id,
        ];
        $res = D('MomentComment', 'Service')->publish($user['id'], $data);
        if( $res ){
            $data = [ 'moment_comment_id' => $res ];
			//评论积分任务
			D('Point','Service')->append($user['id'],'comment');
            return $data;
        }
        E('操作失败！');
    }

    /**
     * Notes: 动态评论列表
     * User: dede
     * Date: 2020/2/27
     * Time: 4:28 下午
     * @return array
     * @throws \Think\Exception
     */
    public function commentList(){
        $moment_id = I('moment_id', 0, 'intval');
        if( !$moment_id ){
            E('请选择要查看的动态！');
        }
        $replay_id = I('replay_id', 0, 'intval');
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;
        $commend = D('MomentComment', 'Service')->comment($moment_id, $replay_id, $offset, $row);
        $data = [
            'commend' => $commend
        ];
        return $data;
    }
}