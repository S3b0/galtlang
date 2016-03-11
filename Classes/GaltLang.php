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
 *
 * @package Ext\GaltLang
 */
class GaltLang
{

    /**
     * @var array
     */
    protected $extensionConfiguration = [];

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager = null;

    /**
     * @var \TYPO3\CMS\Frontend\Controller\TyposcriptFrontendController
     */
    protected $typoscriptFrontendController = null;

    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $db = null;


    public function __construct()
    {
        $this->extensionConfiguration = unserialize($GLOBALS[ 'TYPO3_CONF_VARS' ][ 'EXT' ][ 'extConf' ][ 'galtlang' ]);
        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->typoscriptFrontendController = $GLOBALS[ 'TSFE' ];
        $this->db = $GLOBALS[ 'TYPO3_DB' ];
    }

    /**
     * Function inserts the alternate hreflang tags into the header.
     * The default language here is set to german (de).
     * This should be configureable in the extension settings.
     *
     * @param  string $content Empty string (no content to process)
     * @param  array  $conf    TypoScript configuration
     *
     * @return string
     */
    public function insertAlternateTags($content, $conf)
    {
        $headerString = "\r\n<!-- google alternate languages begin -->\r\n";
        $getParameterString = '';
        $hreflangCollection = [];
        $parsedLanguages = [];
        /** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager $configurationManager */
        $configurationManager = $this->objectManager->get(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::class);
        /** @var array $localExtensionConfiguration Enables you to override settings using PageTS */
        $localExtensionConfiguration = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'galtlang');
        \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($this->extensionConfiguration, (array)$localExtensionConfiguration);

        $this->buildGetParameterString($getParameterString, $language);

        /**
         * @root page set hreflang X-DEFAULT
         */
        if ((int)$conf[ 'rootPid' ] === (int)$this->typoscriptFrontendController->id) {
            $hreflangCollection[] = ['x-default', $conf[ 'baseURL' ]];
        }
        /**
         * Add DEFAULT LANGUAGE hreflang if not hidden (l18n_cfg)
         */
        $page = $this->db->exec_SELECTgetSingleRow('*', 'pages', 'uid=' . $this->typoscriptFrontendController->id . $this->typoscriptFrontendController->sys_page->enableFields('pages'));
        if (($page[ 'l18n_cfg' ] & 1) === 0) {
            $hreflangCollection[] = [
                $this->extensionConfiguration[ 'defaultLang' ],
                str_replace($this->extensionConfiguration[ 'defaultLang' ] . '/', '', $this->getLink(
                    $conf[ 'altTarget.' ][ $this->extensionConfiguration[ 'defaultLang' ] ] ?: $this->typoscriptFrontendController->id,
                    $getParameterString . '&L=0'
                ))
            ];
            $parsedLanguages[] = $this->extensionConfiguration[ 'defaultLang' ];
        }

        /**
         * Add ALTERNATE LANGUAGES hreflang
         */
        if ($records = $this->db->exec_SELECTgetRows(
            'sys_language.uid AS sys_language_uid, sys_language.hreflang AS hreflang, static_languages.lg_iso_2 AS iso',
            'pages_language_overlay LEFT JOIN sys_language ON pages_language_overlay.sys_language_uid = sys_language.uid LEFT JOIN static_languages ON sys_language.static_lang_isocode = static_languages.uid',
            'pages_language_overlay.pid = ' . $this->typoscriptFrontendController->id . $this->typoscriptFrontendController->sys_page->enableFields('pages_language_overlay')
        )
        ) {
            foreach ($records as $row) {
                $hreflangCollection[] = [
                    $row[ 'hreflang' ] ?: strtolower($row[ 'iso' ]),
                    $this->getLink(
                        $conf[ 'altTarget.' ][ strtolower($row[ 'iso' ]) ] ?: $this->typoscriptFrontendController->id,
                        $getParameterString . '&L=' . intval($row[ 'sys_language_uid' ])
                    )
                ];
                $parsedLanguages[] = strtolower($row[ 'iso' ]);
            }
        }

