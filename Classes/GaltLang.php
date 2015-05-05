<?php

namespace Ext\GaltLang;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Klaus Hörmann (http://www.world-direct.at/)
 *  (c) 2014-2015 Sebastian Iffland <Sebastian.Iffland@ecom-ex.com>
 *      » Rewritten to match TYPO3 CMS 6.2+
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

/**
 * Class GaltLang
 * @package Ext\GaltLang
 */
class GaltLang {

	/**
	 * @var array
	 */
	protected $extensionConfiguration = [];

	/**
	 * Function inserts the alternate hreflang tags into the header.
	 * The default language here is set to german (de).
	 * This should be configureable in the extension settings.
	 *
	 * @param  string  $content Empty string (no content to process)
	 * @param  array   $conf    TypoScript configuration
	 * @return string
	 */
	public function insertAlternateTags($content, $conf) {
		$getParameterString = '';
		$hreflangCollection = [];
		$this->extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['galtlang']);
		/** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		/** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObjectRenderer */
		$contentObjectRenderer = $objectManager->get('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		/** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager $configurationManager */
		$configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
		/** @var array $localExtensionConfiguration Enables you to override settings using PageTS */
		$localExtensionConfiguration = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'galtlang');
		\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($this->extensionConfiguration, (array) $localExtensionConfiguration);
		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection $db */
		$db = $GLOBALS['TYPO3_DB'];
		/** @var \TYPO3\CMS\Frontend\Controller\TyposcriptFrontendController $TSFE */
		$TSFE = $GLOBALS['TSFE'];

		$this->buildGetParameterString($getParameterString, $language);

		// Page link configuration returning url only
		$linkConfiguration = [
			'parameter' => $TSFE->id,
			'additionalParams' => $getParameterString,
			'useCacheHash' => (bool) $TSFE->cHash
		];

		// Default language entry
		$page = $db->exec_SELECTgetSingleRow('*', 'pages', 'uid=' . $TSFE->id . $TSFE->sys_page->enableFields('pages'));
		// @root page set hreflang x-default
		if ( (int) $conf['rootPid'] === (int) $TSFE->id ) {
			$hreflangCollection[] = [
				'x-default',
				$conf['baseURL']
			];
		}
		if ( ($page['l18n_cfg'] & 1) === 0 ) {
			$tempConfiguration = $linkConfiguration;
			$tempConfiguration['parameter'] = $conf['altTarget.'][ $this->extensionConfiguration['defaultLang'] ] ?: $TSFE->id;
			$hreflangCollection[] = [
				$this->extensionConfiguration['defaultLang'],
				str_replace( $this->extensionConfiguration['defaultLang'] . '/', '', $contentObjectRenderer->typoLink_URL($tempConfiguration) )
			];
		}

		// Other page language overlays
		$sqlStatement = '
		SELECT sys_language.uid AS sys_language_uid, sys_language.hreflang AS hreflang, static_languages.lg_iso_2 AS iso
		FROM pages_language_overlay
		LEFT JOIN sys_language ON pages_language_overlay.sys_language_uid = sys_language.uid
		LEFT JOIN static_languages ON sys_language.static_lang_isocode = static_languages.uid
		WHERE pages_language_overlay.pid = ' . $TSFE->id . $TSFE->sys_page->enableFields( 'pages_language_overlay' );
		$result = $db->sql_query($sqlStatement);

		while ( $row = $db->sql_fetch_assoc($result) ) {
			$tempConfiguration = $linkConfiguration;
			$tempConfiguration['parameter'] = $conf['altTarget.'][ strtolower($row['iso']) ] ?: $TSFE->id;
			$tempConfiguration['additionalParams'] .= '&L=' . $row['sys_language_uid'];
			$hreflangCollection[] = [
				$row['hreflang'] ?: strtolower($row['iso']),
				$contentObjectRenderer->typoLink_URL($tempConfiguration)
			];
		}
		$db->sql_free_result($result);

		$headerString = '';
		foreach ( $hreflangCollection as $hreflang ) {
			$headerString .= sprintf('<link rel="alternate" hreflang="%1$s" href="%2$s" />', $hreflang[0], $hreflang[1]) . "\r\n";
		}

		// Add the canonical tag
		if ( $this->extensionConfiguration['canonical'] ) {
			$linkConfiguration['parameter'] = $conf['altTarget.'][ strtolower($TSFE->sys_language_isocode) ] ?: $TSFE->id;
			$linkConfiguration['additionalParams'] .= '&L=' . intval($language ?: $TSFE->sys_language_uid);
			$headerString .= sprintf( '<link rel="canonical" href="%1$s" />', $contentObjectRenderer->typoLink_URL($linkConfiguration) ) . "\r\n";
		}

		return $headerString;
	}

	/**
	 * @param string $getParameterString
	 * @param int    $language
	 */
	protected function buildGetParameterString(&$getParameterString, &$language = 0) {
		// Build string containing all GET parameters, without the "L" parameter
		$includeParams = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->extensionConfiguration['includeParams'], TRUE);
		$includeParams[] = 'L'; // Make sure L ist set in excludeParams, otherwise hreflang-tags would not work! Minimum Requirement!
		foreach ( \TYPO3\CMS\Core\Utility\GeneralUtility::_GET() as $key => $value ) {
			if ( $key === 'L' ) {
				$language = $value;
				continue;
			}
			/**
			 * EXT:news => Skip controller and action
			 */
			if ( $key === 'tx_news_pi1' ) {
				if ( $value['news'] ) {
					$getParameterString .= '&' . $key . '[news]=' . $value['news'];
				}
				if ( $value['overwriteDemand']['categories'] ) {
					$getParameterString .= '&' . $key . '[overwriteDemand][categories]=' . $value['overwriteDemand']['categories'];
				}
				if ( $value['overwriteDemand']['tags'] ) {
					$getParameterString .= '&' . $key . '[overwriteDemand][tags]=' . $value['overwriteDemand']['tags'];
				}
				if ( $value['overwriteDemand']['year'] ) {
					$getParameterString .= '&' . $key . '[overwriteDemand][year]=' . $value['overwriteDemand']['year'];
				}
				if ( $value['overwriteDemand']['month'] ) {
					$getParameterString .= '&' . $key . '[overwriteDemand][month]=' . $value['overwriteDemand']['month'];
				}
				if ( $value['@widget_0']['currentPage'] ) {
					$getParameterString .= '&' . $key . '[@widget_0][currentPage]=' . $value['@widget_0']['currentPage'];
				}
				continue;
			}
			if ( in_array($key, $includeParams) ) {
				$getParameterString .= is_array( $value ) ? \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl($key, $value) : '&' . $key . '=' . $value;
			}
		}
	}

}

?>
