<?php
namespace Admin\Controller;

class UnivArticleController extends BaseController {

    protected $type;

    public function __construct()
    {
        parent::__construct();
        $type = I('request.type', 0, 'intval');
        if( !$type ){
            $this->error('非法操作！');
        }
        $this->assign('type', $type);
        $this->type = $type;
    }

    public function index(){
        $order = "id desc";
        $param = $this->getMap();
        $map = [ 'type' => $this->type ];

        $data = $this->pager("UnivArticle", "10", $order, $map, $param);
        $category = D('UnivCategory')->getAll($this->type);
        foreach ($data['data']  as &$item){
            $item['category_name'] = $category[$item['cat_id']]['name'];
            if( $item['resource_type'] == 1 ){
                $item['resource_type'] = '图片';
            }elseif( $item['resource_type'] == 2 ){
                $item['resource_type'] = '视频';
            }
        }
        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->display();
    }

    public function update(){
        $id = I('id', 0, 'intval');
        if( IS_AJAX ){
            $data = [
                'title' => I('title'),
                'cat_id' => I('cat_id', 0, 'intval'),
                'content' => I('content'),
                'sort' => defaultSort(),
                'type' => $this->type,
                'resource_type' => I('resource_type', 0, 'intval'),
            ];

            $msg = '';
            if( !$data['title'] ){
                $msg += '请输入标题！<br/>';
            }
            if( !$data['cat_id'] ){
                $msg += '请选择分类！<br/>';
            }
            if( !$data['resource_type'] || !in_array($data['resource_type'], [1,2]) ){
                $msg += '请选择类型！<br/>';
            }
            if($data['resource_type'] == 1  ){
                $data['resource'] = I('images');
                if( !$data['resource'] ){
                    $data['resource'] = getImages($data['content']);
                    $data['resource'] = implode(',', $data['resource']);
                }
            }elseif ( $data['resource_type'] == 2){
                $data['resource'] = I('video');
                if( !$data['resource'] ){
                    $data['resource'] = getVideo($data['content']);
                }
            }
            if( !$data['content'] ){
                $msg += '请输入内容！<br/>';
            }
            if( $msg ){
                $this->error($msg);
            }

            if( $id ){
                $result = D('UnivArticle')->update($id, $data);
            }else{
                $result = D('UnivArticle')->addOne($data);
            }
            if( $result ){
                $this->success();
            }
            $this->error('操作失败！');
        }
        $data = D('UnivArticle')->getOne($id);
        $data['content'] = htmlspecialchars_decode($data['content']);
        $data['resource_list'] = explode(',', $data['resource']);
        $this->assign('data', $data);

        $category = D('UnivCategory')->getAll($this->type);
        $tree = \Org\Util\Tree::instance();
        $tree->init($category, 'parent_id');
        $categoryList = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $this->assign('categoryList', $categoryList);
        $this->display();
    }

    public function remove(){
        if( IS_AJAX ){
            $id = I('id', 0, 'intval');
            $result = D('UnivNav')->remove($id);
            if( $result ){
                $this->success();
            }
            $this->error('操作失败！');
        }
    }

    public function getMap(){
        $type = I("get.type");
        $p = I("get.p");
        $status = I("get.status");
        $keyword = I("post.keyword");
        $map = array("p"=>$p, "type"=>$type, "status"=>$status, "keyword"=>$keyword);
        return $map;
    }
}