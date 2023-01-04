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

/**
 * Change <youtrack> tags into links to YouTrack for the issue specified.
 */
class YouTrackTicket
{
    /**
     * Link to YouTrack issue.
     *
     * @param string $input YouTrack issue ID.
     * @param array $args
     * @param Parser $parser
     * @param PPFrame $frame
     * @return string
     */
    public static function render($input, array $args, Parser $parser, PPFrame $frame)
    {
        global $wgYouTrackTicketShowImage;
        global $wgYouTrackTicketUrl;

        if (empty($wgYouTrackTicketUrl)) {
            return '<!-- Missing required $wgYouTrackTicketUrl configuration variable for YouTrackTicket -->' . $input;
        }

        $image = '<img src="' . $wgYouTrackTicketUrl . '/_classpath/images/youtrack16.png" height="16" width="16" alt="YouTrack" style="padding-left:3px" />';
        $anchor = '<a href="' . $wgYouTrackTicketUrl . '/issue/' . $input . '">' . $input;

        // Show the image by default unless specifically disabled.
        if (!isset($wgYouTrackTicketShowImage) || $wgYouTrackTicketShowImage == true) {
            $anchor .= $image;
        }

        $anchor .= '</a>';

        return $anchor;
    }
}
