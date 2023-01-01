<?php
// SPDX-License-Identifier: GPL-2.0-or-later
namespace MwRtf;

use TextContentHandler;

class RtfContentHandler extends TextContentHandler
{
    public function __construct($modelId = 'rtf', $formats = ['text/rtf'])
    {
        parent::__construct($modelId, $formats);
    }

    protected function getContentClass()
    {
        return RtfContent::class;
    }
}
