<?php
// SPDX-License-Identifier: GPL-2.0-or-later
namespace MwRtf;

use Jstewmc\Rtf\Document;
use ParserOptions;
use ParserOutput;
use TextContent;
use Title;

class RtfContent extends TextContent
{
    public function __construct($text, $modelId = 'rtf')
    {
        parent::__construct($text, $modelId);
    }

    public function getTextForSearchIndex()
    {
        $rtf = $this->getText();

        $document = new Document($rtf);
        $text = $document->write('text');

        return $text;
    }

    protected function fillParserOutput(Title $title, $revId, ParserOptions $options, $generateHtml, ParserOutput &$output)
    {
        $rtf = $this->getText();

        $document = new Document($rtf);
        $html = $document->write('html');
        $output = new ParserOutput($html);

        return $output;
    }
}
