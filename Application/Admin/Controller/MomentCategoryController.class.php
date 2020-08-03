<?php
namespace Admin\Controller;

class MomentCategoryController extends BaseController {

    public function index(){
        $category = D('MomentCategory')->select();
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
                'parent_id' => I('parent_id', 0 , 'intval'),
                'tag' => I('tag'),
                'sort' => defaultSort(),
            ];
            if( !$data['name'] ){
                E('请输入名称');
            }
            if( $id ){
                $result = D('MomentCategory')->update($id, $data);
            }else{
                $result = D('MomentCategory')->addOne($data);
            }
            if( $result ){
                $this->success();
            }
            $this->error('操作失败！');
        }
        $data = D('MomentCategory')->getOne($id);
        $this->assign('data', $data);
        $category = D('MomentCategory')->getAll();
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
            $children = D('MomentCategory')->children($id);
            if( $children ){
                $this->error('请先删除子分类');
            }
            // 检查该分类下是否有文章
            $where['category_id'] = $id;
            $article = D('Moment')->getList($where);
            if( $article ){
                $this->error('该分类下还有动态，不可删除！');
            }
            $result = D('MomentCategory')->remove($id);
            if( $result ){
                $this->success();
            }
            $this->error('操作失败！');
        }
    }
}