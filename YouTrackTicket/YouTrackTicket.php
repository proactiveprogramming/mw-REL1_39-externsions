<?php
/**
 * YouTrackTicket
 *
 * Copyright © 2014 Andrei Nicholson <contact@andreinicholson.com>
 * https://www.mediawiki.org/
 *
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
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @file
 * @ingroup Extensions
 */

if (!defined('MEDIAWIKI')) {
    exit();
}

$wgExtensionCredits['parserhook'][] = array(
    'path' => __FILE__,
    'name' => 'YouTrackTicket',
    'author' => array('Andrei Nicholson'),
    'description' => 'Links to a [https://www.jetbrains.com/youtrack/ YouTrack] issue using the <code>&lt;youtrack&gt;</code> tag',
    'url' => 'https://www.mediawiki.org/wiki/Extension:YouTrackTicket',
    'version' => '1.0.0',
);

$wgAutoloadClasses['YouTrackTicket'] = dirname(__FILE__) . '/YouTrackTicket.body.php';
$wgHooks['ParserFirstCallInit'][] = 'YouTrackTicketSetup';

/**
 * Performs the hook registration.
 *
 * @param Parser &$parser
 * @return bool Always true.
 */
function YouTrackTicketSetup(Parser &$parser)
{
    $parser->setHook('youtrack', array('YouTrackTicket', 'render'));
    return true;
}
