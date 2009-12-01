<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Rene <rene.staeker@freenet.de>
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


	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');

$LANG->includeLLFile('EXT:rs_securedownload/mod1/locallang.xml');
require_once(PATH_t3lib.'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]



/**
 * Module 'Secure Download' for the 'rs_securedownload' extension.
 *
 * @author	Rene <rene.staeker@freenet.de>
 * @package	TYPO3
 * @subpackage	tx_rssecuredownload
 */
class  tx_rssecuredownload_module1 extends t3lib_SCbase {
	var $pageinfo;

	var $pagelist = '';
	var $csvContent = array();
	var $currentRowNumber = 0;
	var $currentColNumber = 0;

	/**
	 * Initializes the Module
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		parent::init();

		// get the subpages list
		if ($this->id) {
			$this->pagelist = strval($this->id);
			$this->getSubPages($this->id);
		}

		// init the first csv-content row
		$this->csvContent[0] = array();

		// check, if we should render a csv-table
		$this->csvOutput = (t3lib_div::_GET('format') == 'csv') ? true : false;
		// get the code for csv-table output
		$this->csvOutputCode = t3lib_div::_GET('code');
		// get the type for csv-table output (0=all, 1=failure, 2=correct) )
		$this->csvOutputType = t3lib_div::_GET('type');

		// check, if we should delete rows
		$this->delete = (t3lib_div::_GET('delete') == '1') ? true : false;
		// get the code for delete
		$this->deleteCode = t3lib_div::_GET('code');
		// get the type for delete (0=all, 1=failure, 2=correct) )
		$this->deleteType = t3lib_div::_GET('type');
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig()	{
		global $LANG;
		$this->MOD_MENU = Array (
			'function' => Array (
				'1' => $LANG->getLL('function1'),
				'2' => $LANG->getLL('function2'),
				'3' => $LANG->getLL('function3'),
			)
		);
		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return	[type]		...
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

//		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{
		if (($this->id && $access))	{

			// Draw the header.
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form action="" method="POST">';

			// JavaScript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
				<script type="text/javascript">
					function goto_id(ancor){
						if(window.location.hash==""){
							window.location.href = ancor;
						}
					}
				</script>
				<script type="text/javascript">
					function set_bg_color(color){
						alert(color);
					}
				</script>
				<script type="text/javascript">
					function confirm_delete(text,link){
						alert(text);
					}
				</script>';
			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = 0;
				</script>
				<script language="javascript" type="text/javascript">
					goto_id(\'#rs'.t3lib_div::_GET('expand').'\');
				</script>
			';

			$headerSection = $this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br />'.$LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.t3lib_div::fixed_lgd_pre($this->pageinfo['_thePath'],50);

			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->section('',$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'])));
			$this->content.=$this->doc->divider(5);

			// Delete, if requested
			if ($this->delete) $this->markDeleted();
			
			// Render content:
			$this->moduleContent();

			// ShortCut
			if ($BE_USER->mayMakeShortcut())	{
				$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
			}

			$this->content.=$this->doc->spacer(10);
		} else {
				// If no access or if ID == zero

			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;

			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$LANG->getLL('please_select_page');
			$this->content.=$this->doc->spacer(10);
		}
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content.=$this->doc->endPage();

		if ($this->csvOutput) {
			$this->createCSV();
			$this->outputCSV();
		} else {
			echo $this->content;
		}
	}

	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function moduleContent()	{
		global $LANG;
		$db =& $GLOBALS['TYPO3_DB'];

		$image_plus = '<image src="../res/plus.gif">';
		$image_minus = '<image src="../res/minus.gif">';
		$image_delete = '<image src="../res/garbage.gif">';
		$image_export = '<image src="../res/export.gif">';
		$color1 = '#CCCCCC';
		$color2 = '#DDDDDD';
		$tableline_spacer = '<tr><td>&nbsp;</td><td colspan="3" BGCOLOR="%s"><hr></td></tr>';
		$tableline_title1 = '<tr BGCOLOR="%s"><td width="10" align="center" valign="top">%s</td><td width="10" valign="top">%s&nbsp;</td><td colspan="2">%s&nbsp;</td></tr>';
		$tableline_title2 = '<tr BGCOLOR="%s"><td width="10" align="center" valign="top">%s</td><td width="10" valign="top">%s&nbsp;</td><td colspan="2">%s&nbsp;</td></tr>';
		$tableline_title3 = '<tr BGCOLOR="%s"><td width="10" align="center" valign="top">&nbsp;</td><td align="center" valign="top" colspan="3">%s</td></tr>';
		$tableline_download1 = '<tr><td rowspan="%s">&nbsp;</td><td BGCOLOR="%s">%s&nbsp;</td><td BGCOLOR="%s">%s&nbsp;</td></tr>';
		$tableline_download2 = '<tr BGCOLOR="%s"><td>%s&nbsp;</td><td>%s&nbsp;</td></tr>';
		$tableline_download3 = '<tr><td>&nbsp;</td><td colspan="3" BGCOLOR="%s"><center><b>%s&nbsp;</b></center></td></tr>';
		
		$query_pagelist = ' AND pid IN ('.$this->pagelist.')';
		$query_pagelist = '';

		switch((string)$this->MOD_SETTINGS['function'])	{
			case 1:
				// override table structure only for this case
				$tableline_title1 = '<tr BGCOLOR="%s"><td width="10" valign="top">%s&nbsp;</td><td>%s&nbsp;</td></tr>';
				$tableline_title2 = '<tr BGCOLOR="%s"><td align="center" colspan="2">%s</td></tr>';
				$tableline_download1 = '<tr BGCOLOR="%s"><td>%s</td><td>%s</td></tr>';
				$tableline_download3 = '<tr BGCOLOR="%s"><td align="center" colspan="10"><b>%s</b></td></tr>';
				$tableline_inline = '<tr onmouseover="this.style.backgroundColor=\'#AAAAAA\'" onmouseout="this.style.backgroundColor=\'\'"><td>%s</td><td width="50" align="right">%s</td><td width="50">%s</td><td width="50" align="center">%s</td><td width="50" align="center">%s</td><td width="50" align="center">%s</td></tr>';

//				$codes_query = $db->exec_SELECTquery('*', 'tx_rssecuredownload_codes','pid IN ('.$this->pagelist.')');
				$codes_query = $db->exec_SELECTquery('*', 'tx_rssecuredownload_codes','');
				$count_codes = $db->sql_num_rows($codes_query);

				if ($count_codes > 0) {

					$color = $color1;
					$content = '<table border="0" cellpadding="2" width="100%">';
					for ($i_codes = 1; $i_codes <= $count_codes; $i_codes++) {
						if ($color == $color1) {
							$color = $color2;
						} else {
							$color = $color1;
						}
						$codes_result = $db->sql_fetch_assoc($codes_query);

						$logs_query = $db->exec_SELECTquery('error', 'tx_rssecuredownload_logs','docid='.$codes_result['uid'].' AND deleted='.'0'.$query_pagelist);
						$count_logs = $db->sql_num_rows($logs_query);

						$content .= sprintf($tableline_title1,$color,$LANG->getLL('table_title'),$codes_result['title']);
						$content .= sprintf($tableline_title1,$color,$LANG->getLL('table_description'),$codes_result['description']);

						if ($codes_result['deleted'] == 1) {
							$content .= sprintf($tableline_title2,$color,'!! <span style="color: red;">'.$LANG->getLL('table_deleted').'</span> !!');
						}

						if ($count_logs > 0) {
							$logs_query = $db->exec_SELECTquery('error', 'tx_rssecuredownload_logs','docid='.$codes_result['uid'].' AND NOT error='.'0'.' AND deleted='.'0'.$query_pagelist);
							$count_logs_failure = $db->sql_num_rows($logs_query);
							$count_logs_failure_text = ($count_logs_failure == 1) ? $LANG->getLL('table_entry') : $LANG->getLL('table_entries');
							$logs_query = $db->exec_SELECTquery('error', 'tx_rssecuredownload_logs','docid='.$codes_result['uid'].' AND error='.'0'.' AND deleted='.'0'.$query_pagelist);
							$count_logs_correct = $db->sql_num_rows($logs_query);
							$count_logs_correct_text = ($count_logs_correct == 1) ? $LANG->getLL('table_entry') : $LANG->getLL('table_entries');
							$count_logs_all = $count_logs_failure + $count_logs_correct;
							$count_logs_all_text = ($count_logs_all == 1) ? $LANG->getLL('table_entry') : $LANG->getLL('table_entries');

							$detail_link_correct = '<a href="?id='.$this->id.'&SET[function]=3&expand='.$codes_result['uid'].'">'.'<acronym style="border: none" title="'.$LANG->getLL('table_detail').'">'.$image_plus.'</acronym>'.'</a>';
							$detail_link_failure = '<a href="?id='.$this->id.'&SET[function]=2&expand='.$codes_result['uid'].'">'.'<acronym style="border: none" title="'.$LANG->getLL('table_detail').'">'.$image_plus.'</acronym>'.'</a>';

							$export_link_correct = '<a href="?format=csv&code='.$codes_result['uid'].'&type=2">'.'<acronym style="border: none" title="'.$LANG->getLL('table_export').'">'.$image_export.'</acronym>'.'</a>';
							$export_link_failure = '<a href="?format=csv&code='.$codes_result['uid'].'&type=1">'.'<acronym style="border: none" title="'.$LANG->getLL('table_export').'">'.$image_export.'</acronym>'.'</a>';
							$export_link_all     = '<a href="?format=csv&code='.$codes_result['uid'].'&type=0">'.'<acronym style="border: none" title="'.$LANG->getLL('table_export').'">'.$image_export.'</acronym>'.'</a>';
//Overwrite for temporary disabled
							$export_link_correct = '<acronym style="border: none" title="'.$LANG->getLL('table_export').' '.$LANG->getLL('coming_soon').'">'.$image_export.'</acronym>';
							$export_link_failure = '<acronym style="border: none" title="'.$LANG->getLL('table_export').' '.$LANG->getLL('coming_soon').'">'.$image_export.'</acronym>';
							$export_link_all     = '<acronym style="border: none" title="'.$LANG->getLL('table_export').' '.$LANG->getLL('coming_soon').'">'.$image_export.'</acronym>';

							$delete_link_correct = '<a href="?id='.$this->id.'&delete=1&code='.$codes_result['uid'].'&type=2" onclick="return confirm(\''.$LANG->getLL('table_delete').' '.$count_logs_correct.' '.$count_logs_correct_text.'?\')">'.'<acronym style="border: none" title="'.$LANG->getLL('table_delete').' '.$count_logs_correct.' '.$count_logs_correct_text.'">'.$image_delete.'</acronym>'.'</a>';
							$delete_link_failure = '<a href="?id='.$this->id.'&delete=1&code='.$codes_result['uid'].'&type=1" onclick="return confirm(\''.$LANG->getLL('table_delete').' '.$count_logs_failure.' '.$count_logs_failure_text.'?\')">'.'<acronym style="border: none" title="'.$LANG->getLL('table_delete').' '.$count_logs_failure.' '.$count_logs_failure_text.'">'.$image_delete.'</acronym>'.'</a>';
							$delete_link_all     = '<a href="?id='.$this->id.'&delete=1&code='.$codes_result['uid'].'&type=0" onclick="return confirm(\''.$LANG->getLL('table_delete').' '.$count_logs_all.' '.$count_logs_all_text.'?\')">'.'<acronym style="border: none" title="'.$LANG->getLL('table_delete').' '.$count_logs_all.' '.$count_logs_all_text.'">'.$image_delete.'</acronym>'.'</a>';

							$content_inline = '<table border="0" cellpadding="1" cellspacing="0" width="100%">';
							$content_inline .= sprintf($tableline_inline,$LANG->getLL('table_access_success'),$count_logs_correct,$count_logs_correct_text,$detail_link_correct,$export_link_correct,$delete_link_correct);
							$content_inline .= sprintf($tableline_inline,$LANG->getLL('table_access_failure'),$count_logs_failure,$count_logs_failure_text,$detail_link_failure,$export_link_failure,$delete_link_failure);
							$content_inline .= sprintf($tableline_inline,$LANG->getLL('table_access_all'),$count_logs_all,$count_logs_all_text,'&nbsp;',$export_link_all,$delete_link_all);							$content_inline .= '</table>';
							$content .= sprintf($tableline_download1,$color,$LANG->getLL('table_access'),$content_inline);
						} else {
							$content .= sprintf($tableline_download3,$color,$LANG->getLL('no-download-yet'));
						}
					}
					$content .= '</table>';
				} else {
					$error = $LANG->getLL('no-download-defined');
				}

				if (!empty($error)) { 
					$content .= $error; 
				}
				$this->content.=$this->doc->section($LANG->getLL('function1-description'),$content,0,1);

			break;
			case 2:
				$codes_query = $db->exec_SELECTquery('*', 'tx_rssecuredownload_codes','');
				$count_codes = $db->sql_num_rows($codes_query);

				if ($count_codes > 0) {

					$color = $color1;
					$content = '<table border="0" cellpadding="2" width="100%">';
					for ($i_codes = 1; $i_codes <= $count_codes; $i_codes++) {
						if ($color == $color1) {
							$color = $color2;
						} else {
							$color = $color1;
						}
						$codes_result = $db->sql_fetch_assoc($codes_query);

						$logs_query = $db->exec_SELECTquery('*', 'tx_rssecuredownload_logs','docid='.$codes_result['uid'].' AND NOT error='.'0'.' AND deleted='.'0');
						$count_logs = $db->sql_num_rows($logs_query);

						$anchor = '<a name="rs'.$codes_result['uid'].'"></a>';

						if ($count_logs == 1) {
							$count_text = '<acronym title="'.$count_logs.' '.$LANG->getLL('table_entry').'">';
						} else {
							$count_text = '<acronym title="'.$count_logs.' '.$LANG->getLL('table_entries').'">';
						}

						if (t3lib_div::_GET('expand') == $codes_result['uid']) {
							$link_image = $anchor.'<a href="?id='.$this->id.'&expand=">'.$count_text.$image_minus.'</acronym>'.'</a>';
							$link_entry = '<a href="?id='.$this->id.'&expand=">'.$count_text.$count_logs.'</acronym>'.'</a>';
						} else {
							$link_image = $anchor.'<a href="?id='.$this->id.'&expand='.$codes_result['uid'].'">'.$count_text.$image_plus.'</acronym>'.'</a>';
							$link_entry = '<a href="?id='.$this->id.'&expand='.$codes_result['uid'].'">'.$count_text.$count_logs.'</acronym>'.'</a>';
						}

						$content .= sprintf($tableline_title1,$color,$link_image,$LANG->getLL('table_title'),$codes_result['title']);
						$content .= sprintf($tableline_title2,$color,$link_entry,$LANG->getLL('table_description'),$codes_result['description']);

						if ($codes_result['deleted'] == 1) {
							$content .= sprintf($tableline_title3,$color,'!! <span style="color: red;">'.$LANG->getLL('table_deleted').'</span> !!');
						}

						if (t3lib_div::_GET('expand') == $codes_result['uid']) {
							if ($count_logs > 0) {
								for ($i_logs = 1; $i_logs <= $count_logs; $i_logs++) {
									$logs_result = $db->sql_fetch_assoc($logs_query);
									$content .= sprintf($tableline_download1,5,$color,$LANG->getLL('table_datetime'),$color,date($LANG->getLL('table_datetime_format'),$logs_result['accesstime']));
									$content .= sprintf($tableline_download2,$color,$LANG->getLL('table_r_browser'),$logs_result['rbrowser']);
									if ($logs_result['ripadress'] <> "") {
										$content .= sprintf($tableline_download2,$color,$LANG->getLL('table_r_ipadress'),$logs_result['ripadress']);
									} else {
										$content .= sprintf($tableline_download2,$color,$LANG->getLL('table_r_ipadress'),'!! <span style="font-style:italic;">'.$LANG->getLL('no-ip-logging').'</span> !!');
									}
									if ($logs_result['rname'] <> "") {
										$content .= sprintf($tableline_download2,$color,$LANG->getLL('table_r_name'),$logs_result['rname']);
									} else {
										$content .= sprintf($tableline_download2,$color,$LANG->getLL('table_r_name'),'!! <span style="font-style:italic;">'.$LANG->getLL('no-ip-logging').'</span> !!');
									}
									$content .= sprintf($tableline_download2,$color,$LANG->getLL('table_errortext'),sprintf($LANG->getLL('error'.$logs_result['error']),$logs_result['errortext']));
									if ($i_logs < $count_logs) {$content .= sprintf($tableline_spacer,$color); };
								}
							} else {
								$content .= sprintf($tableline_download3,$color,$LANG->getLL('no-download-yet'));
							}
						}
					}
					$content .= '</table>';
				} else {
					$error = $LANG->getLL('no-download-defined');
				}

				if (!empty($error)) { 
					$content .= $error; 
				}
				$this->content.=$this->doc->section($LANG->getLL('function2-description'),$content,0,1);
			break;
			case 3:
				$codes_query = $db->exec_SELECTquery('*', 'tx_rssecuredownload_codes','');
				$count_codes = $db->sql_num_rows($codes_query);

				if ($count_codes > 0) {

					$color = $color1;
					$content = '<table border="0" cellpadding="2" width="100%">';
					for ($i_codes = 1; $i_codes <= $count_codes; $i_codes++) {
						if ($color == $color1) {
							$color = $color2;
						} else {
							$color = $color1;
						}
						$codes_result = $db->sql_fetch_assoc($codes_query);

						$logs_query = $db->exec_SELECTquery('*', 'tx_rssecuredownload_logs','docid='.$codes_result['uid'].' AND error='.'0'.' AND deleted='.'0');
						$count_logs = $db->sql_num_rows($logs_query);

						$anchor = '<a name="rs'.$codes_result['uid'].'"></a>';

						if ($count_logs == 1) {
							$count_text = '<acronym title="'.$count_logs.' '.$LANG->getLL('table_entry').'">';
						} else {
							$count_text = '<acronym title="'.$count_logs.' '.$LANG->getLL('table_entries').'">';
						}

						if (t3lib_div::_GET('expand') == $codes_result['uid']) {
							$link_image = $anchor.'<a href="?id='.$this->id.'&expand=">'.$count_text.$image_minus.'</acronym>'.'</a>';
							$link_entry = '<a href="?id='.$this->id.'&expand=">'.$count_text.$count_logs.'</acronym>'.'</a>';
						} else {
							$link_image = $anchor.'<a href="?id='.$this->id.'&expand='.$codes_result['uid'].'">'.$count_text.$image_plus.'</acronym>'.'</a>';
							$link_entry = '<a href="?id='.$this->id.'&expand='.$codes_result['uid'].'">'.$count_text.$count_logs.'</acronym>'.'</a>';
						}

						$content .= sprintf($tableline_title1,$color,$link_image,$LANG->getLL('table_title'),$codes_result['title']);
						$content .= sprintf($tableline_title2,$color,$link_entry,$LANG->getLL('table_description'),$codes_result['description']);

						if ($codes_result['deleted'] == 1) {
							$content .= sprintf($tableline_title3,$color,'!! <span style="color: red;">'.$LANG->getLL('table_deleted').'</span> !!');
						}

						if (t3lib_div::_GET('expand') == $codes_result['uid']) {
							if ($count_logs > 0) {
								for ($i_logs = 1; $i_logs <= $count_logs; $i_logs++) {
									$logs_result = $db->sql_fetch_assoc($logs_query);
									$content .= sprintf($tableline_download1,4,$color,$LANG->getLL('table_datetime'),$color,date($LANG->getLL('table_datetime_format'),$logs_result['accesstime']));
									$content .= sprintf($tableline_download2,$color,$LANG->getLL('table_r_browser'),$logs_result['rbrowser']);
									if ($logs_result['ripadress'] <> "") {
										$content .= sprintf($tableline_download2,$color,$LANG->getLL('table_r_ipadress'),$logs_result['ripadress']);
									} else {
										$content .= sprintf($tableline_download2,$color,$LANG->getLL('table_r_ipadress'),'!! <span style="font-style:italic;">'.$LANG->getLL('no-ip-logging').'</span> !!');
									}
									if ($logs_result['rname'] <> "") {
										$content .= sprintf($tableline_download2,$color,$LANG->getLL('table_r_name'),$logs_result['rname']);
									} else {
										$content .= sprintf($tableline_download2,$color,$LANG->getLL('table_r_name'),'!! <span style="font-style:italic;">'.$LANG->getLL('no-ip-logging').'</span> !!');
									}
									if ($i_logs < $count_logs) {$content .= sprintf($tableline_spacer,$color); };
								}
							} else {
								$content .= sprintf($tableline_download3,$color,$LANG->getLL('no-download-yet'));
							}
						}
					}
					$content .= '</table>';
				} else {
					$error = $LANG->getLL('no-download-defined');
				}

				if (!empty($error)) { 
					$content .= $error; 
				}
				$this->content.=$this->doc->section($LANG->getLL('function3-description'),$content,0,1);
			break;
		}
	}

	/**
	 * markDeleted 
	 * 
	 * @access public
	 * @return void
	 */
	function markDeleted() {
		global $LANG;
		$db =& $GLOBALS['TYPO3_DB'];
$db->debugOutput = true;
		
		switch($this->deleteType)	{
			case 2:
				$update_result = $db->exec_UPDATEquery('tx_rssecuredownload_logs','docid='.$this->deleteCode.' AND error='.'0',array('deleted'=>'1'));
			break;
			case 1:
				$update_result = $db->exec_UPDATEquery('tx_rssecuredownload_logs','docid='.$this->deleteCode.' AND NOT error='.'0',array('deleted'=>'1'));
			break;
			case 0:
				$update_result = $db->exec_UPDATEquery('tx_rssecuredownload_logs','docid='.$this->deleteCode,array('deleted'=>'1'));
			break;
		}
$db->debugOutput = false;
//$db->explainOutput = false;
	}

	/**
	 * addCsvCol 
	 * 
	 * @param string $content 
	 * @access public
	 * @return void
	 */
	function addCsvCol($content='') {
		$this->csvContent[$this->currentRowNumber][$this->currentColNumber] = $content;
		$this->currentColNumber++;
	}

	/**
	 * addCsvRow 
	 * 
	 * @access public
	 * @return void
	 */
	function addCsvRow() {
		$this->currentRowNumber++;
		$this->currentColNumber = 0;
		$this->csvContent[$this->currentRowNumber] = array();
	}

	/**
	 * createCSV 
	 * 
	 * @access public
	 * @return void
	 */
	function createCSV() {
		global $LANG;
		$db =& $GLOBALS['TYPO3_DB'];
$db->debugOutput = true;
		
		switch($this->csvOutputType)	{
			case 2:
				$logs_result = $db->exec_SELECTquery('*', 'tx_rssecuredownload_logs','docid='.$this->deleteCode.' AND error='.'0'.' AND deleted='.'0');
			break;
			case 1:
				$logs_result = $db->exec_SELECTquery('*', 'tx_rssecuredownload_logs','docid='.$this->deleteCode.' AND NOT error='.'0'.' AND deleted='.'0');
			break;
			case 0:
				$logs_result = $db->exec_SELECTquery('*', 'tx_rssecuredownload_logs','docid='.$this->deleteCode.' AND deleted='.'0');
			break;
		}


$db->debugOutput = false;
	}

	/**
	 * outputCSV 
	 * 
	 * @access public
	 * @return void
	 */
	function outputCSV() {
		// Set Excel as default application
		header('Pragma: private');
		header('Cache-control: private, must-revalidate');
		header("Content-Type: application/vnd.ms-excel");

		// Set file name
//		header('Content-Disposition: attachment; filename="' . str_replace('###DATE###', date('Y-m-d-H-i'), $GLOBALS['LANG']->getLL('csvdownload_filename') . '"'));
		header('Content-Disposition: attachment; filename="text.csv"');

		$content = '';
		foreach ($this->csvContent as $row) {
			//function csvValues($row,$delim=',',$quote='"') 
			$content .= t3lib_div::csvValues($row) . "\n";
		}

		// I'm not sure if this is necessary for all programs you are importing to, tested with OpenOffice.org
		if ($GLOBALS['LANG']->charSet == 'utf-8') {
			$content = utf8_decode($content);
		}

		echo $content;
		exit();
	}

	/**
	 * getSubPages 
	 *
	 * returns commalist of all subpages of a given page 
	 * works recursive
	 * Does explicitly not check for hidden pages and restricted access!
	 * 
	 * @param int $page_uid 
	 * @access public
	 * @return void
	 */
	function getSubPages($page_uid=0) {/*{{{*/
		if ($page_uid) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'pid='.intval($page_uid));
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				if (strlen($this->pagelist)>0) {
					$this->pagelist .= ',';
				}
				$this->pagelist .= $row['uid'];
				$this->getSubPages($row['uid']);
			}
		} else {
			$this->pagelist = '';
		}
	}/*}}}*/


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rs_securedownload/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rs_securedownload/mod1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_rssecuredownload_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>
