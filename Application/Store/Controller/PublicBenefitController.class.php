<?php
namespace Store\Controller;

class PublicBenefitController extends BaseController
{

    public function index(){
        $order = "id desc";
        $p = I("get.p");
        $param = ["p"=>$p];
        $map = [];

        $data = $this->pager("PublicBenefit", "10", $order, $map, $param);
        foreach ($data['data']  as &$item){
            if( $item['resource_type'] == 1 ){
                $item['resource_type'] = '图片';
            }elseif( $item['resource_type'] == 2 ){
                $item['resource_type'] = '视频';
            }
        }
        $this->assign($data);
        $this->display();
    }

    public function update(){
        $id = I('id', 0, 'intval');
        if( IS_AJAX ){
            $data = [
                'title' => I('title'),
                'cat_id' => I('cat_id'),
                'start_time' => I('start_time'),
                'recruit_num' => I('recruit_num', 0, 'intval'),
                'resource_type' => I('resource_type', 0, 'intval'),
                'content' => I('content'),
                'author' => I('author', '一点椿'),
            ];

            $msg = '';
            if( !$data['title'] ){
                $msg += '请输入表题<br/>';
            }
            if( !$data['cat_id'] ){
                $msg += '请选择分类<br/>';
            }
            if( !strtotime($data['start_time']) ){
                $msg += '请选择活动开始时间<br/>';
            }
            if( !$data['recruit_num'] ){
                $msg += '请输入招募人数<br/>';
            }
            if( !$data['resource_type'] ){
                $msg += '请选择类型<br/>';
            }
            if( !$data['content'] ){
                $msg += '请输入内容<br/>';
            }
            if($data['resource_type'] == 1  ){
                $data['resource'] = I('images');
                if( !$data['resource'] ){
                    $data['resource'] = getImages($data['content']);
                    $data['resource'] = implode(',', $data['resource']);
                }
            }elseif ( $data['resource_type'] == 2){
                $data['resource'] = I('video');
                if( !$data['resource'] ) {
                    $data['resource'] = getVideo($data['content']);
                }
            }
            if( $msg ){
                $this->erroe($msg);
            }
            if( $id ){
                $res = D('PublicBenefit')->update($id, $data);
            }else{
                $res = D('PublicBenefit')->addOne($data);
            }
            if( $res ){
                $this->success();
            }
            $this->error('操作失败！');
        }
        $data = D('PublicBenefit')->getOne($id);
        $this->assign('data', $data);
        $category = D('PublicBenefitCategory')->getAll();
        $tree = \Org\Util\Tree::instance();
        $tree->init($category, 'parent_id');
        $categoryList = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $this->assign('categoryList', $categoryList);

        $this->display();
    }

    public function remove(){
        if( IS_AJAX ){
            $id = I('request.id', 0, 'intval');
            $result = D('PublicBenefit')->remove($id);
            if( $result ){
                $this->success();
            }
            $this->error('操作失败！');
        }
    }
}