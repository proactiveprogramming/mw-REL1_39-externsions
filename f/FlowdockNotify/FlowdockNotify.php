<?php
 /*
  *  @author dnoiz1, github.com/dnoiz1
  */

$wgExtensionCredits['notify'][] = array(
    'name' => 'FlowdockNotify',
    'author' => 'Tim Noise',
    'description' => 'A Flowdock inbox notifier for page creation, updates and deletions',
    'path'           => __FILE__,
    'url'            => 'https://github.com/dnoiz1/Mediawiki-FlowdockNotify',
    'version'        => '0.1'
);

/* reference object
curl -i -X POST -H "Content-Type: application/json" -d '{
"flow_token": "",
  "event": "activity",
  "author": {
    "name": "Marty",
    "avatar": "https://avatars.githubusercontent.com/u/3017123?v=3"
  },
  "title": "updated ticket",
  "external_thread_id": "1234567",
  "thread": {
    "title": "Polish the flux capacitor",
    "fields": [{ "label": "Dustiness", "value": "5 - severe" }],
    "body": "The flux capacitor has been in storage for more than 30 years and it needs to be spick and span for the re-launch.",
    "external_url": "https://example.com/projects/bttf/tickets/1234567",
    "status": {
      "color": "green",
      "value": "open"
    }
  }
}' https://api.flowdock.com/messages
*/

function flowdock_make_request($article, $user, $summary, $content, $method)
{
    global $flowdock_token, $wgCanonicalServer;

    $message = array(
        'flow_token' => $flowdock_token,
        'event' => 'activity',
        'author' => array(
            'name' => $user->mRealName,
            'email' => $user->mEmail,
            'avatar' => sprintf("https://www.gravatar.com/avatar/%s?s=50", md5(strtolower($user->mEmail))),
        ),
        'body' => $summary,
        'title' => sprintf("%s has been %s", $article->getTitle()->mTextform, $method),
        'external_thread_id' => (string)$article->getId(),
        'thread' => array(
            'title' => $article->getTitle()->mTextform,
            'body' => $content->getParserOutput($article->getTitle())->getText(),
            'external_url' => sprintf("%s/%s", $wgCanonicalServer, $article->getTitle()->mUrlform),
        )
    );

    $data_string = json_encode($message, JSON_UNESCAPED_SLASHES);

    $ch = curl_init("https://api.flowdock.com/messages");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string))
    );
    $ret = curl_exec($ch);
}

function flowdock_notify_revise($article, $user, $content, $summary,
    $isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId)
{
    $method = $status->value['new'] ? 'created' : 'revised';
    flowdock_make_request($article, $user, $summary, $content, $method);
    return true;
}

function flowdock_notify_delete($article, $user, $reason, $id, $content, $logEntry)
{
    flowdock_make_request($article, $user, $reason, $content, 'deleted');
    return true;
}

$wgHooks['PageContentSaveComplete'][] = array('flowdock_notify_revise');
$wgHooks['ArticleDeleteComplete'][] = array('flowdock_notify_delete');
