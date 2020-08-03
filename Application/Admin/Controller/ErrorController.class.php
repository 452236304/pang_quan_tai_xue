<?php
namespace Admin\Controller;
use Think\Controller;
class ErrorController extends BaseController {

    public function index(){
        $message = mb_convert_encoding($_GET['message'], "UTF-8", "gb2312");

        $this->redirect("Home/Error/index", array("message"=>$message));
    }

}
?>