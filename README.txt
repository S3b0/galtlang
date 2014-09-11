// Default TypoScript for adding Google alternate hreflang tags
includeLibs.tx_galtlang = EXT:galtlang/class.user_galtlang.php
page.headerData.777 = USER
page.headerData.777 {
	userFunc = Ext\GaltLang\GaltLang->insertAlternateTags
}