Uninstalling freepbx-Phonemiddleware<br>
<?php
//delete config file and www folder
try {
	if(!deleteDir($_SERVER['DOCUMENT_ROOT'].'/phoneMiddleware'))
		echo 'Unable to delete www module folder.<br>';
	if(!deleteDir(sys_get_temp_dir().'/phonemiddleware'))
		echo 'Unable to delete cache folder (will get deleted after a reboot, no worry).';
}
catch (Exception $e) {
	//ignored
}

function deleteDir($dirPath) {
	$success = true;
  if (!is_dir($dirPath))
    throw new InvalidArgumentException("$dirPath must be a directory");
  if (substr($dirPath, strlen($dirPath) - 1, 1) != '/')
      $dirPath .= '/';
  $files = glob($dirPath . '{,.}[!.,!..]*', GLOB_MARK|GLOB_BRACE);
  foreach ($files as $file) {
      if (is_dir($file))
          $success = deleteDir($file);
      else
        $success = unlink($file);
  }
  $success = rmdir($dirPath);
	return $success;
}
?>
