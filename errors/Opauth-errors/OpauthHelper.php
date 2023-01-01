<?php

class OpauthHelper {

    public static function getLoginLink( $provider ) {
        return SpecialPage::getTitleFor('Opauth')->getFullURL().'/'.$provider;
    }

}