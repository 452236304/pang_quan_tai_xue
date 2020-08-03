<?php
namespace CApi\Controller;

class MomentController extends BaseController
{

    /**
     * Notes: 发布动态
     * User: dede
     * Date: 2020/2/26
     * Time: 10:20 下午
     * @return array
     * @throws \Think\Exception
     */
    public function publish(){
        if( !$this->UserAuthCheckLogin() ){
            E('请先登录！');
        };
        $user = $this->AuthUserInfo;
        $title = I('title');
        if( !$title ){
            E("请输入标题");
        }
        $content = I('content');
        if( !$content ){
            E("请输入内容");
        }
        $resource_type = I('resource_type', 0, 'intval');
        if( !in_array($resource_type, [1,2]) ){
            E("请选择动态类型");
        }
        $resource = I('resource');
        if( !is_array($resource) ){
            $resource = explode(',', $resource);
        }
        if( !$resource && $resource_type == 1 ){
            E("请上传图片");
        }
        if( !$resource && $resource_type == 1 ){
            E("请上传视频");
        }
        $category_id = I('category_id', 0, 'intval');
        if( !$category_id ){
            E("请选择分类");
        }
        $draft = I('draft', 0, 'intval');

        $data = array(
            'title' => $title,
            'content' => $content,
            'resource_type' => $resource_type,
            'resource' => $resource,
            'category_id' => $category_id,
            'draft' => $draft,
        );
        D()->startTrans();
        $moment_id = D('Moment', 'Service')->publish($user['id'],$data);
		//发布动态积分任务
		D('Point','Service')->append($user['id'],'create_mooment');
		
			
        if( $moment_id ){
            D()->commit();
            return ['moment_id' => $moment_id];
        }
        D()->rollback();
        E('错误！');
    }

