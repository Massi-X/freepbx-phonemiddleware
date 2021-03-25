<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$successUpdate = true;

//if submitting form, update database
if(isset($_POST['submit'])) {
	unset($_POST['submit']); //delete submit button name from the config
	$successUpdate = phoneMiddleware_updateConfig($_POST);
}

$version = phoneMiddleware_getVersion();
$config = phoneMiddleware_readConfig();
if(!$config) {
	$config = array(
		'phone_type' => '1',
		'cache_expire' => '10',
		'country_code' => 'IT'
	);
}
$origDir = dirname(__FILE__).'/module';
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")."://$_SERVER[HTTP_HOST]/";
$phonebookInfoLink = $baseUrl."phoneMiddleware/carddavtoXML.php";

//reinstall www folder if requested
if(isset($_POST['reinstall']) || !file_exists(WWW_MODULE_DIR)) {
	try {
		if(file_exists(WWW_MODULE_DIR) && !phoneMiddleware_deleteDir(WWW_MODULE_DIR))
			echo '<div class="alert alert-danger" role="alert">Unable to delete www module folder. Make sure the module has read/write permissions.</div>';
	}
	catch (InvalidArgumentException $e) {
		//The folder isn't there. Doesn't matter
	}
	if(phoneMiddleware_oneTimeInit($origDir, WWW_MODULE_DIR) && phoneMiddleware_updateConfig($config))
			echo '<div class="alert alert-warning" role="alert">Installation successful. The module is now ready to use.</div>';
	else
		echo '<div class="alert alert-danger" role="alert">Failed to initialize working directory. The module won\'t work. Please make sure you have access to the root PHP folder.</div>';
}

if(!$successUpdate)
	echo '<div class="alert alert-danger" role="alert">Failed to save settings. Make sure the module has read/write permissions.</div>';

//check update and notify user
if (phoneMiddleware_checkUpdate())
	echo '<div class="alert alert-warning" role="alert">A new version of the module is available! Please download it from <a target="_blank" href="https://github.com/Massi-X/freepbx-phonemiddleware/releases" style="text-decoration: underline;font-weight: bold;">here</a>.</div>';
?>

<div class="alert alert-info" role="alert">Simple library to read a carddav server and return Inbound CNAM, Outbound CNAM and XML phonebook. For detailed instructions and the user manual see here: <a href="https://github.com/Massi-X/freepbx-phonemiddleware" target="_blank" style="text-decoration: underline;font-weight: bold;">GitHub</a></div>

<h2>Phone MiddleWare by Massi-X</h2>

<div class="fpbx-container">
	<form autocomplete="off" name="edit" action="" method="post" >

	<!--
	<div class="section-title" data-for="general">
		<h3>General Settings</h3>
	</div>
	-->

	<div class="display no-border">
		<div class="section" data-id="general">
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3 control-label">
									<label for="carddav_url">CardDAV url</label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="carddav_url"></i>
								</div>
								<div class="col-md-9">
									<input type="text" class="form-control" id="carddav_url" name="carddav_url" value="<?php echo $config['carddav_url'] ?>">
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="carddav_url-help" class="help-block fpbx-help-block">Path to the carddav server, complete with the path to the address book.</span>
					</div>
				</div>
			</div>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3 control-label">
									<label for="carddav_user">CardDAV user</label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="carddav_user"></i>
								</div>
								<div class="col-md-9">
									<input type="text" class="form-control" id="carddav_user" name="carddav_user" value="<?php echo $config['carddav_user'] ?>">
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="carddav_user-help" class="help-block fpbx-help-block">Your carddav username for connection to the server.</span>
					</div>
				</div>
			</div>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3 control-label">
									<label for="carddav_psw">CardDAV password</label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="carddav_psw"></i>
								</div>
								<div class="col-md-9">
									<input type="password" class="form-control" id="carddav_psw" name="carddav_psw" value="<?php echo $config['carddav_psw'] ?>">
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="carddav_psw-help" class="help-block fpbx-help-block">Your carddav password for connection to the server. ALERT! Password is saved in plain text, so make it unique for this purpose.</span>
					</div>
				</div>
			</div>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3 control-label">
									<label for="phone_type">Phone type</label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="phone_type"></i>
								</div>
								<div class="col-md-9">
										<select class="form-control" id='phone_type' name='phone_type'>
											<option value="0" <?php if ($config['phone_type']==0) echo 'selected' ?>>Phone with limitations</option>
											<option value="1" <?php if ($config['phone_type']==1) echo 'selected' ?>>Phone without limitations</option>
										</select>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="phone_type-help" class="help-block fpbx-help-block">Some fanvil phones (and maybe others) don't allow multiple tags so we have to rely on using all the three tags even if they are wrong (the lib tries the best to match them). More than three numbers are stripped out.</span>
					</div>
				</div>
			</div>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3 control-label">
									<label for="cache_expire">Cache duration</label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="cache_expire"></i>
								</div>
								<div class="col-md-9">
									<input type="number" class="form-control" id="cache_expire" name="cache_expire" value="<?php echo $config['cache_expire'] ?>">
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="cache_expire-help" class="help-block fpbx-help-block">Expiration time (in minutes) of the internal cache. Set 0 to disable.</span>
					</div>
				</div>
			</div>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3 control-label">
									<label for="country_code">Country code</label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="country_code"></i>
								</div>
								<div class="col-md-9">
									<input type="text" class="form-control" id="country_code" name="country_code" value="<?php echo $config['country_code'] ?>">
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="country_code-help" class="help-block fpbx-help-block">The country code used for parsing the numbers in ISO format.</span>
					</div>
				</div>
			</div>
			<div class="element-container" style="margin: 10px 5px 0 5px;">
				<span class="btn-popup" onclick="$('#infoPopup').dialog({modal:true, height: 'auto', width: 'auto', resizable: false, draggable: false});">Click here</span> to see configurations and useful links to connect with Phone Middleware.
			</div>
			<button name="reinstall" type="submit" class="btn btn-danger reinstall">Reinstall www folder</button>
			<input name="submit" type="submit" value="Save &amp; Apply" class="btn-submit">
		</div>
	</div>
	<br>
	</form>
