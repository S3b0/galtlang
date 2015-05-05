// Default TypoScript for adding Google alternate hreflang tags
includeLibs.tx_galtlang = EXT:galtlang/Classes/GaltLang.php
page.headerData.777 = USER
page.headerData.777 {
	userFunc = Ext\GaltLang\GaltLang->insertAlternateTags
}