    /**
     * Notes: 搜索动态
     * User: dede
     * Date: 2020/2/26
     * Time: 10:29 下午
     * @return array
     * @throws \Think\Exception
     */
    public function search(){
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;
        }else{
            $user['id'] = 0;
        }
        $where = [];
        $keyword = I('keyword');
        if( !$keyword ){
            $where['M.content'] = [ 'like', '%'.$keyword.'%'];
        }
        $category_id = I('category_id', '0', 'intval');
        if( $category_id ){
            $where['M.category_id'] = $category_id;
        }

        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;
        $moment = D('Moment', 'Service')->getList($user['id'], $where, $offset, $row);
        $data = [ 'moment' => $moment ];
        return $data;
    }

    /**
     * Notes: 动态详情
     * User: dede
     * Date: 2020/2/27
     * Time: 10:00 上午
     */
    public function details(){
        $moment_id = I('moment_id', 0, 'intval');
        if( !$moment_id ){
            E('请选择要查看的动态');
        }
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;
        }else{
            $user['id'] = 0;
        }
        $moment = D('Moment', 'Service')->details($moment_id, $user['id']);
        $data = [
            'moment' => $moment,
        ];
        return $data;
    }

    /**
     * Notes: 视频动态
     * User: dede
     * Date: 2020/2/27
     * Time: 4:12 下午
     */
    public function video(){
        $moment_id = I('moment_id', 0, 'intval');
        if( !$moment_id ){
            E('请选择要查看的动态');
        }
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;
        }else{
            $user['id'] = 0;
        }
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;
        $where = [
            'M.resource_type' => 3,
            'M.id' => [ 'egt', $moment_id ],
        ];
        $moment = D('Moment', 'Service')->getList($user['id'], $where, $offset, $row);
        $data = [ 'moment' => $moment ];
        return $data;
    }

    /**
     * Notes: 分享成功回调
     * User: dede
     * Date: 2020/2/27
     * Time: 5:09 下午
     * @throws \Think\Exception
     */
    public function shareSuccess(){
        $moment_id = I('moment_id', 0, 'intval');
        if( !$moment_id ){
            E('请选择要查看的动态');
        }
        $moment = D('Moment')->getOne($moment_id);
        if( !$moment ){
            E('请选择要查看的动态');
        }
        D('Moment', 'Service')->shareNum($moment_id);
		if($this->UserAuthCheckLogin() ){
			$user = $this->AuthUserInfo;
			//分享动态积分任务
			D('Point','Service')->append($user['id'],'share');
		};
        return true;
    }

    /**
     * Notes: 添加/取消点赞
     * User: dede
     * Date: 2020/2/27
     * Time: 5:14 下午
     * @return bool
     * @throws \Think\Exception
     */
    public function thumbs(){
        if( !$this->UserAuthCheckLogin() ){
            E('请先登录！');
        };
        $user = $this->AuthUserInfo;
        $moment_id = I('moment_id', 0, 'intval');
        if( !$moment_id ){
            E('请选择要查看的动态');
        }
        $moment = D('Moment')->getOne($moment_id);
        if( !$moment ){
            E('请选择要查看的动态');
        }
        $res = D('Moment', 'Service')->thumbs($user['id'], $moment_id);
        if( !$res ){
            D('Moment', 'Service')->thumbsNumInc($moment_id, $user['id']);
        }else{
            D('Moment', 'Service')->thumbsNumDec($moment_id, $user['id']);
        }
		//点赞文章积分任务
		D('Point','Service')->append($user['id'],'like');
		
        return true;
    }

    /**
     * Notes: 浏览记录
     * User: dede
     * Date: 2020/3/3
     * Time: 3:27 下午
     * @return array
     * @throws \Think\Exception
     */
    public function history(){
        if( !$this->UserAuthCheckLogin() ){
            E('请先登录！');
        };
        $user = $this->AuthUserInfo;
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;
        $moment = D('Moment', 'Service')->history($user['id'], $offset, $row);
        $data = [
            'history' => $moment['rows'],
        ];
        return $data;
    }

    /**
     * Notes: 动态分类
     * User: dede
     * Date: 2020/3/10
     * Time: 12:04 下午
     * @return array
     * @throws \Think\Exception
     */
    public function category(){
        if( !$this->UserAuthCheckLogin() ){
            E('请先登录！');
        };
        $where = [
            'is_user' => 1,
        ];
        $data = D('MomentCategory')->where($where)->order('sort')->field('id, name')->select();
        return ['category' => $data];
    }

    /**
     * Notes:查询某个用户的动态列表
     * User: dede
     * Date: 2020/3/10
     * Time: 7:06 下午
     * @return array
     * @throws \Think\Exception
     */
    public function userList(){
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;
        }else{
            $user['id'] = 0;
        }
        $user_id = I('user_id', 0, 'intval');
        $data = D('User')->find($user_id);
        $user_info = [
            'user_id' => $data['id'],
            'nickname' => $data['nickname'],
            'avatar' => DoUrlHandle($data['avatar']),
            'follow' => $data['follow'],
            'fans' => $data['fans'],
            'thumbs' => $data['thumbs'],
            'sign' => $data['sign'],
        ];
        if( !$user_id ){
            E('请先选择要查看的用户！');
        }
        $resource_type = I('resource_type', 0, 'intval');
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;

        $data = D('moment', 'Service')->userMoment($user_id, $resource_type, $user['id'], $offset, $row);
        $count = $data['total'];
        $totalpage = ceil($count / $row);
        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        $data = [ 'moment' => $data['rows'], 'user' => $user_info ];
        return $data;

    }


    /**
     * Notes: 添加取消收藏
     * User: dede
     * Date: 2020/3/26
     * Time: 12:39 上午
     * @return bool
     * @throws \Think\Exception
     */
    public function collect(){
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;
        }else{
            E('请先登录');
        }
        $moment_id = I('moment_id', 0, 'intval');
        if( !$moment_id ){
            E('请先选择动态');
        }
        $collect = D('Moment', 'Service')->collect($user['id'], $moment_id);
        if( $collect ){
            $res = D('Moment', 'Service')->removeCollect($user['id'], $moment_id);
        }else{
            $res = D('Moment', 'Service')->appendCollect($user['id'], $moment_id);
        }
        if( $res ){
            return true;
        }
        E('操作失败！');
    }

    /**
     * Notes: 收藏动态列表
     * User: dede
     * Date: 2020/3/26
     * Time: 12:51 上午
     * @return array
     */
    public function collectList(){
        $user_id = I('user_id', 0, 'intval');
        if( !$user_id ){
            E('请选择用户');
        }
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;

        $group_id = I('group_id', 0, 'intval');
        $data = D('Moment', 'Service')->collectList($user_id, $group_id, $offset, $row);
        $count = $data['total'];
        $totalpage = ceil($count / $row);
        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        return ['moment' => $data['rows'] ];
    }

}