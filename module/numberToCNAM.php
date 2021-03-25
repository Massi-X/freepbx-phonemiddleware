<?php
header('Content-type: text/plain');
include('helpers/phoneMiddleware.php');

$params = parse_ini_file('config.ini.php');

$info = new phoneMiddleware($params['carddav_url']);
$info->set_auth($params['carddav_user'], $params['carddav_psw']);

if(isset($_GET['number']))
  echo $info->getCNFromPhone($_GET['number']);
else
  echo 'Private Number';
