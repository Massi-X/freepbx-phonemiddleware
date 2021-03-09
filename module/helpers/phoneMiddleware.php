<?php
require_once 'vendor/autoload.php';
require_once 'helpers/vCard-parser/vCard.php';
require_once 'helpers/CardDAV-PHP/carddav.php';

use Brick\PhoneNumber\PhoneNumber;
use Brick\PhoneNumber\PhoneNumberParseException;
use \libphonenumber\NumberParseException;
use Brick\PhoneNumber\PhoneNumberType;

class phoneMiddleware {

	const TYPE_PLAIN = 'plain';
	const TYPE_XML = 'xml';

	private $url = null;
	private $username = null;
	private $password = null;
	private $carddav = null;
	private $expire_time = 10;
	private $country_code = 'IT';

	private $header_type = self::TYPE_PLAIN;

	/**
	 * Constructor
	 * Sets the CardDAV server url
	 *
	 * @param	string	$url	CardDAV server url
	 */
	public function __construct($url = null)
	{
		$this->url = $url;
		$this->carddav = new carddav_backend($url);
	}

	/**
	 * Sets authentication information
	 *
	 * @param	string	$username	CardDAV server username
	 * @param	string	$password	CardDAV server password
	 * @return	void
	 */
	public function set_auth($username, $password)
	{
		$this->carddav->set_auth($username, $password);
		$this->username	= $username;
		$this->password	= $password;
	}

	/**
	 * Sets time to cache the requests
	 *
	 * @param	int	$time	Time in minutes to cache the requests (0 to disable). Default: 10
	 * @return	void
	 */
	public function set_expire_time($time)
	{
		$this->expire_time = $time;
	}

	/**
	 * Sets time to cache the requests
	 *
 	 * @param	string	$code		Country code (default=IT). If the number is given with + country code at the start, it's ignored
	 * @return	void
	 */
	public function set_country_code($code)
	{
		$this->country_code = $code;
	}

	/**
	 * Sets type for "die" errors
	 *
 	 * @param	string	$type		One of TYPE_PLAIN or TYPE_XML. Default TYPE_PLAIN
	 * @return	void
	 */
	public function set_header_type($type)
	{
		$this->header_type = $type;
	}

