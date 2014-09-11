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
	 * @return string
	 */
	public function insertAlternateTags() {
		$getParameterString = '';
		$alternateLanguageEntries = array();
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['galtlang']);
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$contentObjectRenderer = $objectManager->get('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');

			// Build string containing all GET parameters, without the "L" parameter
		unset($_GET['L']);
		foreach($_GET as $key => $value) {
			$getParameterString .= '&' . $key . '=' . $value;
		}

			// Page link configuration returning only the url
		$linkConfiguration = array(
			'returnLast' => 'url',
			'parameter' => $GLOBALS['TSFE']->id,
			'additionalParams' => $getParameterString
		);

			// Default language entry
		$tempConfiguration = $linkConfiguration;
		$tempConfiguration['additionalParams'] .= '&L=0';
		$defaultLanguageEntry = array(
			'uid' => $GLOBALS['TSFE']->id,
			'hreflang' => $extConf['defaultLang'],
			'href' => str_replace($extConf['defaultLang'] . '/', '', $contentObjectRenderer->typolink('', $tempConfiguration))
		);

			// Other page language overlays
		$sqlStatement = '
		SELECT sys_language.uid AS sys_language_uid, sys_language.hreflang AS hreflang, static_languages.lg_iso_2 AS iso
		 FROM pages_language_overlay
		 LEFT JOIN sys_language ON pages_language_overlay.sys_language_uid = sys_language.uid
		 LEFT JOIN static_languages ON sys_language.static_lang_isocode = static_languages.uid
		 WHERE pages_language_overlay.pid = ' . $GLOBALS['TSFE']->id . ' AND NOT pages_language_overlay.hidden AND NOT pages_language_overlay.deleted';
		$result = $GLOBALS['TYPO3_DB']->sql_query($sqlStatement);

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			$tempConfiguration = $linkConfiguration;
			$tempConfiguration['additionalParams'] .= '&L=' . $row['sys_language_uid'];
			$alternateLanguageEntries[] = array(
				'uid' => $GLOBALS['TSFE']->id,
				'hreflang' => $row['hreflang'] ?: strtolower($row['iso']),
				'href' => $contentObjectRenderer->typolink('', $tempConfiguration)
			);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($result);

			// Generate header string, write only when there
		$headerString = str_replace('%hreflang%', $defaultLanguageEntry['hreflang'], str_replace('%href%', $defaultLanguageEntry['href'], '<link rel="alternate" hreflang="%hreflang%" href="%href%" />')) . "\r\n";
		if (count($alternateLanguageEntries)) {
			foreach ($alternateLanguageEntries as $entry) {
				$headerString .= str_replace('%hreflang%', $entry['hreflang'], str_replace('%href%', $entry['href'], '<link rel="alternate" hreflang="%hreflang%" href="%href%" />')) . "\r\n";
			}
		} else {
			return '';
		}

		return $headerString;
	}
}
?>