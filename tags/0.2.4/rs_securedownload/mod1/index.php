<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2014 Rene <typo3@rs-softweb.de>
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
$BE_USER->modAccess($MCONF, 1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]



/**
 * Module 'Secure Download' for the 'rs_securedownload' extension.
 *
 * @author	Rene <typo3@rs-softweb.de>
 * @package	TYPO3
 * @subpackage	tx_rssecuredownload
 */
class  tx_rssecuredownload_module1 extends t3lib_SCbase {
	private $pageinfo;
	private $pagelist = '';
	private $csvContent = array();
	private $lang;
	/**
	 * Initializes the Module
	 *
	 * @return	void
	 * @access public
	 */
	public function init() {
//		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		parent::init();

		// get the subpages list
		if ($this->id) {
			$this->pagelist = strval($this->id);
			$this->getSubPages($this->id);
		}

		// init the first csv-content row
		$this->csvContent = array();
		// check, if we should render a csv-table
		$this->csvOutput = (t3lib_div::_GET('format') == 'csv') ? TRUE : FALSE;
		// get the code for csv-table output
		$this->csvOutputCode = t3lib_div::_GET('code');
		// get the type for csv-table output (0=all, 1=failure, 2=correct) )
		$this->csvOutputType = t3lib_div::_GET('type');

		// check, if we should delete rows
		$this->delete = (t3lib_div::_GET('delete') == '1') ? TRUE : FALSE;
		// get the code for delete
		$this->deleteCode = t3lib_div::_GET('code');
		// get the type for delete (0=all, 1=failure, 2=correct, 3=single) )
		$this->deleteType = t3lib_div::_GET('type');
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 * @access public
	 */
	public function menuConfig() {
		$this->MOD_MENU = Array (
			'function' => Array (
				'1' => $GLOBALS['LANG']->getLL('function1'),
				'2' => $GLOBALS['LANG']->getLL('function2'),
				'3' => $GLOBALS['LANG']->getLL('function3'),
			)
		);
		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return	void		...
	 * @access public
	 */
	public function main() {
//		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

//		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{
		if (($this->id && $access))	{

			// Draw the header.
			$this->doc = t3lib_div::makeInstance('template');
			$this->doc->backPath = $GLOBALS['BACK_PATH'];
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

			$headerSection = $this->doc->getHeader('pages', $this->pageinfo, 
				$this->pageinfo['_thePath']).'<br />'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.t3lib_div::fixed_lgd_cs($this->pageinfo['_thePath'], -50);

			$this->content.=$this->doc->startPage($GLOBALS['LANG']->getLL('title'));
			$this->content.=$this->doc->header($GLOBALS['LANG']->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->section('', $this->doc->funcMenu($headerSection, t3lib_BEfunc::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function'])));
			$this->content.=$this->doc->divider(5);

			// Delete, if requested
			if ($this->delete) $this->markDeleted();

			// Render content:
			$this->moduleContent();

			// ShortCut
			if ($GLOBALS['BE_USER']->mayMakeShortcut())	{
				$this->content.=$this->doc->spacer(20).$this->doc->section('', $this->doc->makeShortcutIcon('id', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']));
			}

			$this->content.=$this->doc->spacer(10);
		} else {
			// If no access or if ID == zero
			$this->doc = t3lib_div::makeInstance('template');
			$this->doc->backPath = $GLOBALS['BACK_PATH'];

			$this->content.=$this->doc->startPage($GLOBALS['LANG']->getLL('title'));
			$this->content.=$this->doc->header($GLOBALS['LANG']->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$GLOBALS['LANG']->getLL('please_select_page');
			$this->content.=$this->doc->spacer(10);
		}
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 * @access public
	 */
	public function printContent() {
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
	 * @access protected
	 */
	protected function moduleContent() {
//		global $LANG;
		$lang = $GLOBALS['LANG'];
		$db =& $GLOBALS['TYPO3_DB'];

		$image_plus = '<image src="../res/plus.gif">';
		$image_minus = '<image src="../res/minus.gif">';
		$image_delete = '<image src="../res/garbage.gif">';
		$image_export = '<image src="../res/export.gif">';
		$color1 = '#CCCCCC';
		$color2 = '#DDDDDD';
		$brd_dot = ' style="border-bottom:1px dotted grey;padding:2px" ';
		$brd_full= ' style="border-bottom:1px solid black;padding:2px" ';
		$tableline_title1 = '<tr BGCOLOR="%s"><td %s width="10" align="center" valign="top">%s</td><td %s width="10" valign="top">%s&nbsp;</td>'
			. '<td %s colspan="2">%s&nbsp;</td><td %s width="10" align="center" valign="middle">&nbsp;</td></tr>';
		$tableline_title2 = '<tr BGCOLOR="%s"><td %s width="10" align="center" valign="top">%s</td><td %s width="10" valign="top">%s&nbsp;</td>'
			. '<td %s colspan="2">%s&nbsp;</td></tr>';
		$tableline_title3 = '<tr BGCOLOR="%s"><td %s width="10" align="center" valign="top">&nbsp;</td><td %s align="center" valign="top" colspan="3">%s</td></tr>';
		$tableline_download1 = '<tr><td %srowspan="%s" align="right">%s&nbsp;</td><td %s BGCOLOR="%s">%s&nbsp;</td>';
		$tableline_download1 .= '<td %s colspan="2" BGCOLOR="%s">%s&nbsp;</td><td %srowspan="%s" align="center">%s</td></tr>';
		$tableline_download2 = '<tr BGCOLOR="%s"><td %s>%s&nbsp;</td><td %s colspan="2">%s&nbsp;</td></tr>';
		$tableline_download3 = '<tr><td %s align="right">%s&nbsp;</td><td %s colspan="4" BGCOLOR="%s">%s&nbsp;</td></tr>';

		$query_pagelist = ' AND pid IN ('.$this->pagelist.')';
		$query_pagelist = '';

		switch((string)$this->MOD_SETTINGS['function'])	{
			case 1:
				// override table structure only for this case
				$tableline_title1 = '<tr BGCOLOR="%s"><td %s width="10" valign="top">%s&nbsp;</td><td %s>%s&nbsp;</td></tr>';
				$tableline_title2 = '<tr BGCOLOR="%s"><td %s width="10" valign="top">&nbsp;</td><td %s>%s&nbsp;</td></tr>';
				$tableline_download1 = '<tr BGCOLOR="%s"><td %s>%s</td><td %s>%s</td></tr>';
				$tableline_inline = '<tr onmouseover="this.style.backgroundColor=\'#AAAAAA\'" onmouseout="this.style.backgroundColor=\'\'">'
					. '<td style="vertical-align:middle">%s</td><td style="vertical-align:middle" width="50" align="right">%s</td>'
					. '<td style="vertical-align:middle" width="50">%s</td><td style="vertical-align:middle" width="50" align="center">%s</td>'
					. '<td style="vertical-align:middle" width="50" align="center">%s</td><td style="vertical-align:middle" width="50" align="center">%s</td></tr>';

//				$codes_query = $db->exec_SELECTquery('*', 'tx_rssecuredownload_codes', 'pid IN ('.$this->pagelist.')');
				$codes_query = $db->exec_SELECTquery('*', 'tx_rssecuredownload_codes', '');
				$count_codes = $db->sql_num_rows($codes_query);

				if ($count_codes > 0) {

					$color = $color1;
					$content = '<table style="width:100%; border:1px solid black; border-collapse:collapse;">';
					for ($i_codes = 1; $i_codes <= $count_codes; $i_codes++) {
						if ($color == $color1) {
							$color = $color2;
						} else {
							$color = $color1;
						}
						$codes_result = $db->sql_fetch_assoc($codes_query);

						$content .= sprintf($tableline_title1, $color, $brd_dot, $lang->getLL('table_title'), $brd_dot, $codes_result['title']);
						$content .= sprintf($tableline_title1, $color, $brd_dot, $lang->getLL('table_description'), $brd_dot, $codes_result['description']);

						if ($codes_result['deleted'] == 1) {
							$content .= sprintf($tableline_title2, $color, $brd_dot, $brd_dot, '<span style="color: red;">'.$lang->getLL('table_deleted').'!!</span>');
						}

						$logs_query = $db->exec_SELECTquery('error', 'tx_rssecuredownload_logs', 'docid='.$codes_result['uid'].' AND deleted='.'0'.$query_pagelist);
						$count_logs = $db->sql_num_rows($logs_query);

						if ($count_logs > 0) {
							$logs_query = $db->exec_SELECTquery('error', 'tx_rssecuredownload_logs', 'docid='.$codes_result['uid'].' AND NOT error='.'0'.' AND deleted='.'0'.$query_pagelist);
							$count_logs_failure = $db->sql_num_rows($logs_query);
							$count_logs_failure_text = ($count_logs_failure == 1) ? $lang->getLL('table_entry') : $lang->getLL('table_entries');
							$logs_query = $db->exec_SELECTquery('error', 'tx_rssecuredownload_logs', 'docid='.$codes_result['uid'].' AND error='.'0'.' AND deleted='.'0'.$query_pagelist);
							$count_logs_correct = $db->sql_num_rows($logs_query);
							$count_logs_correct_text = ($count_logs_correct == 1) ? $lang->getLL('table_entry') : $lang->getLL('table_entries');
							$count_logs_all = $count_logs_failure + $count_logs_correct;
							$count_logs_all_text = ($count_logs_all == 1) ? $lang->getLL('table_entry') : $lang->getLL('table_entries');

							$detail_link_correct = '<a href="?id='.$this->id.'&SET[function]=3&expand='.$codes_result['uid'].'">'
								.'<acronym style="border: none" title="'.$lang->getLL('table_detail').'">'.$image_plus.'</acronym>'.'</a>';
							$detail_link_failure = '<a href="?id='.$this->id.'&SET[function]=2&expand='.$codes_result['uid'].'">'
								.'<acronym style="border: none" title="'.$lang->getLL('table_detail').'">'.$image_plus.'</acronym>'.'</a>';

							$export_link_correct = '<a href="?id='.$this->id.'&format=csv&code='.$codes_result['uid'].'&type=2">'
								.'<acronym style="border: none" title="'.$lang->getLL('table_export').'">'.$image_export.'</acronym>'.'</a>';
							$export_link_failure = '<a href="?id='.$this->id.'&format=csv&code='.$codes_result['uid'].'&type=1">'
								.'<acronym style="border: none" title="'.$lang->getLL('table_export').'">'.$image_export.'</acronym>'.'</a>';
							$export_link_all     = '<a href="?id='.$this->id.'&format=csv&code='.$codes_result['uid'].'&type=0">'
								.'<acronym style="border: none" title="'.$lang->getLL('table_export').'">'.$image_export.'</acronym>'.'</a>';
//Overwrite for temporary disabled
//							$export_link_correct = '<acronym style="border: none" title="'.$lang->getLL('table_export').' '.$lang->getLL('coming_soon').'">'.$image_export.'</acronym>';
//							$export_link_failure = '<acronym style="border: none" title="'.$lang->getLL('table_export').' '.$lang->getLL('coming_soon').'">'.$image_export.'</acronym>';
//							$export_link_all     = '<acronym style="border: none" title="'.$lang->getLL('table_export').' '.$lang->getLL('coming_soon').'">'.$image_export.'</acronym>';

							$delete_link_correct = '<a href="?id='.$this->id.'&delete=1&code='.$codes_result['uid'].'&type=2" onclick="return confirm(\''.$lang->getLL('table_delete').' '.$count_logs_correct.' '.$count_logs_correct_text.'?\')">'
								.'<acronym style="border: none" title="'.$lang->getLL('table_delete').' '.$count_logs_correct.' '.$count_logs_correct_text.'">'.$image_delete.'</acronym>'.'</a>';
							$delete_link_failure = '<a href="?id='.$this->id.'&delete=1&code='.$codes_result['uid'].'&type=1" onclick="return confirm(\''.$lang->getLL('table_delete').' '.$count_logs_failure.' '.$count_logs_failure_text.'?\')">'
								.'<acronym style="border: none" title="'.$lang->getLL('table_delete').' '.$count_logs_failure.' '.$count_logs_failure_text.'">'.$image_delete.'</acronym>'.'</a>';
							$delete_link_all     = '<a href="?id='.$this->id.'&delete=1&code='.$codes_result['uid'].'&type=0" onclick="return confirm(\''.$lang->getLL('table_delete').' '.$count_logs_all.' '.$count_logs_all_text.'?\')">'
								.'<acronym style="border: none" title="'.$lang->getLL('table_delete').' '.$count_logs_all.' '.$count_logs_all_text.'">'.$image_delete.'</acronym>'.'</a>';

							$content_inline = '<table border="0" cellpadding="1" cellspacing="0" width="100%">'
								.sprintf($tableline_inline, $lang->getLL('table_access_success'), $count_logs_correct, $count_logs_correct_text, $detail_link_correct, $export_link_correct, $delete_link_correct)
								.sprintf($tableline_inline, $lang->getLL('table_access_failure'), $count_logs_failure, $count_logs_failure_text, $detail_link_failure, $export_link_failure, $delete_link_failure)
								.sprintf($tableline_inline, $lang->getLL('table_access_all'), $count_logs_all, $count_logs_all_text, '&nbsp;', $export_link_all, $delete_link_all)
								.'</table>';
							$content .= sprintf($tableline_download1, $color, $brd_full, $lang->getLL('table_access'), $brd_full, $content_inline);
						} else {
							$content .= sprintf($tableline_download1, $color, $brd_full, $lang->getLL('table_access'), $brd_full, '<span style="font-weight:bold;">'.$lang->getLL('no-download-yet').'</span>');
						}
					}
					$content .= '</table>';
				} else {
					$error = $lang->getLL('no-download-defined');
				}

				if (!empty($error)) {
					$content .= $error;
				}
				$this->content.=$this->doc->section($lang->getLL('function1-description'), $content, 0, 1);

			break;
			case 2:
				$codes_query = $db->exec_SELECTquery('*', 'tx_rssecuredownload_codes', '');
				$count_codes = $db->sql_num_rows($codes_query);

				if ($count_codes > 0) {

					$color = $color1;
					$content = '<table style="width:100%; border:1px solid black; border-collapse:collapse;">';
					for ($i_codes = 1; $i_codes <= $count_codes; $i_codes++) {
						if ($color == $color1) {
							$color = $color2;
						} else {
							$color = $color1;
						}
						$codes_result = $db->sql_fetch_assoc($codes_query);

						$logs_query = $db->exec_SELECTquery('*', 'tx_rssecuredownload_logs', 'docid='.$codes_result['uid'].' AND NOT error='.'0'.' AND deleted='.'0');
						$count_logs = $db->sql_num_rows($logs_query);

						$anchor = '<a name="rs'.$codes_result['uid'].'"></a>';

						if ($count_logs == 1) {
							$count_text = '<acronym title="'.$count_logs.' '.$lang->getLL('table_entry').'">';
						} else {
							$count_text = '<acronym title="'.$count_logs.' '.$lang->getLL('table_entries').'">';
						}

						if ($count_logs < 1) {
							$link_image = $anchor.$count_text.'&nbsp;</acronym>';
						} elseif (t3lib_div::_GET('expand') == $codes_result['uid']) {
							$link_image = $anchor.'<a href="?id='.$this->id.'&expand=">'.$count_text.$image_minus.'</acronym>'.'</a>';
						} else {
							$link_image = $anchor.'<a href="?id='.$this->id.'&expand='.$codes_result['uid'].'">'.$count_text.$image_plus.'</acronym>'.'</a>';
						}
						
						$content .= sprintf($tableline_title1, $color, $brd_dot, $link_image, $brd_dot, $lang->getLL('table_title'), $brd_dot, $codes_result['title'], $brd_dot);
						$content .= sprintf($tableline_title1, $color, $brd_dot, '&nbsp;', $brd_dot, $lang->getLL('table_description'), $brd_dot, $codes_result['description'], $brd_dot);
						if ($codes_result['deleted'] == 1) {
							$content .= sprintf($tableline_title1, $color, $brd_dot, '&nbsp;', $brd_dot, '&nbsp;', $brd_dot, '<span style="color: red;">'.$lang->getLL('table_deleted').'!!</span>', $brd_dot);
						}

						if ((t3lib_div::_GET('expand') == $codes_result['uid'])) {
							if ($count_logs > 0) {
								for ($i_logs = 1; $i_logs <= $count_logs; $i_logs++) {
									$logs_result = $db->sql_fetch_assoc($logs_query);
									$link_delete = '<a href="?id='.$this->id.'&delete=1&code='.$logs_result['uid'].'&type=3&expand='.$codes_result['uid'].'" onclick="return confirm(\''.$lang->getLL('table_delete_this').'?\')">'
										.'<acronym style="border: none" title="'.$lang->getLL('table_delete_this').'">'.$image_delete.'</acronym>'.'</a>';
									if ($i_logs < $count_logs) {
										$content .= sprintf($tableline_download1, $brd_dot, 5, $i_logs, $brd_dot, $color, $lang->getLL('table_datetime'), $brd_dot, $color, date($lang->getLL('table_datetime_format'), $logs_result['accesstime']), $brd_dot, 5, $link_delete);
									} else {
										$content .= sprintf($tableline_download1, $brd_full, 5, $i_logs, $brd_dot, $color, $lang->getLL('table_datetime'), $brd_dot, $color, date($lang->getLL('table_datetime_format'), $logs_result['accesstime']), $brd_full, 5, $link_delete);
									}
									$content .= sprintf($tableline_download2, $color, $brd_dot, $lang->getLL('table_r_browser'), $brd_dot, $logs_result['rbrowser']);
									if ($logs_result['ripadress'] <> '') {
										$content .= sprintf($tableline_download2, $color, $brd_dot, $lang->getLL('table_r_ipadress'), $brd_dot, $logs_result['ripadress']);
									} else {
										$content .= sprintf($tableline_download2, $color, $brd_dot, $lang->getLL('table_r_ipadress'), $brd_dot, '!! <span style="font-style:italic;">'.$lang->getLL('no-ip-logging').'</span> !!');
									}
									if ($logs_result['rname'] <> '') {
										$content .= sprintf($tableline_download2, $color, $brd_dot, $lang->getLL('table_r_name'), $brd_dot, $logs_result['rname']);
									} else {
										$content .= sprintf($tableline_download2, $color, $brd_dot, $lang->getLL('table_r_name'), $brd_dot, '!! <span style="font-style:italic;">'.$lang->getLL('no-ip-logging').'</span> !!');
									}
									if ($i_logs < $count_logs) {
										$content .= sprintf($tableline_download2, $color, $brd_dot, $lang->getLL('table_errortext'), $brd_dot, sprintf($lang->getLL('error'.$logs_result['error']), $logs_result['errortext']));
									} else {
										$content .= sprintf($tableline_download2, $color, $brd_full, $lang->getLL('table_errortext'), $brd_full, sprintf($lang->getLL('error'.$logs_result['error']), $logs_result['errortext']));
									}
								}
							} else {
								$content .= sprintf($tableline_download3, $brd_full, $count_logs, $brd_full, $color, $lang->getLL('download-yet-wrong'));
							}
						} else {
							$content .= sprintf($tableline_download3, $brd_full, $count_logs, $brd_full, $color, $lang->getLL('download-yet-wrong'));
						}
					}
					$content .= '</table>';
				} else {
					$error = $lang->getLL('no-download-defined');
				}

				if (!empty($error)) {
					$content .= $error;
				}
				$this->content.=$this->doc->section($lang->getLL('function2-description'), $content, 0, 1);
			break;
			case 3:
				$codes_query = $db->exec_SELECTquery('*', 'tx_rssecuredownload_codes', '');
				$count_codes = $db->sql_num_rows($codes_query);

				if ($count_codes > 0) {

					$color = $color1;
					$content = '<table style="width:100%; border:1px solid black; border-collapse:collapse;">';
					for ($i_codes = 1; $i_codes <= $count_codes; $i_codes++) {
						if ($color == $color1) {
							$color = $color2;
						} else {
							$color = $color1;
						}
						$codes_result = $db->sql_fetch_assoc($codes_query);

						$logs_query = $db->exec_SELECTquery('*', 'tx_rssecuredownload_logs', 'docid='.$codes_result['uid'].' AND error='.'0'.' AND deleted='.'0');
						$count_logs = $db->sql_num_rows($logs_query);

						$anchor = '<a name="rs'.$codes_result['uid'].'"></a>';

						if ($count_logs == 1) {
							$count_text = '<acronym title="'.$count_logs.' '.$lang->getLL('table_entry').'">';
						} else {
							$count_text = '<acronym title="'.$count_logs.' '.$lang->getLL('table_entries').'">';
						}

						if ($count_logs < 1) {
							$link_image = $anchor.$count_text.'&nbsp;</acronym>';
						} elseif (t3lib_div::_GET('expand') == $codes_result['uid']) {
							$link_image = $anchor.'<a href="?id='.$this->id.'&expand=">'.$count_text.$image_minus.'</acronym>'.'</a>';
						} else {
							$link_image = $anchor.'<a href="?id='.$this->id.'&expand='.$codes_result['uid'].'">'.$count_text.$image_plus.'</acronym>'.'</a>';
						}
						
						$content .= sprintf($tableline_title1, $color, $brd_dot, $link_image, $brd_dot, $lang->getLL('table_title'), $brd_dot, $codes_result['title'], $brd_dot);
						$content .= sprintf($tableline_title1, $color, $brd_dot, '&nbsp;', $brd_dot, $lang->getLL('table_description'), $brd_dot, $codes_result['description'], $brd_dot);
						if ($codes_result['deleted'] == 1) {
							$content .= sprintf($tableline_title1, $color, $brd_dot, '&nbsp;', $brd_dot, '&nbsp;', $brd_dot, '<span style="color: red;">'.$lang->getLL('table_deleted').'!!</span>', $brd_dot);
						}

						if ((t3lib_div::_GET('expand') == $codes_result['uid'])) {
							if ($count_logs > 0) {
								for ($i_logs = 1; $i_logs <= $count_logs; $i_logs++) {
									$logs_result = $db->sql_fetch_assoc($logs_query);
									$link_delete = '<a href="?id='.$this->id.'&delete=1&code='.$logs_result['uid'].'&type=3&expand='.$codes_result['uid'].'" onclick="return confirm(\''.$lang->getLL('table_delete_this').'?\')">'
										.'<acronym style="border: none" title="'.$lang->getLL('table_delete_this').'">'.$image_delete.'</acronym>'.'</a>';
									if ($i_logs < $count_logs) {
										$content .= sprintf($tableline_download1, $brd_dot, 4, $i_logs, $brd_dot, $color, $lang->getLL('table_datetime'), $brd_dot, $color, 
											date($lang->getLL('table_datetime_format'), $logs_result['accesstime']), $brd_dot, 4, $link_delete);
									} else {
										$content .= sprintf($tableline_download1, $brd_full, 4, $i_logs, $brd_dot, $color, $lang->getLL('table_datetime'), $brd_dot, $color, 
											date($lang->getLL('table_datetime_format'), $logs_result['accesstime']), $brd_full, 4, $link_delete);
									};
									$content .= sprintf($tableline_download2, $color, $brd_dot, $lang->getLL('table_r_browser'), $brd_dot, $logs_result['rbrowser']);
									if ($logs_result['ripadress'] <> '') {
										$content .= sprintf($tableline_download2, $color, $brd_dot, $lang->getLL('table_r_ipadress'), $brd_dot, $logs_result['ripadress']);
									} else {
										$content .= sprintf($tableline_download2, $color, $brd_dot, $lang->getLL('table_r_ipadress'), $brd_dot, '!! <span style="font-style:italic;">'.$lang->getLL('no-ip-logging').'</span> !!');
									}
									if ($logs_result['rname'] <> '') {
										$rname = $logs_result['rname'];
									} else {
										$rname = '!! <span style="font-style:italic;">'.$lang->getLL('no-ip-logging').'</span> !!';
									}
									if ($i_logs < $count_logs) {
										$content .= sprintf($tableline_download2, $color, $brd_dot, $lang->getLL('table_r_name'), $brd_dot, $rname);
									} else {
										$content .= sprintf($tableline_download2, $color, $brd_full, $lang->getLL('table_r_name'), $brd_full, $rname);
									};
								}
							} else {
								$content .= sprintf($tableline_download3, $brd_full, $count_logs, $brd_full, $color, $lang->getLL('download-yet-wrong'));
							}
						} else {
							$content .= sprintf($tableline_download3, $brd_full, $count_logs, $brd_full, $color, $lang->getLL('download-yet-wrong'));
						}
					}
					$content .= '</table>';
				} else {
					$error = $lang->getLL('no-download-defined');
				}

				if (!empty($error)) {
					$content .= $error;
				}
				$this->content.=$this->doc->section($lang->getLL('function3-description'), $content, 0, 1);
			break;
		}
	}

	/**
	 * markDeleted
	 *
	 * @return	void
	 * @access private
	 */
	private function markDeleted() {
		$db =& $GLOBALS['TYPO3_DB'];

		switch($this->deleteType) {
			case 3:
				$update_result = $db->exec_UPDATEquery('tx_rssecuredownload_logs', 'uid='.$this->deleteCode, array('deleted'=>'1'));
			break;
			case 2:
				$update_result = $db->exec_UPDATEquery('tx_rssecuredownload_logs', 'docid='.$this->deleteCode.' AND error='.'0', array('deleted'=>'1'));
			break;
			case 1:
				$update_result = $db->exec_UPDATEquery('tx_rssecuredownload_logs', 'docid='.$this->deleteCode.' AND NOT error='.'0', array('deleted'=>'1'));
			break;
			case 0:
				$update_result = $db->exec_UPDATEquery('tx_rssecuredownload_logs', 'docid='.$this->deleteCode, array('deleted'=>'1'));
			break;
		}
	}

	/**
	 * createCSV
	 *
	 * @return	void
	 * @access private
	 */
	private function createCSV() {
//		global $LANG;
		$lang = $GLOBALS['LANG'];
		$db =& $GLOBALS['TYPO3_DB'];

		$fields = 'accesstime,rbrowser,ripadress,rname,error,errortext';
		$this->csvContent[] = explode(',', $fields);

		switch($this->csvOutputType)	{
			case 2:
				$logs_query = $db->exec_SELECTquery($fields, 'tx_rssecuredownload_logs', 'docid='.$this->deleteCode.' AND error='.'0'.' AND deleted='.'0');
			break;
			case 1:
				$logs_query = $db->exec_SELECTquery($fields, 'tx_rssecuredownload_logs', 'docid='.$this->deleteCode.' AND NOT error='.'0'.' AND deleted='.'0');
			break;
			case 0:
				$logs_query = $db->exec_SELECTquery($fields, 'tx_rssecuredownload_logs', 'docid='.$this->deleteCode.' AND deleted='.'0');
			break;
		}

		while ($row = $db->sql_fetch_assoc($logs_query)) {
			$row['accesstime'] = date($lang->getLL('table_datetime_format'), $row['accesstime']);
			$row['rbrowser'] = $row['rbrowser'];
			if ($row['ripadress'] <> '') {
				$row['ripadress'] = $row['ripadress'];
			} else {
				$row['ripadress'] = $lang->getLL('no-ip-logging');
			}
			if ($row['rname'] <> '') {
				$row['rname'] = $row['rname'];
			} else {
				$row['rname'] = $lang->getLL('no-ip-logging');
			}
			if ($row['error'] <> 0) {
				$row['errortext'] = sprintf($lang->getLL('error'.$row['error']), $row['errortext']);
			}
			$this->csvContent[] = $row;
		}
	}

	/**
	 * outputCSV
	 *
	 * @return	void
	 * @access private
	 */
	private function outputCSV() {
		$lang = $GLOBALS['LANG'];
		// Set Excel as default application
		header('Pragma: private');
		header('Cache-control: private, must-revalidate');
		header('Content-Type: application/csv');

		// Set file name
		$filename = str_replace('###DATE###', date($lang->getLL('csv-download_datetime')), $lang->getLL('csv-download_filename'));
		header('Content-Disposition: attachment; filename="' . $filename . '"');

		foreach ($this->csvContent as $row) {
			//function csvValues($row, $delim=',', $quote='"')
			$content .= t3lib_div::csvValues($row, ';', '"') . "\n";
		}

		// I'm not sure if this is necessary for all programs you are importing to, tested with OpenOffice.org
		if ($lang->charSet == 'utf-8') {
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
	 * @access private
	 * @return void
	 */
	private function getSubPages($page_uid=0) {
		if ($page_uid) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'pid='.intval($page_uid));
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				if (strlen($this->pagelist)>0) {
					$this->pagelist .= ',';
				}
				$this->pagelist .= $row['uid'];
				$this->getSubPages($row['uid']);
			}
		} else {
			$this->pagelist = '';
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rs_securedownload/mod1/index.php']) {
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