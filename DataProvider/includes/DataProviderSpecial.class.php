<?php

class DataProviderSpecial extends SpecialPage {

    /**
     */
    public function __construct($name = '', $restriction = '', $listed = true, $function = false, $file = 'default', $includable = false)
    {
        parent::__construct('DataProvider', $restriction, $listed, $function, $file, $includable);
    }

    /**
     * Insert article to import
     *
     * @param Title $title
     * @return bool
     */
    protected function addArticleToImport(Title $title) {

        // Duplicate process checking
        $dbw = wfGetDB(DB_MASTER);

        //$count = $dbw->selectField('dp', 'count(*) as count', array())

        // Update
        $result = $dbw->update('dp', array('dp_timestamp' => $dbw->timestamp()), array('dp_title' => $title->getDBkey()), __METHOD__);
        if ($dbw->affectedRows() === 0) {
            // Insert again
            $result = $dbw->insert(
                'dp',
                array(
                    'dp_title' => $title->getDBkey(),
                    'dp_timestamp' => $dbw->timestamp()
                ),
                __METHOD__
            );
        }

        // Create JOB
        $dpj = new DataProviderJob($title, array());
        $dpj->insert();

        return $result;
    }

    /**
     * @param null|string $subPage
     */
    public function execute($subPage)
    {
        global $wgOut, $wgRequest;

        $this->setHeaders();

        $page = $wgRequest->getText('page');
        $title = Title::newFromText($page);
        if ($title->getNamespace() === NS_MAIN) {
            $this->addArticleToImport($title);
            $wgOut->addHtml('<p>Статья "' . $title->getText() . '" была <b>успешно добавлена в очередь на импортирование</b>.</p>');
        } else {
            $wgOut->addHtml('<p>Статья "' . $title->getText() . '" <b>не может быть импортирована из-за своего типа</b>.</p>');
        }
    }

}
