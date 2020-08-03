<?php
namespace Admin\Controller;

class PublicBenefitController extends BaseController
{
    public $status=[0=>'未开始', 1=>'招募中 ', 2=>'已结束'];
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
            $item['category_name'] = D('PublicBenefitCategory')->where(['id'=>$item['cat_id']])->getField('name');
            $item['integral'] =$item['duration']*$item['proportion'];
            $item['status_text'] =$this->status[$item['status']];
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
                'area' => I('area', '天河区'),
                'address' => I('address', ''),
                'sponsor' => I('sponsor', ''),
                'duration' => I('duration', 0, 'intval'),
                'proportion' => I('proportion', 0, 'intval'),
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
            $data['start_time'] = strtotime($data['start_time']);
            if( !$data['recruit_num'] ){
                $msg += '请输入招募人数<br/>';
            }
            if( !$data['resource_type'] ){
                $msg += '请选择类型<br/>';
            }
            if( !$data['area'] ){
                $msg += '选择区域<br/>';
            }
            if( !$data['address'] ){
                $msg += '详细地址不能空<br/>';
            }
            if( !$data['sponsor'] ){
                $msg += '主办单位不能空<br/>';
            }
            if( !$data['content'] ){
                $msg += '请输入内容<br/>';
            }
            if( !$data['duration'] ){
                $msg += '志愿时长不能空<br/>';
            }
            if( !$data['proportion'] ){
                $msg += '积分兑换比例不能空<br/>';
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
        if($data){
            $data['resource_list'] = explode(',',$data['resource']);
            $data['content'] = htmlspecialchars_decode($data['content']);
            $data['start_time'] = date('Y-m-d H:i', $data['start_time']);
        }
        $this->assign('data', $data);
        $category = D('PublicBenefitCategory')->getAll();
        $tree = \Org\Util\Tree::instance();
        $tree->init($category, 'parent_id');
        $categoryList = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $areadata = ['荔湾区', '越秀区', '天河区', '海珠区', '白云区', '黄埔区', '番禺区', '花都区', '南沙区', '从化区', '增城区'];
        $this->assign('areadata',$areadata);
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
    public function status(){
        $id = I('id', 0, 'intval');
        $status = I('stauts', 0, 'intval');
        if(empty($status) && $status>2)
            $this->error('操作状态不符！');
        $data=['status'=>$status];
        if( $id ){
            $res = D('PublicBenefit')->update($id, $data);
        }
        if( $res ){
            $this->success('新增成功', 'PublicBenefit/index');
        }
        $this->error('操作失败！','PublicBenefit/index');
    }
}