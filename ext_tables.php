<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$extendTca = array(
	'hreflang' => array(
		'exclude' => 1,
		'label' => 'hreflang',
		'config' => array(
			'type' => 'input',
			'max' => 5,
			'eval' => 'lower,nospace,trim,is_in',
			'is_in' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-'
		),
	),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_language', $extendTca, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('sys_language', 'hreflang');

?>
