<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

$magicWords = [];

$magicWords['en'] = [
    // 0 = case insensitive
    // ref: https://www.mediawiki.org/wiki/Manual:Magic_words#Mapping_wiki_text_to_magic_word_IDs
    'addnewsectionbelow' => [ 0, '__ADDNEWSECTIONBELOW__' ],
    'addnewsectionabove' => [ 0, '__ADDNEWSECTIONABOVE__' ],
];

$magicWords['de'] = [
    // https://de.wikipedia.org/wiki/Hilfe:Variablen#Schalter_und_andere_%E2%80%9Emagische_W%C3%B6rter%E2%80%9C
    'addnewsectionbelow' => [ 0, '__NEUER_ABSCHNITTSLINK_DRUEBER__' ],
    'addnewsectionabove' => [ 0, '__NEUER_ABSCHNITTSLINK_RUNTER__' ],
];