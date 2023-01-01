<?php

class TopLinkHooks {

    public static function onSkinTemplateOutputPageBeforeExec(&$skin, &$template) {
        $topLink = Html::element( 'a', [ 'href' => '#' ],
           $skin->msg( 'totop' )->text() );
        $template->set('toplink', $topLink);
        $template->data['footerlinks']['places'][] = 'toplink';
        return true;
    }

}
