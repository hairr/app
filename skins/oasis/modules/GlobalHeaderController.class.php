<?php

class GlobalHeaderController extends WikiaController {

	public function executeIndex() {
		global $wgLangToCentralMap, $wgCityId, $wgLang;
		global $wgEnableWallExt;
		global $wgGlobalHeaderVerticalColors;

		$userLang = $wgLang->getCode();

		// generate link to hompage
		if ( !empty( $wgLangToCentralMap[$userLang] ) ) {
			$this->centralUrl = $wgLangToCentralMap[$userLang];
		} else {
			$this->centralUrl = 'http://www.wikia.com/Wikia';
		}

		// generate link to AutoWikiCreate
		$langQuery = ($userLang == 'en') ? '' : "?uselang=$userLang";
		$this->createWikiUrl = "http://www.wikia.com/Special:CreateWiki{$langQuery}";

		// global navigation menu
		$category = WikiFactory::getCategory($wgCityId);

		if($category && !wfEmptyMsg('shared-Globalnavigation-'.$category->cat_id, $text = wfMsg('shared-Globalnavigation-'.$category->cat_id))) {
			$messageName = 'shared-Globalnavigation-'.$category->cat_id;
		} else {
			$messageName = 'shared-Globalnavigation';
		}

		$service = new NavigationService();
		$menuNodes = $service->parseMessage($messageName, array(3, 4, 5), 60*60*3 /* 3 hours */, false, false, true /* useSharedMemcKey */);

		wfRunHooks( 'AfterGlobalHeader', array(&$menuNodes, $category, $messageName) );

		$this->menuNodes = $menuNodes;
	}
}
