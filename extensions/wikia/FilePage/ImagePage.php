<?php

if( !defined( 'MEDIAWIKI' ) )
	die( 1 );

/**
 * This is an override and extension of includes/ImagePage.php
 * As Wikia, we want to output a different markup structure and css for File pages than default MediaWiki.
 * WikiaVideoPage will inherit off of this class
 *
 * @ingroup Media
 * @author Hyun
 */
class WikiaImagePage extends ImagePage {

	/**
	 * TOC override so Wikia File Page does not return any TOC
	 *
	 * @param $metadata Boolean - doesn't matter
	 * @return String - will return empty string to add
	 */
	protected function showTOC( $metadata ) {
		global $wgEnableVideoPageRedesign;
		if(empty($wgEnableVideoPageRedesign)) {
			return parent::showTOC($metadata);
		}
		return '';
	}

	/**
	 * imageDetails override
	 * Image page doesn't need the wrapper, but WikiaFilePage does
	 */
	protected function imageDetails($showmeta, $formattedMetadata) {
		global $wgOut, $wgEnableVideoPageRedesign, $wgJsMimeType, $wgExtensionsPath;

		if(empty($wgEnableVideoPageRedesign)) {
			parent::imageDetails($showmeta, $formattedMetadata);
			return;
		}

		// move these two to WikiaFilePage package after full release
		$wgOut->addStyle(AssetsManager::getInstance()->getSassCommonURL('extensions/wikia/FilePage/css/FilePage.scss'));
		$wgOut->addScript( "<script type=\"{$wgJsMimeType}\" src=\"{$wgExtensionsPath}/wikia/FilePage/js/FilePage.js\"></script>\n" );

		$app = F::app();
		$wgOut->addHtml( $app->renderView( 'FilePageController', 'fileUsage', array('type' => 'local') ) );
		$wgOut->addHtml( $app->renderView( 'FilePageController', 'fileUsage', array('type' => 'global') ) );
		$wgOut->addHtml( $app->renderPartial( 'FilePageController', 'seeMore', array() ));
		$wgOut->addHtml('<div class="more-info-wrapper">');
		parent::imageDetails($showmeta, $formattedMetadata);
		$wgOut->addHtml('</div>');
		$wgOut->addHtml( $app->renderView( 'FilePageController', 'relatedPages', array() ) );
	}

	/**
	 * imageListing override.
	 * for WikiaFilePage, imageListing will be printed under additionalDetails()
	 */
	protected function imageListing() {
		global $wgEnableVideoPageRedesign;

		if(empty($wgEnableVideoPageRedesign)) {
			parent::imageListing();
			return;
		}

		// do nothing on purpose
	}

	protected function openShowImage() {
		global $wgEnableVideoPageRedesign;

		parent::openShowImage();
		if(!empty($wgEnableVideoPageRedesign)) {
			$this->renderDescriptionHeader();
		}
	}

	protected function renderDescriptionHeader() {
		global $wgOut, $wgLang;

		// Contstruct the h2 with edit link
		$skin = $wgOut->getSkin();
		$headline = wfMessage('video-page-description-heading')->text();
		$args = array(
			$this->getTitle(), // title obj
			0, // section
			$headline, // heading text
			$wgLang->getCode() // lang
		);
		$editSection = call_user_func_array( array( $skin, 'doEditSectionLink' ), $args );

		$descriptionHeaderHtml = Linker::makeHeadline("2", ">", $headline, $headline, $editSection);

		// Display description text or default message
		$content = FilePageHelper::stripCategoriesFromDescription( $this->getContent() );
		$isContentEmpty = empty($content);

		$html = F::app()->renderPartial( 'FilePageController', 'description', array('isContentEmpty' => $isContentEmpty, 'descriptionHeaderHtml' => $descriptionHeaderHtml) );

		$wgOut->addHTML( $html );
	}

}