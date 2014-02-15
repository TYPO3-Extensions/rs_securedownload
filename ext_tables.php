<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
$TCA['tx_rssecuredownload_codes'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:rs_securedownload/locallang_db.xml:tx_rssecuredownload_codes',		
		'label'     => 'title',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',	
		'delete' => 'deleted',	
		'enablecolumns' => array (		
			'disabled' => 'hidden',	
			'starttime' => 'starttime',	
			'endtime' => 'endtime',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_rssecuredownload_codes.gif',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'hidden, starttime, endtime, title, description, codeprompt, code, file',
	)
);

if (TYPO3_MODE == 'BE')	{
		
	t3lib_extMgm::addModule('web', 'txrssecuredownloadM1', '', t3lib_extMgm::extPath($_EXTKEY).'mod1/');
}


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key,pages,starttime,endtime';
// you add pi_flexform to be renderd when your plugin is shown
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';

t3lib_extMgm::addPlugin(array('LLL:EXT:rs_securedownload/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'), 'list_type');


t3lib_extMgm::addStaticFile($_EXTKEY, 'pi1/static/', 'Secure Download');
// now, add your flexform xml-file
// NOTE: Be sure to change sampleflex to the correct directory name of your extension!                    // new!
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:rs_securedownload/flexform_ds_pi1.xml');

if (TYPO3_MODE=='BE')	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_rssecuredownload_pi1_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_rssecuredownload_pi1_wizicon.php';
?>
