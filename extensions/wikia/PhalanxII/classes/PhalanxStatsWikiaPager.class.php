<?php
class PhalanxStatsWikiaPager extends PhalanxStatsPager {
	public function __construct( $id ) {
		parent::__construct( $id );
		$this->qCond = 'ps_wiki_id';
		$this->pInx = 'wikiId';
	}
	
	function formatRow( $row ) {
		$type = implode( Phalanx::getTypeNames( $row->ps_blocker_type ) );
		$username = $row->ps_blocked_user;
		$timestamp = $this->app->wg->Lang->timeanddate( $row->ps_timestamp );
		$blockId = (int) $row->ps_blocker_id;
		# block
		$phalanxUrl = $this->mSkin->makeLinkObj( $this->mTitle, $blockId, 'id=' . $blockId );
		# stats
		$statsUrl = $this->mSkin->makeLinkObj( $this->mTitleStats, $this->app->wf->Msg('phalanx-link-stats'), 'blockId=' . $blockId );

		$html  = Html::openElement( 'li' );
		$html .= $this->app->wf->MsgExt( 'phalanx-stats-row-per-wiki', array('parseinline', 'replaceafter'), $type, $username, $phalanxUrl, $timestamp, $statsUrl );
		$html .= Html::closeElement( 'li' );

		return $html;
	}
}
