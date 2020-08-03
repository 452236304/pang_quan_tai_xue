<?php
namespace Admin\Controller;
use Think\Controller;
class WxcodeController extends BaseController {
    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 1);
    	$doinfo = I("get.doinfo");
        $model = D("wxcode");
        $data["info"] = $model->find($id);
        if($doinfo == "modify"){
			$d["thumb"] = I("thumb");
			if(empty($d["thumb"])){
				$this->error('请上传图片');
			}
			if(!is_http($d['thumb'])){
				if(!is_file('.'.$d['thumb'])){
					$this->error($d["thumb"].'图片路径无效');
				}
			}
			$d['remark'] = I("remark");
			$d['updatetime'] = date('Y-m-d H:i:s');
            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
				$d["createtime"] = date("Y-m-d H:i:s");
                $model->add($d);
            }
            
            $this->redirect("Wxcode/modifyad");
        }
        $this->assign($data);
    	$this->display();
    }
}
