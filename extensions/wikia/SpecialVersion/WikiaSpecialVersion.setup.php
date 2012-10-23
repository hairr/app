<?php
/**
 * Wikia Special:Version Extension
 *
 * @author Robert Elwell <robert(at)wikia-inc.com>
 */


$app = F::app();
$dir = dirname(__FILE__) . '/';

/**
 * classes
 */
$app->registerClass('WikiaSpecialVersion',				$dir . 'WikiaSpecialVersion.class.php');
$app->registerClass('WikiaSpecialVersionController',	$dir . 'WikiaSpecialVersionController.class.php');

/**
 * special pages
 */
$app->registerSpecialPage('Version', 'WikiaSpecialVersion');

/**
 * message files
 */
$app->registerExtensionMessageFile('WikiaSearch', $dir . 'WikiaSearch.i18n.php' );

$wgExtensionCredits['other'][] = array(
	'name'				=> 'Wikia Special:Version',
	'version'			=> '1.0',
	'author'			=> '[http://wikia.com/wiki/User:Relwell Robert Elwell]',
	'descriptionmsg'	=> 'wikia-version-desc',
);