        /**
         * Add NON-TRANSLATED PAGE, whereas altTarget is set
         */
        if (is_array($conf[ 'altTarget.' ])) {
            foreach ($conf[ 'altTarget.' ] as $iso => $altTarget) {
                if (in_array($iso, $parsedLanguages)) {
                    continue;
                }
                $lRecord = $this->db->exec_SELECTgetSingleRow('sys_language.uid', 'static_languages LEFT JOIN sys_language ON sys_language.static_lang_isocode=static_languages.uid', 'static_languages.lg_iso_2="' . strtoupper($iso) . '"');
                $hreflangCollection[] = [
                    $iso,
                    $this->getLink($altTarget, $getParameterString . '&L=' . intval($lRecord[ 'uid' ]))
                ];
            }
        }

        foreach ($hreflangCollection as $hreflang) {
            $headerString .= "<link rel=\"alternate\" hreflang=\"{$hreflang[0]}\" href=\"{$hreflang[1]}\" />\r\n";
        }

        // Add the canonical tag
        if ($this->extensionConfiguration[ 'canonical' ]) {
            $headerString .= "<!-- Add canonical tag (toggle in extConf) -->\r\n<link rel=\"canonical\" href=\"" . $this->getLink(
                    $conf[ 'altTarget.' ][ strtolower($this->typoscriptFrontendController->sys_language_isocode) ] ?: $this->typoscriptFrontendController->id,
                    $getParameterString . '&L=' . intval($language ?: $this->typoscriptFrontendController->sys_language_uid)
                ) . "\" />\r\n";
        }

        return $headerString . "<!-- google alternate languages end -->\r\n\r\n";
    }

    private function getLink($parameter = 0, $additionalParams = '')
    {
        /** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObjectRenderer */
        $contentObjectRenderer = $this->objectManager->get(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);

        // Page link configuration returning url only
        $linkConfiguration = [
            'parameter'        => $parameter,
            'additionalParams' => $additionalParams,
            'useCacheHash'     => (bool)$this->typoscriptFrontendController->cHash
        ];

        return $contentObjectRenderer->typoLink_URL($linkConfiguration);
    }

    /**
     * @param string $getParameterString
     * @param int    $language
     */
    protected function buildGetParameterString(&$getParameterString, &$language = 0)
    {
        // Build string containing all GET parameters, without the "L" parameter
        $includeParams = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->extensionConfiguration[ 'includeParams' ], true);
        $includeParams[] = 'L'; // Make sure L ist set in excludeParams, otherwise hreflang-tags would not work! Minimum Requirement!
        foreach (\TYPO3\CMS\Core\Utility\GeneralUtility::_GET() as $key => $value) {
            if ($key === 'L') {
                $language = $value;
                continue;
            }
            /**
             * EXT:news => Skip controller and action
             */
            if ($key === 'tx_news_pi1') {
                if ($value[ 'news' ]) {
                    $getParameterString .= "&{$key}[news]={$value[ 'news' ]}";
                }
                if ($value[ 'overwriteDemand' ][ 'categories' ]) {
                    $getParameterString .= "&{$key}[overwriteDemand][categories]={$value[ 'overwriteDemand' ][ 'categories' ]}";
                }
                if ($value[ 'overwriteDemand' ][ 'tags' ]) {
                    $getParameterString .= "&{$key}[overwriteDemand][tags]={$value[ 'overwriteDemand' ][ 'tags' ]}";
                }
                if ($value[ 'overwriteDemand' ][ 'year' ]) {
                    $getParameterString .= "&{$key}[overwriteDemand][year]={$value[ 'overwriteDemand' ][ 'year' ]}";
                }
                if ($value[ 'overwriteDemand' ][ 'month' ]) {
                    $getParameterString .= "&{$key}[overwriteDemand][month]={$value[ 'overwriteDemand' ][ 'month' ]}";
                }
                if ($value[ '@widget_0' ][ 'currentPage' ]) {
                    $getParameterString .= "&{$key}[@widget_0][currentPage]={$value[ '@widget_0' ][ 'currentPage' ]}";
                }
                continue;
            }
            if (in_array($key, $includeParams)) {
                $getParameterString .= is_array($value) ? \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl($key, $value) : "&{$key}={$value}";
            }
        }
    }

}

?>