</div>

<div class="alert alert-warning" role="alert">
	<p>If you like my work and you want to donate you can do it here:</p>
	<p>Donate to my PayPal address: <a href="https://www.paypal.com/donate?hosted_button_id=JG8QUZPEH3KBG" rel="nofollow" target="_blank"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" alt="paypal" style="max-width:100%;"></a></p>
	<p>Bitcoin: 1Pig6XJxXGBj1v3r6uLrFUSHwyX8GPopRs</p>
	<p>Monero: 89qdmpDsMm9MUvpsG69hbRMcGWeG2D26BdATw1iXXZwi8DqSEstJGcWNkenrXtThCAAJTpjkUNtZuQvxK1N5xSyb18eXzPD</p>
	<br>
	<p>Thank you!</p>
</div>

<p style="text-align: center; width: 100%; font-size: 11px; font-weight: bold;">Module version: <?php echo $version; ?> - Licensed under The Prosperity Public License 3.0.0</p>
<p style="text-align: center; width: 100%; font-size: 11px;">Makes use of the following libraries: <a href="https://github.com/christian-putzke/CardDAV-PHP/">Carddav-PHP</a> - <a href="https://github.com/nuovo/vCard-parser/">vCard-parser</a> - <a href="https://github.com/giggsey/libphonenumber-for-php/">libphonenumber for PHP</a> - <a href="https://github.com/composer/composer/">Composer</a></p>

<div id="infoPopup" title="Configuration Help" style="display: none;">
	<p style="color: #f44336;"><b>NOTE:</b> ALL LINKS ARE CASE SENSITIVE</p>
	<ul style="list-style: none;">
		<li>
			<span class="title">Link to retrieve the XML phonebook for usage with a phone: </span>
			<br><?php echo $phonebookInfoLink; ?>
		</li>
		<li>
			<span class="title">Data for CIDLookup configuration:</span>
			<br><b>Source type:</b> HTTP or HTTPS
			<br><b>Cache Results:</b> No (the module itself does this)
			<br><b>Host:</b> localhost
			<br><b>Port:</b> <?php echo $_SERVER['SERVER_PORT']; ?>
			<br><b>Path:</b> phoneMiddleware/numberToCNAM.php
			<br><b>Query:</b> number=[NUMBER]
		</li>
		<li>
			<span class="title">Data for CID Superfecta configuration:</span>
			<br><b>Data source:</b> Regular Expressions 1 (everything else disabled)
			<br><b>url:</b> <?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")."://localhost:".$_SERVER['SERVER_PORT']."/phoneMiddleware/numberToCNAM.php?number=\$thenumber"; ?>
			<br><b>reg exp:</b> (.*)
		</li>
	</ul>
</div>

<style>
.btn-popup {
	 font-weight: bold;
	 text-decoration: underline;
	 cursor: pointer;
	 color: #2196f3;
}
.btn-submit {
	float: right;
	margin: 10px 10px 5px 0;
}
.btn.reinstall {
	margin: 10px 0 5px 10px;
}
#infoPopup {
	margin-bottom: 1.5em;
	margin-top: 1em;
}
.ui-dialog {
  position: fixed !important;
  top: 50% !important;
  left: 50% !important;
  transform: translate(-50%, -50%);
}
.ui-dialog ul .title {
  color: #9c27b0;
	font-weight: bold;
}
.ui-dialog li {
	margin-bottom: 5px;
}
.ui-dialog li::before {
  color: #9c27b0;
  content: '▶';
  position: absolute;
  margin-left: -20px;
}
</style>
