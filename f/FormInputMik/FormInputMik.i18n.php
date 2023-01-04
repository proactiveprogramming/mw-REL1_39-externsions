<?php

/**
 * Messages file for the FormInputMik extension
 *
 * @file
 * @ingroup Extensions
 */

/**
 * Get all extension messages
 *
 * @return array
 */
$messages = array();

$messages['en'] = array(
	'forminputmik-desc'           	=> 'Checks if the page typed in the input box exists and redirects to either the specified formlink (new page) or forminput (edit page)',
	'forminputmik-error-no-type'  	=> 'You have not specified the type of input box to create.',
	'forminputmik-error-bad-type' 	=> 'Form input type "$1" not recognized. Please specify "create".',
	'createarticle'           		=> 'Add/Edit',
	'forminputmik-ns-main'        	=> 'Main',
);

/** Message documentation (Message documentation)
 * @author Michele Fella
 */
$messages['qqq'] = array(
	'forminputmik-desc' => '{{desc|name=Form Input Mik|url=http://www.mediawiki.org/wiki/Extension:FormInputMik}}',
	'forminputmik-error-no-type' => 'Used as error message.',
);

/** British English (British English)
 * @author Michele Fella <michele.fella@gmail.com>
 */
$messages['en-gb'] = array(
	'forminputmik-error-bad-type' => 'Form input type "$1" not recognised. Please specify "create".',
);

/** Italian (italiano)
 * @author Michele Fella <michele.fella@gmail.com>
 */
$messages['it'] = array(
	'forminputmik-desc' => "Verifica l'esistenz della pagina e redireziona verso l'opportuno forminput (edit) o formlink (add)",
	'forminputmik-error-no-type' => 'Non è stato specificato il tipo di forminputmik da creare.',
	'forminputmik-error-bad-type' => '"$1" non è un tipo di forminputmik riconosciuto. Scegliere il tipo "create".',
	'createarticle' => 'Agiungi/Modifica',
	'forminputmik-ns-main' => 'Principale',
);
