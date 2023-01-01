<?php
include dirname(__FILE__).'/includes/TwitterOAuth/twitteroauth.php';
class SpecialTweetiki extends SpecialPage
{
    function __construct()
    {
        parent::__construct( 'Tweetiki' );
    }

    function execute( $par )
    {
        global $wgRequest, $wgOut;
        if(!$wgRequest->getVal('oauth_token'))
        {
            $wgOut->addWikiText('Sorry! This is not the way this page is to be used.');
            return;
        }
        global $api_key, $api_secret, $wiki_url;
        $this->setHeaders();
        $oauth_token = $wgRequest->getVal('oauth_token');
        $oauth_verifier = $wgRequest->getVal('oauth_verifier');
      	$connection=new TwitterOAuth($api_key, $api_secret, $oauth_token,
                    $_SESSION['tw_oauth_token_secret']);
        $token = $_SESSION['tw_oauth_token'];
        $token_credentials = $connection->getAccessToken($oauth_verifier);
        $url = $wiki_url . 'index.php?title=' . $wgRequest->getVal('urltitle');
        $curl = curl_init('https://www.googleapis.com/urlshortener/v1/url');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, Array('Content-Type: application/json'));
        $postdata = '{"longUrl": "' . $url . '"}';
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata); 
        $response = curl_exec($curl);
        $response_array = json_decode($response, true);
        $url = $response_array['id'];
        $text = 'I just edited a wiki page. Be bold, and edit. ' . $url;
        $result = $connection->post('statuses/update',array('status' => $text));
        $wgOut->setTitle('Tweetiki');
        $wgOut->addWikiText('Thanks! Its done.');
    }
}
?>
