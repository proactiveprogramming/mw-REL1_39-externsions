<?php
/**
 * MarkImages core code
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */
class MarkImages
{
	/**
	 * Return CSS classes for specified Title object in the form of an array.
	 * @param Title $title
	 * @return array
	 */
    public static function getClasses(Title $title) : array {
    	global $wgMarkImagesCategories;
        $cats = $wgMarkImagesCategories;
        $classes = [];

        if (sizeof($cats['recursive']) > 0)
            $tree = $title->getParentCategoryTree();
        else $tree = $title->getParentCategories();

        foreach ($cats['nonrecursive'] as $cat => $class) {
            if (array_key_exists($cat, $tree) && !in_array($class, $classes))
                $classes[] = $class;
        }

        foreach ($cats['recursive'] as $cat => $class) {
            if (self::multi_array_key_exists($cat, $tree) && !in_array($class, $classes))
                $classes[] = $class;
        }

        return $classes;
    }

	/**
	 * Recursively check whether a key exists in an array.
	 * @param $key
	 * @param $array
	 * @return bool
	 */
    public static function multi_array_key_exists($key, $array) : bool {
        if (array_key_exists($key, $array))
            return true;
        else {
            foreach ($array as $nested) {
                if (is_array($nested) && self::multi_array_key_exists($key, $nested))
                    return true;
            }
        }
        return false;
    }
}
