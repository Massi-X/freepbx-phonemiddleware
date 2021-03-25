<?php
header('Content-type: text/xml');
include('helpers/phoneMiddleware.php');

$params = parse_ini_file('config.ini.php');

$info = new phoneMiddleware($params['carddav_url']);
$info->set_auth($params['carddav_user'], $params['carddav_psw']);
$info->set_expire_time($params['cache_expire']);
$info->set_country_code($params['country_code']);
$info->set_header_type(phoneMiddleware::TYPE_XML);

echo $info->getXMLforPhones($params['phone_type']);
