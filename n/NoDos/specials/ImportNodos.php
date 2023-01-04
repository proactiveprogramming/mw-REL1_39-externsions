<?php

/**
 * Nodos ontology data importer
 *
 * @ingroup SpecialPage
 */
class ImportNodos extends SpecialPage {

    private $fullInterwikiPrefix;
    private $mapping = 'default';
    private $namespace;
    private $rootpage = '';
    private $logcomment = false;
    private $pageLinkDepth;

    /**
     * Do the actual import
     */
    public function doImport($fileName) {

        $this->pageLinkDepth = 0;
        $this->mapping = "default";
        $user = $this->getUser();

        $isUpload = true;
        if ( $user->isAllowed( 'importupload' ) ) {
            $source = ImportStreamSource::newFromFile( $fileName );
        } else {
            throw new PermissionsError( 'importupload' );
        }

        $out = $this->getOutput();
        if ( !$source->isGood() ) {
            $out->wrapWikiMsg(
                "<p class=\"error\">\n$1\n</p>",
                array( 'importfailed', $source->getWikiText() )
            );
        } else {
            $importer = new WikiImporter( $source->value, $this->getConfig() );
            if ( !is_null( $this->namespace ) ) {
                $importer->setTargetNamespace( $this->namespace );
            } elseif ( !is_null( $this->rootpage ) ) {
                $statusRootPage = $importer->setTargetRootPage( $this->rootpage );
                if ( !$statusRootPage->isGood() ) {
                    $out->wrapWikiMsg(
                        "<p class=\"error\">\n$1\n</p>",
                        array(
                            'import-options-wrong',
                            $statusRootPage->getWikiText(),
                            count( $statusRootPage->getErrorsArray() )
                        )
                    );
                    return;
                }
            }
            $out->addWikiMsg( "importstart" );
            $reporter = new ImportReporter(
                $importer,
                $isUpload,
                $this->fullInterwikiPrefix,
                $this->logcomment
            );
            $reporter->setContext( $this->getContext() );
            $exception = false;

            $reporter->open();
            try {
                $importer->doImport();
            } catch ( Exception $e ) {
                $exception = $e;
            }
            $result = $reporter->close();

            if ( $exception ) {
                # No source or XML parse error
                $out->wrapWikiMsg(
                    "<p class=\"error\">\n$1\n</p>",
                    array( 'importfailed', $exception->getMessage() )
                );
            } elseif ( !$result->isGood() ) {
                # Zero revisions
                $out->wrapWikiMsg(
                    "<p class=\"error\">\n$1\n</p>",
                    array( 'importfailed', $result->getWikiText() )
                );
            } else {
                # Success!
                $out->addWikiMsg( 'importsuccess' );
            }
            $out->addHTML( '<hr />' );
            return $result->isGood();
        }
    }
}