<?php
if( !defined( 'MEDIAWIKI' ) )
{
        echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
        die( 1 );
}
use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserGroupManager;
 
$wgExtensionCredits['parserhook'][] = array( 
        'name' => 'CategoryControl', 
        'author' => '[http://www.mediawiki.org/wiki/User:Kkragenbrink Kevin Kragenbrink, II]', 
        'url' => 'http://mediawiki.org/wiki/Extension:CategoryControl',
        'description' => 'Category and UserGroup based authorizations',
        'version' => 0.2,
); 
 
$wgHooks['getUserPermissionsErrors'][] = 'hookCategoryControl';
$wgCategoryPermissions = array();
 
function hookCategoryControl( &$title, &$wgUser, $action, &$result )
{
        $result = NULL;
        $categories = $title->getParentCategories();
 
        if( is_array( $categories ) && count( $categories ) )
        {
                foreach( $categories AS $category => $index )
                {
                        $category = substr( $category, (strpos($category, ":")+1));
                        $allow = wfUserCategoryCan( $category, $wgUser, $action );
 
                        if( !$allow )
                                break;
                }
        }
        else
                $allow = TRUE;
 
        // Hack to display the proper error message.
        if( !$allow )
        {
                $result = FALSE;
                global $wgGroupPermissions;
                foreach( $wgGroupPermissions AS $group => $rights )
                {
                        $wgGroupPermissions[$group]['read'] = FALSE;
                }
                return FALSE;
        }
 
        return TRUE;
}
 
function wfUserCategoryCan( $category, &$wgUser, $action )
{
        global $wgCategoryPermissions;
		// If the requested category has no specified permissions, allow access.
        if( !array_key_exists( $category, $wgCategoryPermissions ) )
		{			
            return TRUE;
		}
        // If the specified action has no specified permissions, allow access.
        if( !array_key_exists( $action, $wgCategoryPermissions[$category] ) && array_key_exists( '*', $wgCategoryPermissions[$category] ) )
		{		
            return TRUE;
		}	 		
        $permission_lists = is_array( $wgCategoryPermissions[$category][$action] ) ? $wgCategoryPermissions[$category][$action] : $wgCategoryPermissions[$category]['*'];	   
        foreach( $permission_lists AS $list => $permissions )
        {
                $permission[$list] = TRUE;
                if( is_array( $permissions ) )
                {
                        foreach( $permissions AS $group )
                        {
                                $permission[$list] = in_array( $group, $wgUser->getEffectiveGroups() );
                        }
                }
                else
                {					
                    //$permission[$list] = in_array( $permissions, $wgUser->getEffectiveGroups() );
					$userGroupManager = MediaWikiServices::getInstance()->getUserGroupManager();
					$groups = $userGroupManager->getUserEffectiveGroups($wgUser);
					$permission[$list] = in_array( $permissions, $groups );	
                }
        }
		return in_array( TRUE, $permission );
}