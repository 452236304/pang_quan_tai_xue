<?php
namespace Admin\Controller;

class SpecialCategoryController extends BaseController
{

    public function index(){
        $category = D('SpecialCategory')->order('sort')->select();
        $this->assign('data', $category);
        $this->display();
    }

    public function update(){
        $id = I('id', 0, 'intval');
        if( IS_AJAX ){
            $data = [
                'title' => I('title'),
                'sort' => defaultSort(),
            ];
            if( !$data['title'] ){
                $this->error('请输入名称');
            }
            if( $id ){
                $result = D('SpecialCategory')->update($id, $data);
            }else{
                $result = D('SpecialCategory')->addOne($data);
            }
            if( $result ){
                $this->success();
            }
            $this->error('操作失败！');
        }
        $data = D('SpecialCategory')->getOne($id);
        $this->assign('data', $data);
        $this->display();
    }

    public function remove(){
        if( IS_AJAX ){
            $id = I('request.id');
            if( !$id ){
                $this->error('非法操作');
            }
            // 检查该分类下是否有文章
            $where['category_id'] = $id;
            $article = D('SpecialV3')->where($where)->count();
            if( $article ){
                $this->error('该分类下还有专题，不可删除！');
            }
            $result = D('SpecialCategory')->remove($id);
            if( $result ){
                $this->success();
            }
            $this->error('操作失败！');
        }
    }
}