<?php

namespace Ext\GaltLang;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Klaus Hörmann (http://www.world-direct.at/)
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
 * Userfunction to add the google alternate language tags into the
 * page header.
 *
 * @author Klaus Hörmann <klaus.hoermann@world-direct.at>
 *
 */
class GaltLang {

	/**
	 * Function inserts the alternate hreflang tags into the header.
	 * The default language here is set to german (de).
	 * This should be configureable in the extension settings.
	 *
	 * @param  string  Empty string (no content to process)
	 * @param  array   TypoScript configuration
	 * @return string  HTML output
	 */
	public function insertAlternateTags($content, $conf) {
		$getParameterString = '';
		$alternateLanguageEntries = array();
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['galtlang']);
		/** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		/** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObjectRenderer */
		$contentObjectRenderer = $objectManager->get('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		/** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager $configurationManager */
		$configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
		/** @var array $extConfLocal Enables you to override settings using PageTS */
		$extConfLocal = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'galtlang');
		\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($extConf, (array) $extConfLocal);
		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection $db */
		$db = $GLOBALS['TYPO3_DB'];
		/** @var \TYPO3\CMS\Frontend\Controller\TyposcriptFrontendController $TSFE */
		$TSFE = $GLOBALS['TSFE'];

		$this->buildGetParameterString($getParameterString, $language, $extConf);

			// Page link configuration returning only the url
		$linkConfiguration = array(
			'parameter' => $TSFE->id,
			'additionalParams' => $getParameterString,
			'useCacheHash' => (bool) $TSFE->cHash
		);

			// Default language entry
		$tempConfiguration = $linkConfiguration;
		$tempConfiguration['additionalParams'] .= '&L=0';
		$defaultLanguageEntry = array(
			'uid' => $TSFE->id,
			'hreflang' => $extConf['defaultLang'],
			'href' => str_replace($extConf['defaultLang'] . '/', '', $contentObjectRenderer->typoLink_URL($tempConfiguration))
		);

			// Other page language overlays
		$sqlStatement = '
		SELECT sys_language.uid AS sys_language_uid, sys_language.hreflang AS hreflang, static_languages.lg_iso_2 AS iso
		 FROM pages_language_overlay
		 LEFT JOIN sys_language ON pages_language_overlay.sys_language_uid = sys_language.uid
		 LEFT JOIN static_languages ON sys_language.static_lang_isocode = static_languages.uid
		 WHERE pages_language_overlay.pid = ' . $TSFE->id . ' AND NOT pages_language_overlay.hidden AND NOT pages_language_overlay.deleted';
		$result = $db->sql_query($sqlStatement);

		while ($row = $db->sql_fetch_assoc($result)) {
			$tempConfiguration = $linkConfiguration;
			$tempConfiguration['additionalParams'] .= '&L=' . $row['sys_language_uid'];
			$alternateLanguageEntries[] = array(
				'uid' => $TSFE->id,
				'hreflang' => $row['hreflang'] ?: strtolower($row['iso']),
				'href' => $contentObjectRenderer->typoLink_URL($tempConfiguration)
			);
		}
		$db->sql_free_result($result);

			// Generate header string, write only when there
		$headerString = '';
		if ( (int) $conf['rootPid'] === (int) $TSFE->id ) {
			$headerString = '<link rel="alternate" hreflang="x-default" href="' . $conf['baseURL'] . '" />\r\n';
		}
		$hreflangPattern = '<link rel="alternate" hreflang="%hreflang%" href="%href%" />';
		$headerString .= str_replace('%hreflang%', $defaultLanguageEntry['hreflang'], str_replace('%href%', $defaultLanguageEntry['href'], $hreflangPattern)) . "\r\n";
		if ( count($alternateLanguageEntries) ) {
			foreach ( $alternateLanguageEntries as $entry ) {
				$headerString .= str_replace('%hreflang%', $entry['hreflang'], str_replace('%href%', $entry['href'], $hreflangPattern)) . "\r\n";
			}
		} else {
			return '';
		}

		// Add the canonical tag
		if ( $extConf['canonical'] ) {
//			$linkConfiguration['forceAbsoluteUrl'] = 1;
//			if ( \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SSL') ) {
//				$linkConfiguration['forceAbsoluteUrl.']['scheme'] = 'https';
//			}

			$linkConfiguration['additionalParams'] .= '&L=' . intval($language ?: $TSFE->sys_language_content);
			$headerString .= str_replace('%href%', $contentObjectRenderer->typoLink_URL($linkConfiguration), '<link rel="canonical" href="%href%" />') . "\r\n";
		}

		return $headerString;
	}

	/**
	 * @param string $getParameterString
	 * @param int    $language
	 * @param array  $extConf
	 */
	protected function buildGetParameterString(&$getParameterString, &$language = 0, array $extConf) {
		// Build string containing all GET parameters, without the "L" parameter
		$includeParams = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $extConf['includeParams'], TRUE);
		$includeParams[] = 'L'; // Make sure L ist set in excludeParams, otherwise hreflang-tags would not work! Minimum Requirement!
		foreach ( \TYPO3\CMS\Core\Utility\GeneralUtility::_GET() as $key => $value ) {
			if ( $key === 'L' ) {
				$language = $value;
				continue;
			}
			/**
			 * News Extension Configuration (skip controller and action)
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
				$getParameterString .= is_array($value) ? \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl($key, $value) : '&' . $key . '=' . $value;
			}
		}
	}

}

?>
