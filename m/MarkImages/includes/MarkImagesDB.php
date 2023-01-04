<?php
/**
 * MarkImages database interaction code
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */
class MarkImagesDB
{
    const PROP_NAME = 'mi_cache';

    public static function getClassesFromDB(Title $title)
    {
        if ($title->getArticleID() < 0)
            return 0;   //no such page

        $dbr = wfGetDB(DB_REPLICA);
        $classes = $dbr->selectField(
            'page_props',
            'pp_value',
            [
                'pp_page' => $title->getArticleID(),
                'pp_propname' => self::PROP_NAME
            ],
            __METHOD__
        );

        if ($classes == false)
            return '';

        return $classes;
    }

    public static function updateClasses(Title $title, string $classes)
    {
        if ($title->getArticleID() < 0)
            return;   //no such page

        $dbw = wfGetDB(DB_PRIMARY);
        $dbw->startAtomic(__METHOD__);

        $currentClasses = $dbw->selectField(
            'page_props',
            'count(pp_value)',
            [
                'pp_page' => $title->getArticleID(),
                'pp_propname' => self::PROP_NAME
            ],
            __METHOD__
        );

        if ($currentClasses == 0) {
            $dbw->insert(
                'page_props',
                [
                    'pp_page' => $title->getArticleID(),
                    'pp_propname' => self::PROP_NAME,
                    'pp_value' => $classes
                ],
                __METHOD__
            );
        } else {
            $dbw->update(
                'page_props',
                ['pp_value' => $classes],
                [
                    'pp_page' => $title->getArticleID(),
                    'pp_propname' => self::PROP_NAME
                ],
                __METHOD__
            );
        }

        $dbw->endAtomic(__METHOD__);
    }
}
