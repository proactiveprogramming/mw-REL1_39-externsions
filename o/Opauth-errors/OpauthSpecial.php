<?php

class OpauthSpecial extends UnlistedSpecialPage {

    public function __construct() {
        parent::__construct('Opauth');
    }

    public function execute( $params ) {

        $this->getOutput()->setPageTitle('Authentication..');

        if( empty($params) ) {
            $this->getOutput()->setPageTitle('Authentication error');
            $this->getOutput()->addHTML( 
                wfMessage('opauth-error')->plain()
            );
            return false;
        }

        if( $params == 'callback.php' ) {
            $this->callback();
        }else{
            $this->init();
        }

    }

    private function callback() {

        global $wgOpauthConfig;

        /**
         * Instantiate Opauth with the loaded config but not run automatically
         */
        $Opauth = new Opauth( $wgOpauthConfig, false );

        /**
         * Fetch auth response, based on transport configuration for callback
         */
        $response = null;

        switch($Opauth->env['callback_transport']){
            case 'session':
            	if( empty( session_id() ) ) {
		            wfSetupSession();
            		//session_start();
	            }
                $response = $_SESSION['opauth'];
                unset($_SESSION['opauth']);
                break;
            case 'post':
                $response = unserialize(base64_decode( $_POST['opauth'] ));
                break;
            case 'get':
                $response = unserialize(base64_decode( $_GET['opauth'] ));
                break;
            default:
                die( '<strong style="color: red;">Error: </strong>Unsupported callback_transport.'."<br>\n" );
                break;
        }

        /**
         * Check if it's an error callback
         */
        if (array_key_exists('error', $response)){
            echo( '<strong style="color: red;">Authentication error: </strong> Opauth returns error auth response.'."<br>\n" );
            print_r($response);
            die();
        }

        /**
         * Auth response validation
         *
         * To validate that the auth response received is unaltered, especially auth response that
         * is sent through GET or POST.
         */
        if (empty($response['auth']) || empty($response['timestamp']) || empty($response['signature']) || empty($response['auth']['provider']) || empty($response['auth']['uid'])){
            echo( '<strong style="color: red;">Invalid auth response: </strong>Missing key auth response components.'."<br>\n" );
            print_r($response);
            die();
        }
        elseif (!$Opauth->validate(sha1(print_r($response['auth'], true)), $response['timestamp'], $response['signature'], $reason)){
            echo( '<strong style="color: red;">Invalid auth response: </strong>'.$reason.".<br>\n" );
            print_r($response);
            die();
        }

        //echo( '<strong style="color: green;">OK: </strong>Auth response is validated.'."<br>\n" );
        /**
         * It's all good. Go ahead with your application-specific authentication logic
         */

        // echo "<pre>".print_r($response,1)."</pre>";
        // die();

        wfRunHooks('OpauthUserAuthorized', array(
            strtolower( $response['auth']['provider'] ),
            $response['auth']['uid'],
            $response['auth']['info'],
            $response['auth']
        ) );

    }

    private function init() {

        global $wgOpauthConfig;

        /**
         * Instantiate Opauth with the loaded config and run automatically
         */
        $Opauth = new Opauth( $wgOpauthConfig );

    }

}