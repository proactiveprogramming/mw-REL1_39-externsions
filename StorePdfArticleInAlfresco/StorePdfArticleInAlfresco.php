<?php
// have got these modules
require_once ("main/Store.php");
require_once ("main/Delete.php");
require_once ("main/Rename.php");
require_once ("main/Fix.php");

/**
 * Hook hooks
 */
// Create title
$wgHooks['PageContentSaveComplete'][] = 'onPageContentSave';
$wgHooks['onArticleInsertComplete'][] = 'onPageContentSave';
$wgHooks['ArticleSaveComplete'][] = 'onPageContentSave';
// Delete article
$wgHooks['ArticleConfirmDelete'][] = 'onArticleDelete';
$wgHooks['ArticleDeleteComplete'][] = 'onArticleDeleteComplete';
// Rename title of article
$wgHooks['TitleMoveComplete'][] = 'onTitleMoveComplete';
// Help for ExternalStoreAlfresco of alfresco-php-library
$wgHooks['ArticleFromTitle'][] = 'onArticleFromTitle';
?>
