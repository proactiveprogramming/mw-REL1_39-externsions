<?php

/**
 * DataProvider class provide base hook handlers
 *
 * @author Vitold Sedyshev
 */
class DataProvider {

    /**
     * Prepare extensions to work
     *
     * @param DatabaseUpdater $updater
     * @return bool
     */
    public function onLoadExtensionSchemaUpdates(DatabaseUpdater $updater) {
        $updater->addExtensionTable('dp', dirname(__FILE__) . '/../resources/dp.sql');
        return true;
    }

    /**
     * @param Article $article
     * @return bool
     */
    public function onBeforeDisplayNoArticleText( $article ) {

        global $wgOut, $wgSitename;

        $wgOut->addHtml('<div id="noarticletext" class="plainlinks" style="padding-left: 2em; padding-right: 2em">');
        $wgOut->addHtml('<p>На сервере "' . $wgSitename . '" <b>нет статьи</b> с таким названием.</p>');

        $wgOut->addHtml('<p>Вы можете: </p>');
        $wgOut->addHtml('<ul>');

        $title = $article->getTitle();

        $importLink = Title::newFromText('DataProvider', NS_SPECIAL)->getLocalURL(array('page' => $title->getText()));
        $editLink = $title->getLocalURL(array('action'=> 'edit'));
        $incomingLink = Title::newFromText('Ссылки_сюда', NS_SPECIAL)->getLocalURL(array('target' => $title->getText()));
        $searchLink = Title::newFromText('Поиск', NS_SPECIAL)->getLocalURL(array('search' => $title->getText()));;
        $logLink = Title::newFromText('Журналы', NS_SPECIAL)->getLocalURL(array('page' => $title->getText()));;

        // Actions make it on hook
        $actions = array(
            '<b><a href="' . $importLink . '">импорировать статью</a></b> из русского раздела Wikipedia;',
            '<b><a href="' . $searchLink . '">найти упоминания</a></b> данного названия;',
            '<a href="' . $incomingLink . '">найти страницы</a>, которые ссылаются на это название;',
            '<a href="' . $logLink . '">найти соответствующие записи журналов</a>;',
            '<a href="' . $editLink . '">создать такую страницу</a>;'
        );

        foreach($actions as $action) {
            $html = '<li>' . $action . '</li>';
            $wgOut->addHtml($html);
        }
        $wgOut->addHtml('</ul>');
        $wgOut->addHtml('</div>');

        return false;
    }

}
