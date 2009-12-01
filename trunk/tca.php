<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA["tx_rssecuredownload_codes"] = array (
	"ctrl" => $TCA["tx_rssecuredownload_codes"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "hidden,starttime,endtime,title,description,codeprompt,code,file"
	),
	"feInterface" => $TCA["tx_rssecuredownload_codes"]["feInterface"],
	"columns" => array (
		'hidden' => array (
			'exclude' => 0,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'starttime' => array (
			'exclude' => 0,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'config'  => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'default'  => '0',
				'checkbox' => '0'
			)
		),
		'endtime' => array (
			'exclude' => 0,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'config'  => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'checkbox' => '0',
				'default'  => '0',
				'range'    => array (
					'upper' => mktime(0, 0, 0, 12, 31, 2020),
					'lower' => mktime(0, 0, 0, date('m')-1, date('d'), date('Y'))
				)
			)
		),
		"title" => Array (
			"exclude" => 0,		
			"label" => "LLL:EXT:rs_securedownload/locallang_db.xml:tx_rssecuredownload_codes.title",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"eval" => "required,trim",
			)
		),
		"description" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:rs_securedownload/locallang_db.xml:tx_rssecuredownload_codes.description",		
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "5",
			)
		),
		"codeprompt" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:rs_securedownload/locallang_db.xml:tx_rssecuredownload_codes.codeprompt",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"eval" => "trim",
			)
		),
		"code" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:rs_securedownload/locallang_db.xml:tx_rssecuredownload_codes.code",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"eval" => "required,trim",
			)
		),
		"file" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:rs_securedownload/locallang_db.xml:tx_rssecuredownload_codes.file",		
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "",	
				"disallowed" => "php,php3",	
				"max_size" => 100000,	
				"uploadfolder" => "uploads/tx_rssecuredownload",
				"show_thumbs" => 1,	
				"size" => 2,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
	),
	"types" => array (
		"0" => array("showitem" => "hidden;;1;;1-1-1, title;;;;2-2-2, description;;;richtext[*];3-3-3, codeprompt, code, file")
	),
	"palettes" => array (
		"1" => array("showitem" => "starttime, endtime")
	)
);



?>
