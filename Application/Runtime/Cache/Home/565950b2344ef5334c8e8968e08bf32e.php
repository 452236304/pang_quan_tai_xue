<?php if (!defined('THINK_PATH')) exit();?>订单类型:<br>
<?php if(is_array($title["title1"])): foreach($title["title1"] as $key=>$vo): echo ($key); ?>|<?php echo ($vo); ?><br><?php endforeach; endif; ?>
科目类型:<br>
<?php if(is_array($title["title2"])): foreach($title["title2"] as $key=>$vo): echo ($key); ?>|<?php echo ($vo); ?><br><?php endforeach; endif; ?>
学历背景:<br>
<?php if(is_array($title["title3"])): foreach($title["title3"] as $key=>$vo): echo ($key); ?>|<?php echo ($vo); ?><br><?php endforeach; endif; ?>
引用数:<br>
<?php if(is_array($title["title4"])): foreach($title["title4"] as $key=>$vo): echo ($key); ?>|<?php echo ($vo); ?><br><?php endforeach; endif; ?>
写作格式:<br>
<?php if(is_array($title["title5"])): foreach($title["title5"] as $key=>$vo): echo ($key); ?>|<?php echo ($vo); ?><br><?php endforeach; endif; ?>