<?php
namespace Admin\Controller;
use Think\Controller;
class PensionactivityController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("Pension_activity");
        // $type = I('get.type', 0);
        $map = array();
        $data = $model->where($map)->order("id asc")->select();
        foreach ($data as $key => $value) {
            $data[$key]['name'] = D('pension')->where('id='.$value['pension_id'])->field('title')->find();
        }
		// $params=$this->GetParam();
        $this->assign("data", $data);
		// $this->assign("params", $params);
        $this->assign("map", $this->getMap());
        $this->show();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $model = D("Pension_activity");
        $data["info"] = $model->find($id);

        // $params = $this->GetParam();

        if($doinfo == "modify"){
			$d["title"] = I("post.title",'');
            $d["status"] = I("post.status", 1);
            $d["thumb"] = I("post.thumb");
            $d["starttime"] = I("post.starttime", 0);
            $d["endtime"] = I("post.endtime", 0);
            $d['pension_id'] = I("post.pension_id",0);
            $d['price'] = I("post.price",0);
            $d["content"] = htmlspecialchars_decode(I("post.content"));

            
                if(!is_numeric($d["price"])){
                    $this->error('价格请 输入数字');
                }
            
			
			if(!is_http($d['thumb'])){
				if(!is_file('.'.$d['thumb'])){
					$this->error($d["thumb"].'图片路径无效');
				}
			}


            if($id == 0){
                $d["createtime"] = date("Y-m-d H:i");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
			$this->redirect("Pensionactivity/listad", $this->getMap());
			
        }
        $Pension = D('pension')->select();
        $this->assign("Pension", $Pension);
        $this->assign($data);
        $this->assign("map", $this->getMap());
    	$this->show();
    }


    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("Pension_activity");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("Pensionactivity/listad", $this->getMap());
    }

    public function sortad(){
        $id = I("post.id");
        $ordernum = I("post.ordernum");
        if(count($id)>0){
            $model = D("Pensionactivity");
            foreach ($id as $key=>$val){
                $model->where("id=".$val)->setField("ordernum", $ordernum[$key]);
            }
            $this->redirect("Pensionactivity/listad", $this->getMap());
            exit();
        }else{
            $this->assign("jumpUrl", U("Pensionactivity/listad", $this->getMap()));
            $this->error("没有进行任何操作");
            exit();
        }
    }

    public function getMap(){
        $type = I("get.type");
        $p = I("get.p");
        $map = array("type"=>$type,"p"=>$p);
        return $map;
    }
}