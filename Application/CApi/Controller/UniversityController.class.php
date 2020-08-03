<?php
namespace CApi\Controller;

class UniversityController extends BaseController
{
    /**
     * Notes: 大咖秀
     * User: dede
     * Date: 2020/3/10
     * Time: 2:12 下午
     * @return array
     */
    public function masterShow(){
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;
        }else{
            $user['id'] = 0;
        }
        // 轮播图
        $banner_type = 1002;
        $banner = D('Banner', 'Service')->show($banner_type);
        // 关注量top10
        $users = D('User', 'Service')->fansTop();
        // 分类动态
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;
        $category = D('MomentCategory')->getByTag('zhuanjia');
        $moment = D('Moment', 'Service')->categoryList($category['id'], $user['id'], $offset, $row);

        $data = [
            'user' => $users,
            'banner' => $banner,
            'moment' => $moment['rows']
        ];

        $count = $moment['total'];
        $totalpage = ceil($count / $row);
        $this->SetPaginationHeader($totalpage, $count, $page, $row);
        return $data;
    }

    /**
     * Notes: 话题
     * User: dede
     * Date: 2020/3/10
     * Time: 2:53 下午
     * @return mixed\
     */
    public function topic(){
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;
        }else{
            $user['id'] = 0;
        }
        $nav_id = 4;
        $this->common($nav_id);
        $topic = D('Topic', 'Service')->top(10);
        $this->data['topic'] = $topic;
        // 分类动态
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;
        $category = D('MomentCategory')->getByTag('huati');
        $data = D('Moment', 'Service')->categoryList($category['id'], $user['id'], $offset, $row);
        $this->data['moment'] = $data['rows'];
        $this->data['category_id'] = $category['id'];

        $count = $data['total'];
        $totalpage = ceil($count / $row);
        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        return $this->data;
    }

    /**
     * Notes: 养老通-政策解读
     * User: dede
     * Date: 2020/3/10
     * Time: 3:01 下午
     * @return mixed
     */
    public function older(){
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;
        }else{
            $user['id'] = 0;
        }
        $banner_type = 1001;
        // 轮播图
        $banner = D('Banner', 'Service')->show($banner_type);
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;
        // 动态列表
        $tag = 'zhengce';
        $nav = D('UnivNav','Service')->nav('older');
        $category = D('MomentCategory')->getByTag($tag);
        $moment = D('Moment', 'Service')->categoryList($category['id'], $user['id'], $offset, $row);
        $count = $moment['total'];
        $totalpage = ceil($count / $row);
        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        $data = [
            'banner' => $banner,
            'nav' => $nav,
            'moment' => $moment['rows'],

        ];

        return $data;
    }



    /**
     * Notes: 知识库
     * User: dede
     * Date: 2020/3/10
     * Time: 3:25 下午
     * @return mixed
     */
    public function knowledge(){
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;
        }else{
            $user['id'] = 0;
        }
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;

        $banner_type = 1004;
        $banner = D('Banner', 'Service')->show($banner_type);

        $tag = 'zhishi';
        $nav = D('UnivNav', 'Service')->nav($tag);

        $category = D('MomentCategory')->getByTag($tag);

        $moment = D('Moment', 'Service')->categoryList($category['id'], $user['id'], $offset, $row);

        $count = $moment['total'];
        $totalpage = ceil($count / $row);
        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        $data = [
            'banner' => $banner,
            'nav' => $nav,
            'moment' => $moment['rows'],
        ];

        return $data;
    }

    /**
     * Notes: 知识库分类查询列表
     * User: dede
     * Date: 2020/3/10
     * Time: 3:27 下午
     * @throws \Think\Exception
     */
    public function knowledgeList(){
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;
        }else{
            $user['id'] = 0;
        }

        $tag = 'zhishi';
        $category = D('MomentCategory')->getByTag($tag);
        $children = D('MomentCategory')->children($category['id']);

        $category_id = I('category_id', 0, 'intval');
        if( !$category_id ){
            $category_id = $children[0]['id'];
            if( !$category ){
                $category_id = $category['id'];
            }
        }
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;
        $moment = D('Moment', 'Service')->categoryList($category_id, $user['id'], $offset, $row);

        $count = $moment['total'];
        $totalpage = ceil($count / $row);
        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        $data = [
            'category' => $children,
            'moment' => $moment['rows'],
        ];
        return $data;
    }

    /**
     * Notes: 动态分类翻页
     * User: dede
     * Date: 2020/3/16
     * Time: 4:44 下午
     * @return array
     * @throws \Think\Exception
     */
    public function pageLimit(){
        $category_id = I('category_id', 0, 'intval');
        if( !$category_id ){
            E('请选择分类');
        }
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;
        }else{
            $user['id'] = 0;
        }

        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;
        $data = D('Moment', 'Service')->categoryList($category_id, $user['id'], $offset, $row);
        return ['moment' => $data['rows']];
    }

    /**
     * Notes: V3专题讲座
     * User: dede
     * Date: 2020/3/26
     * Time: 6:48 下午
     */
    public function special(){
        $page = I('page', 1, 'intval');
        $limit = I('limit', 10, 'intval');
        $banner_type = 1005;
        $banner = D('Banner', 'Service')->show($banner_type);

        $where = [
            'type' => 1,
        ];
        $category = D('SpecialCategory')
            ->where($where)
            ->field('id, title, icon')
            ->order('sort')
            ->limit(5)
            ->select();
        foreach ( $category as &$item ){
            $item['icon'] = DoUrlHandle($item['icon']);
        }
        $author = D('SpecialAuthor')
            ->field('id, name, avatar, tag, org')
            ->order('is_recommend desc, sort desc')
            ->limit(3)
            ->select();
        foreach ( $author as &$item ){
            $item['avatar'] = DoUrlHandle($item['avatar']);
        }

        $where = [
            'S.status' => 1,
            'S.type' => 1,
            'S.is_recommend' => 1,
        ];
        $offset = ($page-1)*$limit;
        $data = D('SpecialV3')->getList($where, $offset, $limit);
        foreach ( $data['rows'] as &$item ){
            $item['avatar'] = DoUrlHandle($item['avatar']);
            $item['thumb'] = DoUrlHandle($item['thumb']);
            $item['add_time'] = date('Y-m-d', $item['add_time']);
        }

        $count = $data['total'];
        $totalpage = ceil($count / $limit);
        $this->SetPaginationHeader($totalpage, $count, $page, $limit);

        $data = [
            'banner' => $banner,
            'category' => $category,
            'author' => $author,
            'list' => $data['rows']
        ];

        return $data;
    }

    /**
     * Notes: 学习教程首页
     * User: dede
     * Date: 2020/7/20
     * Time: 9:21 下午
     * @return array
     */
    public function study(){
        $page = I('page', 1, 'intval');
        $limit = I('limit', 10, 'intval');

        $banner_type = 1006;
        $banner = D('Banner', 'Service')->show($banner_type);

        $where = [
            'type' => 2,
        ];
        $category = D('SpecialCategory')
            ->where($where)
            ->field('id, title, icon')
            ->order('sort')
            ->limit(5)
            ->select();
        foreach ( $category as &$item ){
            $item['icon'] = DoUrlHandle($item['icon']);
        }

        $where = [
            'S.status' => 1,
            'S.type' => 2,
            'S.is_recommend' => 1,
        ];
        $limit = 4;
        $recommend = D('SpecialV3')->getList($where, 0, $limit);
        foreach ( $recommend['rows'] as &$item ){
            $item['avatar'] = DoUrlHandle($item['avatar']);
            $item['thumb'] = DoUrlHandle($item['thumb']);
        }

        $where = [
            'S.status' => 1,
            'S.type' => 2,
            'S.recommend_type' => 1,
        ];
        $study = D('SpecialV3')->getList($where, 0, $limit);
        foreach ( $study['rows'] as &$item ){
            $item['avatar'] = DoUrlHandle($item['avatar']);
            $item['thumb'] = DoUrlHandle($item['thumb']);
        }

        $data = [
            'banner' => $banner,
            'category' => $category,
            'recommend' => $recommend['rows'],
            'study' => $study['rows'],
        ];

        return $data;
    }

    /**
     * Notes: 精品课首页
     * User: dede
     * Date: 2020/7/20
     * Time: 9:26 下午
     */
    public function course(){
        $page = I('page', 1, 'intval');
        $limit = I('limit', 10, 'intval');

        $banner_type = 1007;
        $banner = D('Banner', 'Service')->show($banner_type);

        $where = [
            'type' => 3,
        ];
        $category = D('SpecialCategory')
            ->where($where)
            ->field('id, title, icon')
            ->order('sort')
            ->limit(5)
            ->select();
        foreach ( $category as &$item ){
            $item['icon'] = DoUrlHandle($item['icon']);
        }

        $where = [
            'S.status' => 1,
            'S.type' => 3,
            'S.is_recommend' => 1,
        ];
        $limit = 4;
        $recommend = D('SpecialV3')->getList($where, 0, $limit);
        foreach ( $recommend['rows'] as &$item ){
            $item['avatar'] = DoUrlHandle($item['avatar']);
            $item['thumb'] = DoUrlHandle($item['thumb']);
        }
        $where = [
            'S.status' => 1,
            'S.type' => 3,
            'S.recommend_type' => 2,
        ];
        $data = D('SpecialV3')->getList($where, 0, $limit);
        foreach ( $data['rows'] as &$item ){
            $item['avatar'] = DoUrlHandle($item['avatar']);
            $item['thumb'] = DoUrlHandle($item['thumb']);
        }

        $data = [
            'banner'=> $banner,
            'category' => $category,
            'recommend' => $recommend['rows'],
            'list' => $data['rows']
        ];

        return $data;
    }


    /**
     * Notes: 专题\学习教程\精品课分类列表页
     * User: dede
     * Date: 2020/7/20
     * Time: 9:32 下午
     * @return array
     */
    public function listed(){
        $page = I("get.page", 1);
        $limit = I("get.limit", 10);
        $type = I('type',0, 'intval');
        if( !in_array($type, [1,2,3]) ){
            E('非法操作！');
        }
        $category_id = I('category_id', 0, 'intval');
        $where = [
            'type' => $type,
        ];
        $category = D('SpecialCategory')
            ->where($where)
            ->field('id, title')
            ->order('sort')
            ->select();
        if( $category_id ){
            if( $category ){
                $category_id = $category[0]['id'];
            }else{
                $category_id = 0;
            }
        }

        $where = [
            'S.type' => $type,
            'S.status' => 1
        ];
        if( $category_id ){
            $where['category_id'] = $category_id;
        }
        $keyword = I('keyword');
        if( $keyword ){
            $where['title'] = ['like', '%'.$keyword.'%'];
        }
        $offset = ($page-1)*$limit;
        $data = D('SpecialV3')->getList($where, $offset, $page);
        foreach ( $data['rows'] as &$item ){
            $item['avatar'] = DoUrlHandle($item['avatar']);
            $item['thumb'] = DoUrlHandle($item['thumb']);
        }

        $count = $data['total'];
        $totalpage = ceil($count / $limit);
        $this->SetPaginationHeader($totalpage, $count, $page, $limit);
        $data = [
            'category' => $category,
            'list' => $data['rows']
        ];
        return $data;
    }

    /**
     * Notes: 专题\学习教程\精品课详情
     * User: dede
     * Date: 2020/7/20
     * Time: 9:35 下午
     * @return mixed
     * @throws \Think\Exception
     */
    public function details(){
        $id = I('id', 0, 'intval');
        if( !$id ){
            E('请先选择要查看的课程');
        }
        $data = D('SpecialV3')->find($id);
        if( !$data ){
            E('请先选择要查看的课程');
        }
        return $data;
    }

    /**
     * Notes: 专题评论列表
     * User: dede
     * Date: 2020/7/28
     * Time: 3:36 下午
     * @return mixed
     * @throws \Think\Exception
     */
    public function comment(){
        $page = I("get.page", 1);
        $limit = I("get.limit", 10);

        $id = I('id', 0, 'intval');
        $special = D('special')->find($id);
        if( !$special ){
            E('非法操作！');
        }

        $comment = D('SpecialComment');
        $where = [
            'special_id' => $id
        ];
        $field = ['C.*', 'U.nickname', 'U.avatar'];
        $data = $comment->alias('C')
            ->join('__USER__ U ON U.id = C.user_id')
            ->where($where)
            ->field($field)
            ->order('C.add_time DESC')
            ->limit( ($page-1)*$limit, $limit )
            ->select();
        foreach ( $data as &$item ){
            $item['add_time'] = date('Y-m-d', $item['add_time']);
            $item['avatar'] = DoUrlHandle($item['avatar']);
            $where = [
                'special_id' => $id,
                'replay_id' => $item['id']
            ];
            $replay = $comment->alias('C')
                ->join('__USER__ U ON U.id = C.user_id')
                ->where($where)
                ->field($field)
                ->order('C.add_time DESC')
                ->select();
            foreach ( $replay as &$value ){
                $value['add_time'] = date('Y-m-d', $value['add_time']);
            }
        }

        $count = $comment->alias('C')
            ->join('__USER__ U ON U.id = C.user_id')
            ->where($where)
            ->count();
        $totalpage = ceil($count / $limit);
        $this->SetPaginationHeader($totalpage, $count, $page, $limit);

        return $data;
    }

    /**
     * Notes: 添加评论
     * User: dede
     * Date: 2020/7/28
     * Time: 3:40 下午
     * @throws \Think\Exception
     */
    public function appendComment(){
        $user = $this->AuthUserInfo;
        if( !$user ){
            E('请先登录');
        }

        $id = I('id', 0, 'intval');
        $replay_id = I('replay_id', 0, 'intval');
        $special = D('SpecialV3')->find($id);
        if( !$special ){
            E('非法操作！');
        }

        $content = I('content');
        if( !$content ){
            E('请输入评论内容');
        }
        $data = [
            'special_id' => $id,
            'user_id' => $user['id'],
            'content' => $content,
            'add_time' => time(),
            'replay_id' => $replay_id
        ];
        $comment = D('SpecialComment');
        $res = $comment->add($data);
        if( $data['replay_id'] ){
            $where = ['id' => $data['replay_id']];
            $comment->where($where)->setInc('comment_num');
        }
        $where = ['id' => $id];
        D('SpecialV3')->where($where)->setInc('comment_num');
        return ['status' => $res];
    }

    /**
     * Notes: 分享回调
     * User: dede
     * Date: 2020/7/28
     * Time: 3:43 下午
     * @return array
     * @throws \Think\Exception
     */
    public function share(){
        $id = I('id', 0, 'intval');
        $special = D('SpecialV3')->find($id);
        if( !$special ){
            E('非法操作！');
        }
        $where = ['id' => $id];
        D('SpecialV3')->where($where)->setInc('share_num');
        return ['status' => 1];
    }

    /**
     * Notes:专题、评论
     * User: dede
     * Date: 2020/7/28
     * Time: 3:53 下午
     * @return array
     * @throws \Think\Exception
     */
    public function thumbs(){
        $user = $this->AuthUserInfo;
        if( !$user ){
            E('请先登录');
        }

        $id = I('id', 0, 'intval');
        $type = I('type', 0, 'intval');
        if( !$type ){
            E('非法操作！');
        }

        $where = [
            'param' => $id,
            'user_id' => $user['id'],
            'type' => $type
        ];
        $result = D('SpecialThumbs')->where($where)->find();
        if( $result ){
            D('SpecialThumbs')->where($where)->delete();
            if( $type == 1 ){
                $where = [
                    'id' => $id
                ];
                D('SpecialV3')->where($where)->setDec('thumbs_num');
            }else if( $type == 2 ){
                $where = [
                    'id' => $id
                ];
                D('SpecialComment')->where($where)->setDec('thumbs_num');
            }
            $status = 0;
        }else{
            $data = [
                'user_id' => $user['id'],
                'param' => $id,
                'add_time' => time(),
                'type' => $type,
            ];
            D('SpecialThumbs')->add($data);
            if( $type == 1 ){
                $where = [
                    'id' => $id
                ];
                D('SpecialV3')->where($where)->setInc('thumbs_num');
            }else if( $type == 2 ){
                $where = [
                    'id' => $id
                ];
                D('SpecialComment')->where($where)->setInc('thumbs_num');
            }
            $status = 1;
        }
        return ['status'=>$status];
    }


}