<?php
	if (!empty(F::app()->wg->WallBrickHeader)){
		echo F::app()->renderView( 'WallController', 'brickHeader', array( "id" => F::app()->wg->WallBrickHeader) );
	}
?>
<div class="WallGreeting" >
	<?= $greeting ?>
</div>
<div class="Wall" id="Wall">
	<?php if($showNewMessage): ?>
		<?= $app->renderView( 'WallController', 'newMessage' ); ?>
	<?php endif; ?>
	<? if ($showNewMessage): ?>
	<div class="SortingBar">
		<div class="SortingMenu">
			<span class="SortingSelected"><?= $sortingSelected; ?></span>
			<ul class="SortingList">
				<? foreach($sortingOptions as $option): ?>
					<li class="<? if (!empty($option['selected'])): ?>current<? endif ?> <?= $option['id']; ?>">
						<a href="<?= $option['href'] ?>" class="sortingOption">
							<?= $option['text'] ?>
						</a>
					</li>
				<? endforeach; ?>
			</ul>
		</div>
	</div>
	<? endif; ?>
	<ul class="comments">
		<? foreach($threads as $value): ?>
			<? echo $app->renderView( 'WallController', 'message', array('showDeleteOrRemoveInfo' => $showDeleteOrRemoveInfo, 'condense' => $condenseMessage, 'title' => $title, 'replies' => $value->getRepliesWallMessages(), 'comment' => $value->getThreadMainMsg(), 'isreply' => false ) ); ?>
		<? endforeach; ?>
	</ul>
	<?php if($showPager): ?>
		<?= $app->renderView( 'PaginationController', 'index', array('totalItems' => $totalItems, 'itemsPerPage' => $itemsPerPage, 'currentPage' => $currentPage)); ?>
	<?php endif;?>
	<?= $app->renderView( 'WallController', 'renderUserTalkArchiveAnchor', array('renderUserTalkArchiveAnchor' => $renderUserTalkArchiveAnchor,  'title' => $title ) ); ?>
</div>