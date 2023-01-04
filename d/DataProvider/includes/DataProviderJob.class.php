<?php

/**
 *
 */
class DataProviderJob extends Job {

    /**
     * @var bool
     */
    public $removeDuplicates = false;

    /**
     * @param Title $title
     * @param array $params
     */
    public function __construct( $title, $params ) {
        parent::__construct( 'DataProviderJob', $title, $params );
    }

    /**
     * @return string
     */
    protected function getEditToken() {
        global $wgUser;
        $result = $wgUser->editToken();
        return $result;
    }

    /**
     * @param Title $title
     * @param string $content
     */
    protected function updatePage($title, $content) {
        global $wgRequest;

        // Request data
        $data = array(
            'action'     => 'edit',
            'title'      => $title->getFullText(),
            'text'       => $content,
            'token'      => $this->getEditToken(),
            'summary'    => '/* DataProvider Internal Import */',
            'notminor'   => true,
        );

        // Change current user
        $this->setUser('WikiSysop');

        // Create API request
        $api = new ApiMain(
            new DerivativeRequest(
                $wgRequest,
                $data,
                false // was posted?
            ),
            true // enable write?
        );
        $api->execute();
    }

    /**
     * Run the job
     *
     * @return bool
     */
    public function run()
    {
        // Update page
        $dpi = new DataProviderImport();
        $content = $dpi->getMWContent($this->title);
        if (empty($content)) {
            $content = 'Internal import error.';
        }
        $this->updatePage($this->title, $content);

        return true;
    }

    /**
     * @param string $user
     * @return void
     */
    private function setUser($user)
    {
        global $wgUser;
        $wgUser = User::newFromName($user);
    }

}
