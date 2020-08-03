<?php
namespace Admin\Controller;

class UnivNavBannerController extends BaseController
{


    protected $nav_id;

    public function __construct()
    {
        parent::__construct();
        $this->nav_id = I('request.nav_id', 0, 'intval');
        if( !$this->nav_id ){
            $this->error('非法操作！');
        }
        $this->assign('nav_id', $this->nav_id);
    }

    public function index(){
        $where = ['nav_id' => $this->nav_id];
        $data = D('UnivNavBanner')->where($where)->order('sort')->select();
        foreach ( $data as &$item){
            if( $item['status'] ){
                $item['status'] = '启用';
            }else{
                $item['status'] = '禁用';
            }
        }
        $this->assign('data', $data);
        $this->display();
    }

    public function update(){
        $params = $this->GetParam();
        $this->assign("param", $params);
        $id = I('id', 0, 'intval');
        if( IS_AJAX ){
            $data = [
                'nav_id' => $this->nav_id,
                'title' => I('title'),
                'image' => I('image'),
                'sort' => defaultSort(),
                'status' => I('status',0 ,'intval'),
                'remark' => I('remark'),
            ];
            $param = array("param_type"=>I("param_type", 0), "param_id"=>I("param_id", 0));
            $current = $params["param_".$param["param_type"]];
            if($current){
                if($current["type"] != 10){
                    $data["link"] = $current["link"].$param["param_id"];
                } else{
                    $data["link"] = $param["param_id"];
                }
            }
            $data["param"] = json_encode($param);

            $msg = '';
            if( !$data['title'] ){
                $msg += '请输入标题<br/>';
            }
            if( !$data['image'] ){
                $msg += '请上传图片<br/>';
            }
            if( $msg ){
                $this->error($msg);
            }

            if( $id ){
                $result = D('UnivNavBanner')->update($id, $data);
            }else{
                $result = D('UnivNavBanner')->addOne($data);
            }
            if( $result ){
                $this->success();
            }
            $this->error('操作失败！');
        }
        $data = D('UnivNavBanner')->getOne($id);
        $data["param"] = json_decode($data["param"], true);
        $this->assign('info', $data);
        $this->display();
    }

    public function remove(){
        if( IS_AJAX ){
            $id = I('request.id', 0, 'intval');
            $result = D('UnivNavBanner')->remove($id);
            if( $result ){
                $this->success();
            }
            $this->error('操作失败！');
        }
    }

    private function GetParam(){
        $data = array(
            "param_1"=>array("title"=>"商品详情", "type"=>"1", "link"=>"/pages/ShoppingMall/product/product?id="),
            "param_2"=>array("title"=>"配餐详情", "type"=>"2", "link"=>"/pages/ShoppingMall/Letter_details/Letter_details?type=0&id="),
            "param_3"=>array("title"=>"1元上门定制适老家具", "type"=>"3", "link"=>"/pages/ShoppingMall/Customized/Customized?id="),
            "param_4"=>array("title"=>"适老改造详情", "type"=>"4", "link"=>"/pages/ShoppingMall/Letter_details/Letter_details?type=2&id="),
            "param_5"=>array("title"=>"社交详情", "type"=>"5", "link"=>"/pagesB/search/search?id="),
            "param_6"=>array("title"=>"机构详情", "type"=>"6", "link"=>"/pagesA/institutionalCare/details/details?id="),
            "param_7"=>array("title"=>"家护师详情", "type"=>"7", "link"=>"/pagesA/AquaFresh/AquaFresh_details/AquaFresh_details?id="),
            "param_8"=>array("title"=>"服务项目详情", "type"=>"8", "link"=>"/pages/chaperonage/SetMeal/SetMeal?id="),
            "param_9"=>array("title"=>"健康详情", "type"=>"9", "link"=>"/pages/health/classify/classify?type="),
            "param_10"=>array("title"=>"其它链接", "type"=>"10", "link"=>""),
            "param_11"=>array("title"=>"资讯详情", "type"=>"11", "link"=>"/pages/classDetalis/details/details?id="),
            "param_12"=>array("title"=>"健康管理页", "type"=>"12", "link"=>"/pages/health/health/health?id="),
            "param_13"=>array("title"=>"服务项目列表", "type"=>"13", "link"=>"/pages/index/server/server?id="),
            "param_14"=>array("title"=>"一元参观", "type"=>"14", "link"=>"/pagesA/institutionalCare/list/list?id=1&demo="),
            "param_15"=>array("title"=>"折扣长住", "type"=>"15", "link"=>"/pagesA/institutionalCare/list/list?id=2&demo="),
            "param_16"=>array("title"=>"养老院", "type"=>"16", "link"=>"/pagesA/institutionalCare/yanglaojigou/yanglaojigou?type=1&title=养老院&id="),
            "param_17"=>array("title"=>"护理院", "type"=>"17", "link"=>"/pagesA/institutionalCare/yanglaojigou/yanglaojigou?type=2&title=护理院&id="),
            "param_18"=>array("title"=>"旅居养老", "type"=>"18", "link"=>"/pagesA/institutionalCare/yanglaojigou/yanglaojigou?type=3&title=旅居养老&id="),
            "param_19"=>array("title"=>"养老科普", "type"=>"19", "link"=>"/pagesB/search-list/search-list?pid=0&id=1&searchid=1&oTitle=养老科普&id="),
            "param_20"=>array("title"=>"老人大学", "type"=>"20", "link"=>"/pagesB/search-list/search-list?searchid=2&oTitle=老人大学&id="),
            "param_21"=>array("title"=>"优惠券", "type"=>"21", "link"=>"/pagesA/myself/myCoupon/myCoupon?id="),
            "param_22"=>array("title"=>"邀请好友", "type"=>"22", "link"=>"/pages/index/invitation/invitation?id="),
            "param_23"=>array("title"=>"招聘", "type"=>"23", "link"=>"/pages/recruit/recruit?id="),
            "param_24"=>array("title"=>"应用内链接", "type"=>"24", "link"=>""),
        );
        return $data;
    }
}