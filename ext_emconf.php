<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "galtlang".
 ***************************************************************/

$EM_CONF[ $_EXTKEY ] = [
	'title' => 'Google Alternate Language',
	'description' => 'If you have a multilanguage site and want to use the Google alternate hreflang tags this extension is for you.',
	'category' => 'Frontend',
	'shy' => 0,
	'version' => '6.2.1',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'excludeFromUpdates',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Sebastian Iffland',
	'author_email' => 'Sebastian.Iffland@ecom-ex.com',
	'author_company' => 'ecom instruments GmbH',
	'CGLcompliance' => NULL,
	'CGLcompliance_note' => NULL,
	'constraints' =>
	[
		'depends' => [
			'static_info_tables' => '6.0.0'
		],
		'conflicts' => [],
		'suggests' => []
	]
];

?>