<?php
//deny access for all the functions below
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

define('WWW_MODULE_DIR', $amp_conf['AMPWEBROOT'].'/phoneMiddleware');

//check for settings and return
function phoneMiddleware_readConfig() {
	try {
		return parse_ini_file(WWW_MODULE_DIR.'/config.ini.php');
	}
	catch(Exception $e) {
		return false;
	}
}

//store settings
function phoneMiddleware_updateConfig($assoc_arr, $has_sections = FALSE) {
	$path = WWW_MODULE_DIR.'/config.ini.php';
  $content = ";<?php die(); ?>\n";
  if ($has_sections) {
    foreach ($assoc_arr as $key=>$elem) {
      $content .= "[".$key."]\n";
      foreach ($elem as $key2=>$elem2) {
        if(is_array($elem2)) {
            for($i=0;$i<count($elem2);$i++)
              $content .= $key2."[] = \"".$elem2[$i]."\"\n";
        }
        else if($elem2=="")
					$content .= $key2." = \n";
        else
					$content .= $key2." = \"".$elem2."\"\n";
      }
    }
  }
  else {
	  foreach ($assoc_arr as $key=>$elem) {
      if(is_array($elem)) {
        for($i=0;$i<count($elem);$i++)
          $content .= $key."[] = \"".$elem[$i]."\"\n";
      }
      else if($elem=="")
				$content .= $key." = \n";
      else
				$content .= $key." = \"".$elem."\"\n";
	  }
  }

  if (!$handle = fopen($path, 'w'))
      return false;

  $success = fwrite($handle, $content);
  fclose($handle);

  return $success;
}

//initialize module by copying the required files to the root www folder
function phoneMiddleware_oneTimeInit($src, $dst) {
	$dir = opendir($src);

	if (!file_exists($dst) && !mkdir($dst))
		return false;

  while(false !== ($file = readdir($dir))) {
    if (($file != '.') && ($file != '..')) {
      if (is_dir($src.'/'.$file)) {
				if(!phoneMiddleware_oneTimeInit($src.'/'.$file, $dst.'/'.$file))
					return false;
				}
			else if(!copy($src.'/'.$file, $dst.'/'.$file))
				return false;
    }
  }
  closedir($dir);
	return true;
}

function phoneMiddleware_deleteDir($dirPath) {
	$success = true;
  if (!is_dir($dirPath))
    throw new InvalidArgumentException("$dirPath must be a directory");
  if (substr($dirPath, strlen($dirPath) - 1, 1) != '/')
      $dirPath .= '/';
  $files = glob($dirPath . '{,.}[!.,!..]*', GLOB_MARK|GLOB_BRACE);
  foreach ($files as $file) {
      if (is_dir($file))
          $success = phoneMiddleware_deleteDir($file);
      else
        $success = unlink($file);
  }
  $success = rmdir($dirPath);
	return $success;
}

//get version
function phoneMiddleware_getVersion() {
	return simplexml_load_file("modules/phonemiddleware/module.xml")->version;
}

//check if there is an update
function phoneMiddleware_checkUpdate() {
	try {
		$onlineVersion = simplexml_load_file("https://raw.githubusercontent.com/Massi-X/freepbx-phonemiddleware/main/module.xml")->version;
	}
	catch (Exception $e) {
		return false;
	}
	return version_compare(phoneMiddleware_getVersion(),$onlineVersion) === -1;
}