	/**
	* Gets all vCards in XML format for Fanvil Phones and print them
	*
	* @param	int	$phoneType		Some phones (Fanvil and surely others) don't accept multiple number tags, so we need to use telephone,
	*														mobile and other even if they aren't right. This way we are also limited to 3 numbers. Defaults to 1
	*														(unlimited phones), 0 to enable it
	* @param	bool	$forceUpdate		Whatever to force the update of the database
	*/
	public function getXMLforPhones($phoneType = 1, $forceUpdate = false)
	{
		$file = $this->cache_or_get_file(sys_get_temp_dir().'/phonemiddleware', 'phonebook.xml', null, $this->expire_time);
		$execUpdate = false;

		//check if the file is intact
		if(!$this->isXMLContentValid($file['content']))
			$forceUpdate = true;

		//file is valid (not expired)
		if ($file['valid'] && !$forceUpdate) {
			return $file['content'];
		}
		//file is expired so we exec a background update (but return the old one anyway)
		else if($file['content'] != null && $file['content'] != '' && !$forceUpdate) {
			$execUpdate = true;
		}

		if($execUpdate) {
			exec("php -f helpers/backgroundHelper.php 0 ".$this->url.' '.$this->username." ".$this->password." ".$this->expire_time." ".$phoneType." > /dev/null &");
			return $file['content'];
		}

		//the file doesn't exist or forceupdate = true
		$file = sys_get_temp_dir().'/phonemiddleware/tempXMLWriter.xml';
		$simplified_xml = new XMLWriter();
		fopen($file, "w");
		$simplified_xml->openURI($file);
		$simplified_xml->setIndent(4);
		$simplified_xml->startDocument('1.0', 'utf-8');
		$simplified_xml->startElement('FanvilIPPhoneDirectory');
		$simplified_xml->writeAttribute('clearlight', true);

		$bookArray = array();
		$data = simplexml_load_string($this->getAllVcards());

		foreach ($data->element as $item) {
			$vCard = new vCard(false, $item->vcard);

			$phoneNumbers = $this->arrayTelWalk($vCard->tel);
			if(empty($phoneNumbers))
				continue;

			$tmpArray = array(
				'name' => $vCard->fn[0],
				'phone' => $phoneNumbers
			);

			array_push($bookArray, $tmpArray);
		}

		$this->aasort($bookArray,"name");
		$phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();

		foreach ($bookArray as $card) {
			$simplified_xml->startElement('DirectoryEntry');
			$simplified_xml->writeElement('Name', $card['name']);

			if($phoneType == 0) {
				if(count($card['phone']) == 1) {
					//Sorry, you have a very stupid phone... You can't have a card with only the mobile number so we change it into a standard telephone
					$simplified_xml->writeElement('Telephone', $card['phone'][0]);
				}
				else {
					$usedFields = array(
				    "Telephone" => false,
				    "Mobile" => false,
						"Other" => false
					);

					//this can give some problems if there are duplicates, but for now it works
					$lastElement = end($card['phone']);
					$i = 0;
					foreach ($card['phone'] as $number) {
						//stop our dirctory after using all the tags
						if ($i == 3)
							continue;
						//make sure our stupid phone always get a <telephone> element
						if($number == $lastElement && !$usedFields["Telephone"]) {
							$simplified_xml->writeElement('Telephone', $number);
							continue;
						}
						try {
								$type = $phoneNumberUtil->getNumberType($phoneNumberUtil->parse($number, $this->country_code));
								$writeTo = "Other";
								switch ($type) {
									case PhoneNumberType::FIXED_LINE:
										$writeTo = "Telephone";
										break;
									case PhoneNumberType::MOBILE:
										$writeTo = "Mobile";
										break;
								}
						}
						catch (NumberParseException $e) {
							//this is not a valid number, simply reject it
							continue;
						}
						//check for stupid phones to make sure they receive exactly three different elements
						//TODO:we can do better?
						if ($writeTo == "Telephone" && !$usedFields["Telephone"]) {
							$simplified_xml->writeElement('Telephone', $number);
							$usedFields["Telephone"] = true;
						}
						else if ($writeTo == "Telephone" && !$usedFields["Other"]) {
							$simplified_xml->writeElement('Other', $number);
							$usedFields["Other"] = true;
						}
						else if ($writeTo == "Telephone" && !$usedFields["Mobile"]) {
							$simplified_xml->writeElement('Mobile', $number);
							$usedFields["Mobile"] = true;
						}
						else if ($writeTo == "Mobile" && !$usedFields["Mobile"]) {
							$simplified_xml->writeElement('Mobile', $number);
							$usedFields["Mobile"] = true;
						}
						else if ($writeTo == "Mobile" && !$usedFields["Telephone"]) {
							$simplified_xml->writeElement('Telephone', $number);
							$usedFields["Telephone"] = true;
						}
						else if ($writeTo == "Mobile" && !$usedFields["Other"]) {
							$simplified_xml->writeElement('Other', $number);
							$usedFields["Other"] = true;
						}
						else if ($writeTo == "Other" && !$usedFields["Other"]) {
							$simplified_xml->writeElement('Other', $number);
							$usedFields["Other"] = true;
						}
						else if ($writeTo == "Other" && !$usedFields["Telephone"]) {
							$simplified_xml->writeElement('Telephone', $number);
							$usedFields["Telephone"] = true;
						}
						else if ($writeTo == "Other" && !$usedFields["Mobile"]) {
							$simplified_xml->writeElement('Mobile', $number);
							$usedFields["Mobile"] = true;
						}
						$i++;
					}
				}
			}
			else {
				foreach ($card['phone'] as $number) {
					try {
						$type = $phoneNumberUtil->getNumberType($phoneNumberUtil->parse($number, $this->country_code));
						switch ($type) {
							case PhoneNumberType::FIXED_LINE:
								$simplified_xml->writeElement('Telephone', $number);
								break;
							case PhoneNumberType::MOBILE:
								$simplified_xml->writeElement('Mobile', $number);
								break;
							default:
								$simplified_xml->writeElement('Other', $number);
								break;
						}
					}
					catch (NumberParseException $e) {
					//this is not a valid number, simply reject it
					}
				}
			}
			$simplified_xml->endElement();
		}
		$simplified_xml->endElement();
		$simplified_xml->endDocument();
		$simplified_xml->flush();

		$data = file_get_contents($file);
		unlink($file);
		$this->cache_or_get_file(sys_get_temp_dir().'/phonemiddleware', 'phonebook.xml', $data, $this->expire_time);
		return $data;
	}

	/**
	* Take a number and return caller ID (if found)
	*
	* @param	int	$maxPhones		Max phone numbers the client can handle
	*/
	public function getCNFromPhone($callerNumber)
	{
		//if for some reason the number is empty, return empty
		if($callerNumber == null || $callerNumber == "")
			return "";

		//format number for future use
		try {
				$formattedNumber = PhoneNumber::parse($callerNumber, $this->country_code);
		}
		catch (PhoneNumberParseException $e) {
			//if the number can't be formatted, simply return the number untouched
			return $callerNumber;
		}

		$foundCNAM = null;

		$data = simplexml_load_string($this->getAllVcards());

		foreach ($data->element as $item) {
			$vCard = new vCard(false, $item->vcard);

			$phoneNumbers = $this->arrayTelWalk($vCard->tel);
			if(empty($phoneNumbers))
				continue;
			foreach ($phoneNumbers as $number) {
				try {
						if ($formattedNumber == PhoneNumber::parse($number, $this->country_code)) {
							$foundCNAM = $vCard->fn[0]; //this is the name
							break 2;
						}
				}
				catch (PhoneNumberParseException $e) { }
			}
		}

		if($foundCNAM != null)
			return $foundCNAM;
		else
			//if everything fails, simply return the number untouched
			return $callerNumber;
	}

