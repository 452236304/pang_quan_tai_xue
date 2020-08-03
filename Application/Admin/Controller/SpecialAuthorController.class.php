<?php
namespace Admin\Controller;

class SpecialAuthorController extends BaseController
{
    public function index(){
        $pagenum = I('p', 1, 'intval');
        $limit = 10;
        $where = [];
        $list = D('SpecialAuthor')->getList($where, ($pagenum-1)*$limit, $limit, 'id', 'desc');
        foreach ($list['rows'] as &$item){
            if( $item['is_recommend'] ){
                $item['recommend'] = '是';
            }else{
                $item['recommend'] = '否';
            }
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
                'name' => I('name'),
                'tag' => I('tag'),
                'avatar' => I('avatar'),
                'sort' => defaultSort(),
                'is_recommend' => I('is_recommend', 0, 'intval'),
            ];

            $msg = '';
            if( !$data['name'] ){
                $msg += '请输入姓名</br>';
            }
            if( !$data['tag'] ){
                $msg += '请输入标签</br>';
            }
            if( !$data['avatar'] ){
                $msg += '请上传头像</br>';
            }
            if( $msg ){
                $this->error($msg);
            }
            if( $id ){
                $result = D('SpecialAuthor')->update($id, $data);
            }else{
                $result = D('SpecialAuthor')->addOne($data);
            }
            if( $result ){
                $this->success();
            }
            $this->error('操作失败！');
        }
        $data = D('SpecialAuthor')->getOne($id);
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
            $where['author_id'] = $id;
            $article = D('SpecialV3')->where($where)->count();
            if( $article ){
                $this->error('该作者还有专题，不可删除！');
            }
            $result = D('SpecialAuthor')->remove($id);
            if( $result ){
                $this->success();
            }
            $this->error('操作失败！');
        }
    }
}