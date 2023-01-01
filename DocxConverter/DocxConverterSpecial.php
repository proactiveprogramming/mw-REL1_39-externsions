<?php
if (!defined('MEDIAWIKI')) {
        echo <<<EOT
To install extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/DocxConverter/DocxConverter.php" );
Do not forget about new table in DB;
EOT;
exit( 1 );
}
 
$wgExtensionCredits['specialpage'][] = array(
        'name' => 'DocxConverter',
        'author' => 'Lain',
        'url' => '',
        'description' => 'Docx upload and Convert',
        'descriptionmsg' => 'docxconverter-desc',
        'version' => '0.8',
);
 
$dir = dirname(__FILE__) . '/';

$wgExtensionMessagesFiles['DocxConverter'] = $dir . 'DocxConverter.i18n.php';
$wgAutoloadClasses['DocxConverter'] = $dir . 'DocxConverter.php'; 

$wgSpecialPages['DocxConverter'] = 'DocxConverter'; 
$wgSpecialPageGroups['DocxConverter'] = 'other';
?>