<?php
/**
 * AdvancedBacklinks
 * Copyright (C) 2019  Ostrzyciel
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

$specialPageAliases = [];

/** English (English) */
$specialPageAliases['en'] = [
	'AdvancedBacklinks' => [ 'AdvancedBacklinks', 'Advanced_Backlinks' ],
	'WikitextWantedPages' => [ 'WikitextWantedPages', 'Wikitext_wanted_pages' ],
	'WikitextLonelyPages' => [ 'WikitextLonelyPages', 'Wikitext_lonely_pages' ],
	'WikitextContentLonelyPages' => [ 'WikitextContentLonelyPages', 'Wikitext_content_lonely_pages' ],
	'MostWikitextLinked' => [ 'MostWikitextLinked', 'Most_wikitext_linked' ],
	'MostWikitextLinkedFiles' => [
		'MostWikitextLinkedFiles',
		'MostWikitextLinkedImages',
		'Most_wikitext_linked_images',
		'Most_wikitext_linked_files'
	],
	'UndesiredRedlinks' => [ 'UndesiredRedlinks', 'Undesired_redlinks' ]
];

/*
 * Polish
 */
$specialPageAliases['pl'] = [
	'AdvancedBacklinks' => [ 'Zaawansowane_linkujące' ],
	'WikitextWantedPages' => [ 'Potrzebne_strony_w_wikitekście' ],
	'WikitextLonelyPages' => [ 'Porzucone_strony_w_wikitekście' ],
	'WikitextContentLonelyPages' => [ 'Porzucone_strony_w_wikitekście_przestrzeni_treści' ],
	'MostWikitextLinked' => [ 'Najczęściej_linkowane_w_wikitekście' ],
	'MostWikitextLinkedFiles' => [ 'Najczęściej_linkowane_pliki_w_wikitekście' ],
	'UndesiredRedlinks' => [ 'Niepożądane_redlinki' ]
];