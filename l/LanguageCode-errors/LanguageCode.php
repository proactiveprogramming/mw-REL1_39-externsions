<?php

namespace LanguageCode;

class LanguageCode
{
    public static function registerMagicWord(array &$variableIds)
    {
        $variableIds[] = 'userlanguage';
        $variableIds[] = 'pagelanguage';
    }

    public static function getMagicWord(\Parser $parser, array $cache, $magicWordId, &$ret)
    {
        if ($magicWordId == 'userlanguage') {
            $article = new \Article($parser->getTitle());
            $ret = $article->getContext()->getLanguage()->getCode();
        } elseif ($magicWordId == 'pagelanguage') {
            $ret = $parser->getTitle()->getPageLanguage()->getCode();
        }

        return true;
    }
}
