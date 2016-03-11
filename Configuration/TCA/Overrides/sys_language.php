<?php
/**
 * Created by PhpStorm.
 * User: sebo
 * Date: 05.05.15
 * Time: 10:56
 */

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

/** Make field static_lang_isocode required */
$GLOBALS[ 'TCA' ][ 'sys_language' ][ 'columns' ][ 'static_lang_isocode' ][ 'config' ][ 'minitems' ] = 1;
$GLOBALS[ 'TCA' ][ 'sys_language' ][ 'columns' ][ 'static_lang_isocode' ][ 'config' ][ 'items' ][ 0 ] = ['', 0];

/** Add extension fields */
$addColumns = [
    'hreflang' => [
        'exclude' => 1,
        'label'   => 'hreflang',
        'config'  => [
            'type'  => 'input',
            'max'   => 5,
            'eval'  => 'lower,nospace,trim,is_in',
            'is_in' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-'
        ]
    ]
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_language', $addColumns, true);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('sys_language', 'hreflang', '', 'after:flag');