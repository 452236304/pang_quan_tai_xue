<?php
namespace Admin\Controller;

class MomentController extends BaseController
{

    public function index(){
        $pagenum = I('p', 1, 'intval');
        $limit = 10;
        $where = [];
        $list = D('Moment')->getList($where, ($pagenum-1)*$limit, $limit, 'id', 'desc');
        foreach ($list['rows'] as &$item){
        }

        $data['data'] = $list['rows'];
        $page = new \Think\Page($list['total'],$limit);
        $page->setConfig("theme","%FIRST% %LINK_PAGE% %END%");
        if( $page->totalPages > 1 ){
            $data["pageshow"] = $page->show();
        }
        $this->assign($data);
        $this->display();
    }

    public function update(){
        $id = I('id', 0, 'intval');
        if( IS_AJAX ){
            $data = [
                'user_id' => C('MOMENT_OFFICIAL_USER_ID'),
                'title' => I('title'),
                'content' => I('content'),
                'resource_type' => I('resource_type', 0, 'intval'),
                'recommend_type' => I('recommend_type'),
                'category_id' => I('category_id'),
            ];

            $msg = '';
            if( !$data['title'] ){
                $msg += '请输入标题！<br/>';
            }
            if( !$data['content'] ){
                $msg += '请输入内容！<br/>';
            }
            if( !$data['category_id'] ){
                $msg += '请选择分类！<br/>';
            }
            if( !$data['resource_type'] ){
                $msg += '请选择类型！<br/>';
            }
            if ( $data['resource_type'] == 2){
                $data['resource'] = I('video');
                if( !$data['resource'] ){
                    $msg += '请上传视频！<br/>';
                }
            }else{
                $data['resource'] = I('images');
                if( !$data['resource'] ){
                    $msg += '请上传图片！<br/>';
                }
            }
            if( $msg ){
                $this->error($msg);
            }
            if( is_array($data['category_id']) ){
                $data['category_id'] = implode(',', $data['category_id']);
            }
            if( $data['recommend_type'] == 1 ){
                $data['recommend'] = implode(',', I('serviceProject'));
            }else if( $data['recommend_type'] == 2 ){
                $data['recommend'] = implode(',', I('product'));
            }
            if( $id ){
                $result = D('Moment')->update($id, $data);
            }else{
                $result = D('Moment')->addOne($data);
            }
            if( $result ){
                $this->success();
            }
            $this->error('操作失败！');
        }
        $data = D('Moment')->getOne($id);
        $data['content'] = htmlspecialchars_decode($data['content']);
        $data['resource_list'] = explode(',', $data['resource']);
        $data['resource_list'] = array_filter($data['resource_list']);
        $data['recommend'] = explode(',', $data['recommend']);
        $this->assign('data', $data);

        $category = D('MomentCategory')->select();
        $tree = \Org\Util\Tree::instance();
        $tree->init($category, 'parent_id');
        $categoryList = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $this->assign('category' , $categoryList);

        $serviceProject = D('ServiceProject')->select();
        $this->assign('serviceProject', $serviceProject);
        $where = [ 'status' => 1 ];
        $product = D('Product')->where($where)->select();
        $this->assign('product', $product);

        $this->display();
    }

    public function checkBoxList(){
        if( IS_AJAX ){
            $offset = I('offset', 0, 'intval');
            $limit = I('limit', 10, 'intval');
            $sort = I('sort', 'id');
            $order = I('order', 'DESC');
            $search = I('search');

            $where = [];
            $data = D('Moment')->getList($where, $offset, $limit, $sort, $order);
            foreach ($data['rows'] as &$item){
                $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
            }
            $this->ajaxReturn($data);
        }
    }

    public function remove(){
        if( IS_AJAX ){
            $id = I('request.id');
            $result = D('Moment')->remove($id);
            if( $result ){
                $this->success();
            }
            $this->error('操作失败！');
        }
    }

}