	/**
	* Build the file needed for creating the xml or getting the number
	*
	* @param	bool	$forceUpdate		Whatever to force the update of the database
	*/
	public function getAllVcards($forceUpdate = false)
	{
		$vcards = $this->cache_or_get_file(sys_get_temp_dir().'/phonemiddleware', 'vcards.xml', null, $this->expire_time);
		$execUpdate = false;

		//check if the file is intact
		if(!$this->isXMLContentValid($vcards['content']))
			$forceUpdate = true;

		//file is valid (not expired)
		if ($vcards['valid'] && !$forceUpdate) {
			return $vcards['content'];
		}
		//file is expired so we exec a background update (but return the old one anyway)
		else if($vcards['content'] != null && $vcards['content'] != '' && !$forceUpdate) {
			$execUpdate = true;
		}

		if($execUpdate) {
			exec("php -f helpers/backgroundHelper.php 1 ".$this->url." ".$this->username." ".$this->password." ".$this->expire_time." > /dev/null &");
			return $vcards['content'];
		}

		//the file doesn't exist or forceupdate = true
		try {
			$vcards = $this->cache_or_get_file(sys_get_temp_dir().'/phonemiddleware', 'vcards.xml', $this->carddav->get(), $this->expire_time);
		}
		catch (Exception $e) {
			$errStr1 = "Something went wrong while retrieving datas from the carddav server. Please make sure your credentials and url are correct. Below the HTTP error code.";
			if($this->header_type == self::TYPE_PLAIN)
				echo $errStr1."\n".$e->getMessage();
			else {
				$xml = new SimpleXMLElement('<xml/>');
		    $xml->addChild('error', $errStr1);
		    $xml->addChild('error', $e->getMessage());
				print($xml->asXML());
			}
			die();
		}
		return $vcards['content'];
	}

	/**
	* Query the the cache for a file, if exists and it is valid return it else return it with a warning
	* if a content is given, store it anyway
	*
	* @param	string	$file				Folder name, the path will bel relative to this file
	* @param	string	$file				File name
	* @param	string	$content				The content to update
	* @param	int	$minutes			Time duration to cache the file
	* @return	array						File is valid or not (bool) and the content
	*/
	private function cache_or_get_file($folder, $fileName, $content = null, $minutes = 60) {
		if(!is_dir($folder))
			mkdir($folder);
		$current_time = time();
		$expire_time = $minutes * 60;

		if ($content != null) {
			$content .= '<!-- cached:  '.time().'-->';
			file_put_contents($folder.'/'.$fileName,$content);
			return array(
				'valid' => false,
				'content' => file_get_contents($folder.'/'.$fileName)
			);
		}

		if(file_exists($folder.'/'.$fileName)) {
			$file_time = filemtime($folder.'/'.$fileName);
			if($current_time - $file_time < $expire_time) {
				return array(
					'valid' => true,
					'content' => file_get_contents($folder.'/'.$fileName)
				);
			}
			else {
				return array(
					'valid' => false,
					'content' => file_get_contents($folder.'/'.$fileName)
				);
			}
		}
		else {
			fopen($folder.'/'.$fileName, "w");
			touch($folder.'/'.$fileName, $current_time - $expire_time);
			return array(
				'valid' => false,
				'content' => null
			);
		}
	}

	/**
	* Sort array in ascending order
	*
	* @param	string	$array			The array
	* @param	string	$key			The key to use for sorter
	* @return	string						The sorted array
	*/
	private function aasort (&$array, $key) {
		$sorter=array();
		$ret=array();
		reset($array);
		foreach ($array as $ii => $va) {
			$sorter[$ii]=$va[$key];
		}
		asort($sorter);
		foreach ($sorter as $ii => $va) {
			$ret[$ii]=$array[$ii];
		}
		$array=$ret;
	}
	/**
	* Walk through arrays and get telephone datas
	*
	* @param	string	$arr			A multidimensional array
	* @return	string						A simple array with all the phones
	*/
	private function arrayTelWalk($arr) {
		if ($arr == null)
			return null;

		$result = array();
		$arr = array_values($arr);
		$length = count($arr);

		for ($i = 0; $i < $length; $i++) {
			$object = $arr[$i];
			if (is_array($object)) {
				$innerRes = $this->arrayTelWalk($object);
				foreach ($innerRes as $value)
					array_push($result, $value);
			}
			else {
				//First strip everything but numbers, + and *
				$object = preg_replace('/[^0-9\+\*]/', '', $object);
				//Then replace + with 00 for better compatibility with asterisk
				$object = preg_replace('/[^0-9]\*/', '00', $object);
				if ($object != '')
					array_push($result, $object);
			}
		}
		return $result;
	}

	/**
	 * @author Francesco Casula <fra.casula@gmail.com>
	 * @param string $xmlContent A well-formed XML string
	 * @param string $version 1.0
	 * @param string $encoding utf-8
	 * @return bool
	 */
	private function isXMLContentValid($xmlContent, $version = '1.0', $encoding = 'utf-8')
	{
			if (trim($xmlContent) == '') {
					return false;
			}

			libxml_use_internal_errors(true);

			$doc = new DOMDocument($version, $encoding);
			$doc->loadXML($xmlContent);

			$errors = libxml_get_errors();
			libxml_clear_errors();

			return empty($errors);
	}
}
