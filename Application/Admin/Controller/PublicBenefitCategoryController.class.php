<?php
namespace Admin\Controller;

class PublicBenefitCategoryController extends BaseController
{
    public function index(){
        $where = [];
        $category = D('PublicBenefitCategory')->where($where)->select();
        $tree = \Org\Util\Tree::instance();
        $tree->init($category, 'parent_id');
        $categoryList = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $this->assign('data', $categoryList);
        $this->display();
    }

    public function update(){
        $id = I('id', 0, 'intval');
        if( IS_AJAX ){
            $data = [
                'name' => I('name'),
                'add_time' => time(),
                'parent_id' => I('parent_id', 0 , 'intval'),
//                'sort' => defaultSort(),
            ];
            if(empty($data['parent_id']))
                $this->error('无法添加顶级类');
            $res =D('PublicBenefitCategory')->where(['id'=>$data['parent_id']])->getField('parent_id');
            if($res)
                $this->error('最多只能添加二级类');
            if( $id ){
                $result = D('PublicBenefitCategory')->update($id, $data);
            }else{
                $result = D('PublicBenefitCategory')->addOne($data);
            }
            if( $result ){
                $this->success();
            }
            $this->error('操作失败！');
        }
        $data = D('PublicBenefitCategory')->getOne($id);
        $this->assign('data', $data);
        $category = D('PublicBenefitCategory')->getAll($this->type);
        $tree = \Org\Util\Tree::instance();
        $tree->init($category, 'parent_id');
        $categoryList = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $parentList = [];
        foreach ($categoryList as $key => $item) {
            if( $id ){
                if( $item['id'] == $id || $item['parent_id'] == $id ){
                    continue;
                }
            }
            $parentList[$key] = $item;
        }
        $this->assign('parentList', $parentList);
        $this->display();
    }

    public function remove(){
        if( IS_AJAX ){
            $id = I('request.id');
            if( !$id ){
                $this->error('非法操作');
            }
            // 检查是否存在子分类
            $children = D('PublicBenefitCategory')->children($id);
            if( $children ){
                $this->error('请先删除子分类');
            }
            // 检查该分类下是否有文章
            $article = D('PublicBenefit')->where(['cat_id'=>$id])->select();
            if( $article ){
                $this->error('该分类下还有活动，不可删除！');
            }
            $result = D('PublicBenefitCategory')->remove($id);
            if( $result ){
                $this->success();
            }
            $this->error('操作失败！');
        }
    }
}