<?php
namespace CApi\Controller;
use Think\Controller;
//服务
class ServiceController extends BaseController {
    /**
     * 首页
     * @return array
     */
    public function index(){
        // 轮播
        $bannermodel = D("banner");
        $map = array("status"=>1, "type"=>13);
        $banner = $bannermodel->where($map)->order("ordernum asc")->select();
        foreach ($banner as $k=>$v) {
            $v["image"] = $this->DoUrlHandle($v["image"]);

            if($v["param"]){
                $v["param"] = json_decode($v["param"], true);
            } else{
                $v["param"] = array("param_type"=>"-1", "param_id"=>"");
            }

            $banner[$k] = $v;
        }

        // 热搜
        $hot_search = D('hot_search')->field('id,keyword')->order('weight DESC')->limit(4)->select();

        // 电话咨询
        $aboutmodel = D("about");
        $map = [];
        $map = array("status"=>1, "id"=>4);
        $about = $aboutmodel->where($map)->find();
        if($about){
            $about = array("title"=>$about["title"], "tel"=>$about["content"]);
        }

        // 分类
        $category = D('service_category')->where('status=1')->order('ordernum ASC')->limit(4)->select();

        // 明显家护师
        $nurse = $this->nurse();

        // 我们擅长的护理

        // 首页设置
        $set = D('service_set')->find();
        if(!empty($set))
        {
            $set["bg_url"] = !empty($set["bg_url"]) ? $this->DoUrlHandle($set["bg_url"]) : '';

            if($set["status"] == 1)
            {
                $set["video_url"] = $this->DoUrlHandle($set["video_url"]);
            }
            else
            {
                $set["video_url"] = '';
            }
        }

        return array('banner'=>$banner,'hot_search'=>$hot_search,'about'=>$about,'category'=>$category,'nurse'=>$nurse,'set'=>$set);
    }

    /**
     * 品质服务
     * @return array
     */
    public function lists()
    {
        $row = I('get.row',6);
        $page = I('get.page',1);
        $begin = ($page-1)*$row;

        $service = D('service_project');

        $map = "(s.status=1 or s.assess=1 or (s.assess=0 and p.status=1))";
        $count = $service->alias('s')->join('LEFT JOIN sj_service_project_level_price as p on s.id=p.projectid')->where($map)->count();
        $totalpage = ceil($count/$row);

        $order = "s.top desc, s.ordernum asc, s.sales desc, s.browser_count desc";
        $list = $service->alias('s')->join('LEFT JOIN sj_service_project_level_price as p on s.id=p.projectid')->field('s.*')->where($map)->order($order)->limit($begin, $row)->select();

        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        foreach($list as $k=>$v){
            $v["thumb"] = $this->DoUrlHandle($v["thumb"]);

            if(empty($v["label"])){
                $v["label"] = array("attr1"=>"", "attr2"=>"");
            } else{
                $v["label"] = json_decode($v["label"], true);
            }
            $v["market_price"] = getNumberFormat($v["market_price"]);
            $v["price"] = ($v["price"]);

            //时长类型 - 月
            if($v["time_type"] == 3){
                $v["time"] = 1;
            }
            $v['type'] = 1;
            $list[$k] = $v;
        }

        return $list;
    }

    /**
     * 明显家护师
     */
    public function nurse()
    {
        //星选家护师
        $usermodel = D("user_role");
        $order = "up.recommend desc, up.top desc, up.comment_percent desc";
        $map = array("ur.role"=>3, "u.status"=>200, "up.status"=>1);
        //剔除爽约
        $plane_time = date("Y-m-d H:i:s", strtotime("-3 month", time()));
        $map["up.plane_time"] = array(
        	array("exp", "is null"),
        	array("lt", $plane_time),
        	"or"
        );
        $user = $usermodel->alias("ur")->join("left join sj_user as u on ur.userid=u.id")->join("left join sj_user_profile as up on ur.userid=up.userid")
        	->field("u.id,ur.role,u.avatar,up.realname,up.gender,up.birth,up.mobile,up.major_level,up.service_level,up.work_year,up.education,up.major,up.language,up.comment_percent")->where($map)->order($order)->limit(6)->select();
        foreach ($user as $k=>$v) {
        	$v["avatar"] = $this->DoUrlHandle($v["avatar"]);
        	
        	//性别
        	switch($v['gender']){
        		case 0:
        			$v['gender']='保密';
        			break;
        		case 1:
        			$v['gender']='男';
        			break;
        		case 2:
        			$v['gender']='女';
        			break;
        	}
        	
        	//年龄
        	$v["age"] = getAgeMonth($v["birth"]);
        
        	$user[$k] = $v;
        }

        return $user;
    }

}