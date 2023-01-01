<?php

class WWAPIFormatSSE extends ApiFormatBase {

	/**
	 * Constructor
	 * @param $main ApiMain object
	 * @param $moduleName
	 */
	public function __construct( $main, $moduleName ) {
		parent::__construct( $main, $moduleName );
		// unlike other format modules, don't wait to start
		// output.  There's no going back from here, and all
		// output from now forward must be in SSE format,
		// including errors.
		wfResetOutputBuffers();
                header( 'Content-type: text/event-stream' );
                header( 'Cache-Control: no-cache, no-store, max-age=0, must-revalidate' );
                header( 'Pragma: no-cache' );
		$this->setBufferResult( false );
		wfResetOutputBuffers();
		ob_end_clean();
		// disable all the normal output routines.  All output must
		// be explicitly sent by the WWApi* object.
		$this->disable();
        }

        # send an event
        public function sendEvent( $data, $event=null, $id=null, $retry=null, $comment=null ) {
                if ( $comment !== null ) {
                        echo ': ' . str_replace( "\n", "\n: ", $comment ) . "\n";
                }
                if ( $retry !== null ) {
                        echo "retry: $retry\n";
                }
                if ( $id !== null ) {
                        echo "id: $id\n";
                }
                if ( $event !== null ) {
                        echo "event: $event\n";
                }
                if ( $data !== null ) {
                        echo 'data: ' . str_replace( "\n", "\ndata: ", $data ) . "\n";
                        #wwLog( 'WWApiFormatSSE data: ' . str_replace( "\n", "\ndata: ", $data ) . "\n" );
                }
                echo "\n";
                @ob_flush();
		@ob_end_flush();
                flush();
        }

	public function close() {
		exit(0);
	}

	public function execute() {
		$this->sendEvent(
			json_encode( $this->getResultData() ),
			'return-value'
		);
	}

	public function getMimeType() {
		return null;
	}
}

?>
