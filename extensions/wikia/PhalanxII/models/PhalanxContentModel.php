<?php
class PhalanxContentModel extends PhalanxModel {
	protected $title = null;
	const SPAM_WHITELIST_TITLE = 'Spam-whitelist';
	const SPAM_WHITELIST_NS_TITLE = 'Mediawiki:Spam-whitelist';

	public function __construct( $title, $id = 0 ) {
		parent::__construct( __CLASS__, array( 'title' => $title, 'id' => $id ) );
	}
	
	public function isOk() {
		return ( !( $this->title instanceof Title ) || ( $this->title->getPrefixedText() == self::SPAM_WHITELIST_NS_TITLE ) );
	}

	public function setTitle( $title ) {
		$this->title = $title;
		return $this;
	}
	
	public function getTitle() {
		return $this->title;
	}
	
	public function buildWhiteList() {
		$this->wf->profileIn( __METHOD__ );

		$whitelist = array();
		$content = $this->wf->msgForContent( self::SPAM_WHITELIST_TITLE );
		
		if ( $this->wf->emptyMsg( self::SPAM_WHITELIST_TITLE, $content ) ) {
			$this->wf->profileOut( __METHOD__ );
			return $whitelist;
		}
			
		$content = array_filter(
			array_map( 'trim', preg_replace( '/#.*$/', '', explode( "\n", $content ) ) )
		);

		foreach ( $content as $regex ) {
			$regex = str_replace( '/', '\/', preg_replace('|\\\*/|', '/', $regex) );
			$regex = "/https?:\/\/+[a-z0-9_.-]*$regex/i";
			$this->wf->suppressWarnings();
			$regexValid = preg_match($regex, '');
			$this->wf->restoreWarnings();
			if ( $regexValid === false ) continue;
			$whitelist[] = $regex;
		}

		Wikia::log( __METHOD__, __LINE__, count( $whitelist ) . ' whitelist entries loaded.' );

		$this->wf->profileOut( __METHOD__ );
		return $whitelist;
	}
	
	public function displayBlock() {
		$this->wg->Out->setPageTitle( $this->wf->msg( 'spamprotectiontitle' ) );
		$this->wg->Out->setRobotPolicy( 'noindex,nofollow' );
		$this->wg->Out->setArticleRelated( false );
		$this->wg->Out->addHTML( Html::openElement( 'div', array( 'id' => 'spamprotected_summary' ) ) );
		$this->wg->Out->addWikiMsg( 'spamprotectiontext' );
		$this->wg->Out->addHTML( Html::element( 'p', array(), wfMsg( '( Call #3 )' ) ) );
		$this->wg->Out->addWikiMsg( 'spamprotectionmatch', "<nowiki>{Block #{$this->blockId}</nowiki>" );
		$this->wg->Out->addWikiMsg( 'phalanx-content-spam-summary' );
		$this->wg->Out->returnToMain( false, $this->title );
		$this->wg->Out->addHTML( Html::closeElement( 'div' ) );
		$this->logBlock();
	}
	
	public function contentBlock() {
		$msg = "Block #{$this->blockId}";
		$this->logBlock();
		return $msg;
	}
	
	public function reasonBlock() {
		$msg = $this->wf->msgExt( 'phalanx-title-move-summary', 'parseinline' );
		$msg .= $this->wf->msgExt( 'spamprotectionmatch', 'parseinline', "<nowiki>{Block #{$this->blockId}</nowiki>" );
		$this->logBlock();
		
		return $msg;
	}
}
