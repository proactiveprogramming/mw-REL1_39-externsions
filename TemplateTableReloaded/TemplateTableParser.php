<?php
/**
 * TemplateTableParser
 *
 * Copyright 2015 Rusty Burchfield
 *
 * Licensed under GPLv2 or later (see COPYING)
 */

class TemplateTableParser extends Parser {

  private $callData = array();

  public function getCallData() {
    return $this->callData;
  }

  public function clearCallData() {
    $this->callData = array();
  }

  public function braceSubstitution($piece, $frame) {
    $this->callData[] = array('piece' => $piece, 'frame' => $frame);

    return parent::braceSubstitution($piece, $frame);
  }
}
