<?php

function DoUrlHandle($thumb){
    if(!empty($thumb) && (strpos(strtolower($thumb), 'http://') === false && strpos(strtolower($thumb), 'https://') === false)){
        $http_type = "http://";
        if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')){
            $http_type = "https://";
        }
        return $http_type.$_SERVER['HTTP_HOST'].$thumb;
    }else{
        return $thumb;
    }
}