<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Rene <typo3@rs-softweb.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * Plugin 'Secure Download' for the 'rs_securedownload' extension.
 *
 * @author	Rene <typo3@rs-softweb.de>
 * @package	TYPO3
 * @subpackage	tx_rssecuredownload
 */
class tx_rssecuredownload_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_rssecuredownload_pi1';	// Same as class name
	var $prefixString  = 'tx-rssecuredownload-pi1'; // Same as class name, but "_" replaced with "-" (used for names)
	var $scriptRelPath = 'pi1/class.tx_rssecuredownload_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'rs_securedownload';	// The extension key.
	
	/**
	 * Get value from FlexForm
	 * @param	string	$field: The field name
	 * @param	string	$sheet: The sheet with the field
	 * @return	The value of selected FlexForm field
	 */
	function FF($field, $sheet='') {
		$result = "";
		if (empty($sheet)) {
			$result = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], $field);
		}
		else {
			$result = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], $field, $sheet);
		}
		return $result;
	}

	/**
	 * Returns the data of the User-Computer
	 * @return	Array with the user-computer data
	 */
	function UserDataArray() {
		$result = array();

		$result['accesstime'] = date("U");
		$result['rbrowser']   = $_SERVER['HTTP_USER_AGENT'];
		if ($this->extConf['enableIpLogging'] == 1) {
			$result['ripadress'] = $_SERVER['REMOTE_ADDR'];
		} else {
			$result['ripadress'] = '';
		}
		if ($this->extConf['enableNameLogging'] == 1) {
			$result['rname'] = gethostbyaddr($_SERVER['REMOTE_ADDR']);
		} else {
			$result['rname'] = '';
		}
		return $result;
	}

	/**
	 * Main method of the PlugIn
	 *
	 * @param	string	$content: The content of the PlugIn
	 * @param	array		$conf: The PlugIn Configuration
	 * @return	The content that should be displayed on the website
	 */
	function main($content,$conf)	{
		global $LANG;

		//initiate
		$this->pi_initPIflexForm();
		$db = $GLOBALS['TYPO3_DB'];
		$this->conf = $conf;
		$this->pi_loadLL();

		// get the extension-manager configuration 
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['rs_securedownload']);
		if (!isset($this->extConf['enableIpLogging'])) {
			$this->extConf['enableIpLogging'] = 0;
		}
		if (!isset($this->extConf['enableNameLogging'])) {
			$this->extConf['enableNameLogging'] = 0;
		}

		//set important values
		$pid = $this->cObj->data['pid'];
		$uid = $this->cObj->data['uid'];
		$pathUploads = t3lib_div::dirname($_SERVER['SCRIPT_FILENAME']).'/uploads/tx_rssecuredownload/';
		$pluginPath = t3lib_extMgm::siteRelPath($this->extKey);

		//get data from flexform
		$tryall = $this->FF('tryall','general');
		$downloadid = $this->FF('downloadselect','general');
		
		if ($this->piVars['action'] != "") {
			$action = $this->piVars['action'];
			$givenCode = $this->piVars['code'];
		}
		else {
			$action = "getCode";
		}

		$content = "\n";	

		switch ($action) {

		case "checkCode":
			if ( ($tryall == 1) && ($this->piVars['download'] == 0) ) {
				$code_query = $db->exec_SELECTquery('*', 'tx_rssecuredownload_codes', 'code="' . addslashes($givenCode) . '"' );
			} else {
				$code_query = $db->exec_SELECTquery('*', 'tx_rssecuredownload_codes', 'uid="' . addslashes($downloadid) . '" AND code="' . addslashes($givenCode) . '"' );
			}
			
			

			$log = $this->UserDataArray();
			$log['docid'] = $this->piVars['download'];
			$log['pid'] = $pid;

			if ($db->sql_num_rows($code_query) > 0) {
				$code = $db->sql_fetch_assoc($code_query); 

				if ( ($code['starttime'] == 0 && $code['endtime'] == 0 ) || 
						 ($code['starttime'] == 0 && date('Y-m-d',$code['endtime']) >= date('Y-m-d',strtotime(date("Y-m-d"))) ) || 
						 (date('Y-m-d',$code['starttime']) <= date('Y-m-d',strtotime(date("Y-m-d"))) && $code['endtime'] == 0 ) || 
						 (date('Y-m-d',$code['starttime']) <= date('Y-m-d',strtotime(date("Y-m-d"))) && date('Y-m-d',$code['endtime']) >= date('Y-m-d',strtotime(date("Y-m-d"))) ) )
				{
					if ($code['hidden'] == 0) {
						$content .= '<div class="'.$this->prefixString.'-title"><h2>' . $code['title'] . "</h2></div>\n";	
						if ($code['description'] != "") {
							$content .= '<div class="'.$this->prefixString.'-description">' . $code['description'] . "</div><br />\n";
						}

						if (file_exists('rssecuredownload.php')) {
							$content .= '<div class="'.$this->prefixString.'-filelink"><a href="' . 'rssecuredownload.php">' . $this->pi_getLL('start_download') . '</a></div>' . "\n";
						} else {
							$content .= '<div class="'.$this->prefixString.'-filelink"><a href="' . $pluginPath . 'rssecuredownload.php">' . $this->pi_getLL('start_download') . '</a></div>' . "\n";
						}

						$db->exec_INSERTquery('tx_rssecuredownload_logs', $log );

						//set data in session for download.php
						session_start();
						$_SESSION[$this->prefixId]['file'] = $pathUploads.$code['file']; 
						$_SESSION[$this->prefixId]['title'] = $code['file'];
						break;
					}
					else { 
						$content .= '<div class="'.$this->prefixString.'-error3">' . $this->pi_getLL('error3') . "</div>\n";
						$log['error'] = 3;
					} 
				}
				else { 
					$content .= '<div class="'.$this->prefixString.'-error2">' . $this->pi_getLL('error2') . "</div>\n"; 
					$log['error'] = 2;
				}
			}
			else { 
				$content .= '<div class="'.$this->prefixString.'-error1">' . sprintf($this->pi_getLL('error1'), $givenCode) . "</div>\n"; 
				$log['error'] = 1;
				$log['errortext'] = $givenCode;
			}

			$db->exec_INSERTquery('tx_rssecuredownload_logs', $log );

			$query_local = $db->exec_SELECTquery('*', 'tx_rssecuredownload_codes', 'uid="'.addslashes($downloadid).'"');
			if ($db->sql_num_rows($query_local) == 1) {
				$row = $db->sql_fetch_assoc($query_local);

				$content .= '<div class="'.$this->prefixString.'-title"><h2>' . $row['title'] . "</h2></div>\n";	
				if ($row['description'] != "") {
					$content .= '<div class="'.$this->prefixString.'-description">' . $row['description'] . "</div><br />\n";
				}
				if ($tryall == 1) {
					$downloadid = 0;
				}
				if (!empty($row['file'])) {
					$content .=
						'<div class="'.$this->prefixString.'-codeform">' . "\n" .
						'	<form style="display:inline;" action="' . $this->pi_getPageLink($pid).'" method="post">' . "\n" .
						'		<input type="hidden" name="'.$this->prefixId.'[action]" value="checkCode" />' . "\n" .
						'		<input type="hidden" name="'.$this->prefixId.'[download]" value="' . $downloadid . '" />' . "\n" .
						'		<input type="text" name="'.$this->prefixId.'[code]" value="' . $row['codeprompt'] . '" />&nbsp;&nbsp;' . "\n" .
						'		<input type="submit" value="'.$this->pi_getLL('send_download').'" />' . "\n" .
						'	</form>' . "\n" .
						'</div>' . "\n";
				}
			}
			break;
		case "getCode":
			$query_local = $db->exec_SELECTquery('*', 'tx_rssecuredownload_codes', 'uid="'.addslashes($downloadid).'"');
			if ($db->sql_num_rows($query_local) == 1) {
				$row = $db->sql_fetch_assoc($query_local);

				$content .= '<div class="'.$this->prefixString.'-title"><h2>' . $row['title'] . "</h2></div>\n";	
				if ($row['description'] != "") {
					$content .= '<div class="'.$this->prefixString.'-description">' . $row['description'] . "</div><br />\n";
				}
				if ($tryall == 1) {
					$downloadid = 0;
				}
				if (!empty($row['file'])) {
					$content .=
						'<div class="'.$this->prefixString.'-codeform">' . "\n" .
						'	<form style="display:inline;" action="' . $this->pi_getPageLink($pid).'" method="post">' . "\n" .
						'		<input type="hidden" name="'.$this->prefixId.'[action]" value="checkCode" />' . "\n" .
						'		<input type="hidden" name="'.$this->prefixId.'[download]" value="' . $downloadid . '" />' . "\n" .
						'		<input type="text" name="'.$this->prefixId.'[code]" value="' . $row['codeprompt'] . '" />&nbsp;&nbsp;' . "\n" .
						'		<input type="submit" value="'.$this->pi_getLL('send_download').'" />' . "\n" .
						'	</form>' . "\n" .
						'</div>' . "\n";
				}
			}
			break;
		}


		return $this->pi_wrapInBaseClass($content);
	}
	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rs_securedownload/pi1/class.tx_rssecuredownload_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rs_securedownload/pi1/class.tx_rssecuredownload_pi1.php']);
}

?>
