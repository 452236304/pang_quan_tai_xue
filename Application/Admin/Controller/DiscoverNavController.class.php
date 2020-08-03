<?php
namespace Admin\Controller;

class DiscoverNavController extends BaseController
{
    public function index(){
        $data = D('DiscoverNav')->order('sort')->select();
        $this->assign('data', $data);
        $this->display();
    }

    public function update(){
        $id = I('id', 0, 'intval');
        if( IS_AJAX ){
            $data = [
                'title' => I('title'),
                'sort' => defaultSort(),
                'recommend_type' => I('recommend_type', 0, 'intval'),
            ];

            $msg = '';
            if( !$data['title'] ){
                $msg = '请输入标题<br/>';
            }
            if( $msg ){
                $this->error($msg);
            }
            if( $data['recommend_type'] == 1 ){
                $data['recommend'] = implode(',', I('service'));
            }else if( $data['recommend_type'] == 2 ){
                $data['recommend'] = implode(',', I('product'));
            }else if( $data['recommend_type'] == 3 ){
                $data['recommend'] = implode(',', I('user'));
            }
            if( $id ){
                $result = D('DiscoverNav')->update($id, $data);
            }else{
                $result = D('DiscoverNav')->addOne($data);
            }
            if( $result ){
                $this->success();
            }
            $this->error('操作失败！');
        }
        $data = D('DiscoverNav')->getOne($id);
        $data['recommend'] = explode(',',  $data['recommend']);
        $this->assign('data', $data);

        // 服务
        $serviceProject = D('ServiceProject')->select();
        $this->assign('service', $serviceProject);

        // 商品
        $where = [ 'status' => 1 ];
        $product = D('Product')->where($where)->select();
        $this->assign('product', $product);

        // 家护师
        $where = [ 'UR.status' => 1, 'UR.role' => 3 ];
        $field = ['U.id', 'realname'];
        $user = D('UserRole')->alias('UR')
            ->join('__USER__ AS U ON U.id = UR.userid')
            ->where($where)
            ->field($field)
            ->select();
        $this->assign('user', $user);

        $this->display();
    }

    public function remove(){
        if( IS_AJAX ){
            $id = I('request.id', 0, 'intval');
            $result = D('DiscoverNav')->remove($id);
            if( $result ){
                $this->success();
            }
            $this->error('操作失败！');
        }
    }
}