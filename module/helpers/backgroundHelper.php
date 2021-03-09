<?php
require 'phoneMiddleware.php';
//call it this way: php exec ... MODE    HTTP_URL USERNAME PASSWORD EXPIRE_TIME PHONETYPE (IF MODE 0)
//                              $argv[1] $argv[2] $argv[3] $argv[4] $argv[5]    $argv[6]
$info = new phoneMiddleware($argv[2]);
$info->set_auth($argv[3], $argv[4]);
$info->set_expire_time($argv[5]);
if ($argv[1] == 0) {
  $info->getXMLforPhones($argv[6], true);
}
else if($argv[1] == 1) {
  $info->getAllVcards(true);
}
