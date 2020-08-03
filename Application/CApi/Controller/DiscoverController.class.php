<?php
namespace CApi\Controller;

/**
 * 发现
 */

class DiscoverController extends BaseController
{

    /**
     * Notes: 关注
     * User: dede
     * Date: 2020/2/26
     * Time: 3:38 下午
     * @return array
     */
    public function concerned(){
        $this->UserAuthCheckLogin();
        $user = $this->AuthUserInfo;
        $recommend_user = D('User', 'Service')->recommend($user['id']);
        shuffle($recommend_user);
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;
        $moments = D('Moment', 'Service')->followList($user['id'], $offset, $row);
        foreach ($moments['rows'] as $key => $val) {
           $topic = D('Moment')->getTopic($val['moment_id']);
           $moments['rows'][$key]['topic'] = $topic;
        }
        $totalpage = ceil($moments['total'] / $row);
        $this->SetPaginationHeader($totalpage, $moments['total'], $page, $row);
        $data = [
            'recommend_user' => $recommend_user,
            'moment' => $moments['rows'],
        ];
        return $data;
    }


    /**
     * Notes: 推荐
     * User: dede
     * Date: 2020/7/20
     * Time: 12:12 下午
     * @return array
     */
    public function recommend(){
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;
        }else{
            $user['id'] = 0;
        }
        $where = [
            'is_top' => 1
        ];
        $top = D('Moment', 'Service')->getList($user['id'], $where, 0, 3);
        $topic = D('Topic', 'Service')->top(1, 4);
        $recommend = D('Moment', 'Service')->recommend($user['id']);
        $data = [
            'top' => $top['rows'],
            'topic' => $topic,
            'recommend' => $recommend['rows']
        ];
        return $data;
    }




    /**
     * Notes: 发现推荐
     * User: dede
     * Date: 2020/2/26
     * Time: 5:29 下午
     * @return array
     */
    public function handpick(){
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;
        }else{
            $user['id'] = 0;
        }
        $nav = D('DiscoverNav', 'Service')->nav();
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;
        $recommend = D('Moment', 'Service')->recommend($user['id'], [], $offset, $row);
        $data = [
            'nav' => $nav,
            'recommend' => $recommend,
        ];
        return $data;
    }


    /**
     * Notes: 精选栏目
     * User: dede
     * Date: 2020/2/26
     * Time: 5:31 下午
     */
    public function navInfo(){
        $nav_id = I('nav_id', 0, 'intval');
        if( !$nav_id || !in_array($nav_id, [1,2,3,4]) ){
            E('请选择栏目！');
        }
        $nav = D('DiscoverNav')->find($nav_id);
        if( !$nav ){
            E('请选择栏目！');
        }
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;
        }else{
            $user['id'] = 0;
        }
        $recommend = explode(',', $nav['recommend']);
        $product = [];
        if( $nav['recommend_type'] == 1 ){     // 优选
            $product = D('ServiceProject', 'Service')->batch($recommend);
        }else if( $nav['recommend_type'] == 2 ){       //好物
            $product = D('Product', 'Service')->batch($recommend);
        }else if( $nav['recommend_type'] == 3 ){       //家护师
            $product = D('Product', 'Service')->batch($recommend);
        }
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;
        if( $nav_id != 5 ){
            switch ($nav_id){
                case 1:
                    $tag = 'youxuan';
                    break;
                case 2:
                    $tag = 'haowu';
                    break;
                case 3:
                    $tag = 'jiahushi';
                    break;
                case 4:
                    $tag = 'gongyi';
                    break;
            }
            $category = D('MomentCategory')->getByTag($tag);
            if( $category ){
                $where['category_path'] = [ 'like', '%-'.$category['id'].'-%' ];
                $moment = D('Moment', 'Service')->getList($user['id'], $where, $offset, $row);
            }else{
                $moment = [
                    'total' => 0,
                    'rows' => [],
                ];
            }
        }else{
            $moment = D('PublicBenefit', 'Service')->getList();
        }
        $totalpage = ceil($moment['total'] / $row);
        $this->SetPaginationHeader($totalpage, $moment['total'], $page, $row);
        sort($product);
        $data = [
            'product' => $product,
            'moment' => $moment['rows'],
        ];
        return $data;
    }

    /**
     * Notes: 精选动态翻页
     * User: dede
     * Date: 2020/2/26
     * Time: 9:56 下午
     * @return mixed
     * @throws \Think\Exception
     */
    public function navPage(){
        $nav_id = I('nav_id', 0, 'intval');
        if( !$nav_id || !in_array($nav_id, [1,2,3,4,5]) ){
            E('请选择栏目！');
        }
        $nav = D('DiscoverNav')->find($nav_id);
        if( !$nav ){
            E('请选择栏目！');
        }
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;
        }else{
            $user['id'] = 0;
        }
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;
        if( $nav_id != 5 ){
            switch ($nav_id){
                case 1:
                    $tag = 'youxuan';
                    break;
                case 2:
                    $tag = 'haowu';
                    break;
                case 3:
                    $tag = 'jiahushi';
                    break;
                case 4:
                    $tag = 'gongyi';
                    break;
            }
            $category = D('MomentCategory')->getByTag($tag);
            if( $category ){
                $where['category_id'] = [ 'like', '%-'.$category['id'].'-%' ];
                $moment = D('Moment', 'Service')->getList($user['id'], $where, $offset, $row);
            }else{
                $moment = [
                    'total' => 0,
                    'rows' => [],
                ];
            }
        }else{
            $moment = D('PublicBenefit', 'Service')->getList([], $offset, $row);
        }

        $totalpage = ceil($moment['total'] / $row);
        $this->SetPaginationHeader($totalpage, $moment['total'], $page, $row);
        $data = [
            'moment' => $moment['rows'],
        ];
        return $data;
    }

    /**
     * Notes: 同城
     * User: dede
     * Date: 2020/2/26
     * Time: 10:16 下午
     * @return array
     */
    public function cityWide(){
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;
        }else{
            $user['id'] = 0;
        }
        $city = I('city');
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;
        $where = [
            'U.city' => $city
        ];
        $moment = D('Moment', 'Service')->getList($user['id'], $where, $offset, $row);
        $totalpage = ceil($moment['total'] / $row);
        $this->SetPaginationHeader($totalpage, $moment['total'], $page, $row);
        $data = [
            'moment' => $moment['rows'],
        ];
        return $data;
    }

    /**
     * Notes: 小视频
     * User: dede
     * Date: 2020/7/27
     * Time: 10:43 上午
     * @return array
     */
    public function video(){
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;
        }else{
            $user['id'] = 0;
        }
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;
        $where = [
            'resource_type' => 2
        ];
        $moment = D('Moment', 'Service')->getList($user['id'], $where, $offset, $row);
        $totalpage = ceil($moment['total'] / $row);
        $this->SetPaginationHeader($totalpage, $moment['total'], $page, $row);
        $data = [
            'moment' => $moment['rows'],
        ];
        return $data;
    }
}