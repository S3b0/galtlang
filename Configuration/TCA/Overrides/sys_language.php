<?php
/**
 * Created by PhpStorm.
 * User: sebo
 * Date: 05.05.15
 * Time: 10:56
 */

if ( !defined('TYPO3_MODE') ) {
	die( 'Access denied.' );
}

/** Make field static_lang_isocode required */
\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($GLOBALS[ 'TCA' ][ 'sys_language' ][ 'columns' ], [
	'static_lang_isocode' => [
		'config' => [
			'minitems' => 1,
			'items' => [
				0 => [
					1 => ''
				]
			]
		]
	]
]);

/** Add extension fields */
$addColumns = [
	'hreflang' => [
		'exclude' => 1,
		'label' => 'hreflang',
		'config' => [
			'type' => 'input',
			'max' => 5,
			'eval' => 'lower,nospace,trim,is_in',
			'is_in' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-'
		]
	]
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns( 'sys_language', $addColumns, TRUE );
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes( 'sys_language', 'hreflang' );