<?php
/**
 * Mapping wiki text to magic word IDs.
 * 
 * PHP version 5
*/
 
$messages = array();
$magicWords = array();

/** English (English) */
$magicWords['en'] = array(
    'MAG_NUMBEREDHEADINGS' => array( 0, '__NUMBEREDHEADINGS__' ),
    'MAG_NONUMBEREDHEADINGS' => array( 0, '__NONUMBEREDHEADINGS__' ),
);
/** German (Deutsch) */
$magicWords['de'] = array(
    'MAG_NONUMBEREDHEADINGS' => array( 0, '__KEINEÜERSCHRIFTENNUMMERIERUNG__', '__NONUMBEREDHEADINGS__' ),
    'MAG_NUMBEREDHEADINGS' => array( 0, '__UEBERSCHRIFTENNUMMERIERUNG__' ), /* not working with Umlaut */
);
/** Colognian (Ripoarisch) */
$magicWords['ksh'] = array(
    'MAG_NONUMBEREDHEADINGS' => array( 0, '__ÖVERSCHRIFTENITNUMMERIERE__', '__KEINEÜERSCHRIFTENNUMMERIERUNG__', '__NONUMBEREDHEADINGS__' ),
    'MAG_NUMBEREDHEADINGS' => array( 0, '__OEVVERSCHRIFTENUMMERIERE__' ), /* not working with Umlaut */
);
/** Norwegian Bokmål (Norsk bokmål)*/
$magicWords['nb'] = array(
    'MAG_NUMBEREDHEADINGS' => array( 0, '__NUMBEREDHEADINGS__', '__NUMERERTEOVERSKRIFTER__'),
    'MAG_NONUMBEREDHEADINGS' => array(0, '__NONUMBEREDHEADINGS__', '__NUMERERTEOVERSKRIFTER__'),
);
/** Arabic (العربية) */
$magicWords['ar'] = array(
    'MAG_NONUMBEREDHEADINGS' => array( 0, 'لا_عناوين_مرقمة', '__NONUMBEREDHEADINGS__' ),
);
/** Egyptian Spoken Arabic (مصرى) */
$magicWords['arz'] = array(
    'MAG_NONUMBEREDHEADINGS' => array( 0, '__لا_عناوين_مرقمة__', 'لا_عناوين_مرقمة', '__NONUMBEREDHEADINGS__' ),
);
/** Chechen (Нохчийн) */
$magicWords['ce'] = array(
    'MAG_NONUMBEREDHEADINGS' => array( 0, '__ТЕРАХЬДОЦУШКОРТА__', '__БЕЗНОМЕРОВЗАГОЛОВКОВ__', '__NONUMBEREDHEADINGS__' ),
);
/** Spanish (Español) */
$magicWords['es'] = array(
    'MAG_NONUMBEREDHEADINGS' => array( 0, '__ENCABEZADOSNOENUMERADOS__', '__NONUMBEREDHEADINGS__' ),
);
/** Malayalam (മലയാളം) */
$magicWords['ml'] = array(
    'MAG_NONUMBEREDHEADINGS' => array( 0, '__ക്രമസംഖ്യാരഹിതതലക്കെട്ടുകൾ__' ),
);
/** Marathi (मराठी) */
$magicWords['mr'] = array(
    'MAG_NONUMBEREDHEADINGS' => array( 0, '__विनाअंकमथळे__', '__NONUMBEREDHEADINGS__' ),
);
/** Nedersaksisch (Nedersaksisch) */
$magicWords['nds-nl'] = array(
    'MAG_NONUMBEREDHEADINGS' => array( 0, '__GIENENUMMERDEKOPJES__', '__GEENGENUMMERDEKOPPEN__', '__NONUMBEREDHEADINGS__' ),
);
/** Dutch (Nederlands) */
$magicWords['nl'] = array(
    'MAG_NONUMBEREDHEADINGS' => array( 0, '__GEENGENUMMERDEKOPPEN__' ),
);
/** Russian (Русский) */
$magicWords['ru'] = array(
    'MAG_NUMBEREDHEADINGS' => array( 0, '__НОМЕРАЗАГОЛОВКОВ__', '__NUMBEREDHEADINGS__' ),
    'MAG_NONUMBEREDHEADINGS' => array( 0, '__БЕЗНОМЕРОВЗАГОЛОВКОВ__', '__NONUMBEREDHEADINGS__' ),
);
/** Swedish (Svenska) */
$magicWords['sv'] = array(
    'MAG_NONUMBEREDHEADINGS' => array( 0, '__INTENUMRERADERUBRIKER__', '__NONUMBEREDHEADINGS__' ),
);
/** Turkish (Türkçe) */
$magicWords['tr'] = array(
    'MAG_NONUMBEREDHEADINGS' => array( 0, '__NUMARALIBAŞLIKYOK__', '__NONUMBEREDHEADINGS__' ),
);
/** Ukrainian (Українська) */
$magicWords['uk'] = array(
    'MAG_NONUMBEREDHEADINGS' => array( 0, '__БЕЗНОМЕРІВЗАГОЛОВКІВ__', '__БЕЗНОМЕРОВЗАГОЛОВКОВ__', '__NONUMBEREDHEADINGS__' ),
);
