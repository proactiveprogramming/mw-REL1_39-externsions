<?php

# extension credits

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Category Subscriptions',
	'author' =>' Michael Yatzkanic',
	'version' => 'beta 2',
	'description' => 'Allows user to subscribe to categories and receive email updates listing new and updated pages for each category.',
	'url' => 'http://code.google.com/p/mw-categorysubscriptions'
);


class CategorySubscriptions extends SpecialPage {

	function CategorySubscriptions()
	{
		SpecialPage::SpecialPage("CategorySubscriptions");
		wfLoadExtensionMessages('CategorySubscriptions');
	}

	function execute( $par )
	{
			
		#global wiki variables
		global $wgOut;
		global $wgUser;
		global $wgParser;
		global $wgRequest;
		
		#the user ID of the user accessing this wiki page
		$userID = $wgUser -> getID();
		$wgParser -> disableCache();
		$wgOut->setPagetitle("Category Subscriptions");
		
		#clear the HTML on the page to ensure no cached text appears
		$wgOut->clearHTML();
		
		#if user id is zero, deny access to anonymous user
		if ($userID == 0)
		{
			$wgOut->addHTML("sorry this feature is only available for logged in users");
		}
		else
		{
			#check if post variables exist, and process accordingly
			if ( isset($_POST['removeCategories']) )
			{
				$this->removeCategories($_POST['removeCategories']);
			}
		
			if ( isset($_POST['addCategories']) )
			{
				$this->addCategories($_POST['addCategories']);
			}
		
		
			#if page title and action are not blank, show forms
			#else show manage all form
			if ($par != "")
			{
				$this->showForms($par);
			}
			else
			{
				$this->manageAll();
			}
		}
	}
		
	private function showForms($title_text)
	{
		#check if page exists
		$title = Title::newFromText($title_text);
		if( $title->exists() )
		{
			#if category namespace, show form specific for categories,
			#else show article category form 
			if ($title->getNamespace() == '14')
			{
				$this->categoryForm($title);
			}
			else
			{
				$this->articleForm($title);
			}	
		}
		else
		{ 
			#parameter is not a valid wiki article
			global $wgOut;
			$wiki = 'There is currently no text in this page, you can [[Special:Search/'.$title_text.' | search for this page title]] in other pages or [http://'.$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].'?title='.$title_text.'&action=edit edit this page].';
			$wgOut->addWikiText($wiki);
		}
	}

	private function addCategories($categories)
	{
		global $wgOut;
		global $wgUser;
		
		$dbw =& wfGetDB( DB_MASTER );
			
		foreach($categories as $category){
			$dbw->insert( 'category_subscriptions', array('user_id' => $wgUser->getID(), 'category'    => $category), "", 'IGNORE' );
		}
	}
		
		
	private function removeCategories($categories)
	{
		global $wgOut;
		global $wgUser;
		global $wgDBprefix;
		
		$dbw =& wfGetDB( DB_MASTER );
		
		foreach ($categories as $category){
			$dbw->delete('category_subscriptions', array( 'user_id' => $wgUser->getID(), 'category' => $category ), "" );
		}		
	}


	private function categoryForm($title)
	{
		global $wgOut;
		global $wgUser;
		
		$user_categories = $this->getUserCategoriesAsArray($wgUser->getID());
		$html = '<h2>Category: '.$title->getText().'</h2>';
		$html .= '<br><form action="'.$this->getTitle()->escapeLocalURL().'/'.$title->getNsText().':'.$title->getPartialURL().'" method="post"  >';
		$html .= '<table style="width: 500px;"><tr><th>Category</th><th>Subscribe</th><th>Unsubscribe</th></tr>';
		
		$html .= "<tr><td>".$title->getDBKey()."</td>";
		if ( in_array($title->getDBKey(), $user_categories) )
		{
			$html .= '<td>Already subscribed</td><td><input type="checkbox" name="removeCategories[]" value="' . $title->getDBKey() . '" /></td></tr>';
		}
		else
		{
			$html .= '<td><input type="checkbox" name="addCategories[]" value="' . $title->getDBKey() . '" /></td><td>&nbsp;</td></tr>';
		}
		
		#show sub-categories if available
		$sub_categories = $this->getArticleCategoriesAsArray($title->getArticleID());
		if(count($sub_categories) > 0)
		{
			$html .= '<tr><td colspan="3" style="background-color: #C9C9C9;"><b>Sub-categories</b></td></tr>';				
		
			foreach($sub_categories as $cat)
			{
				$html .= '<tr><td>'.$cat.'</td>';
				if ( in_array($cat, $user_categories) )
				{
					$html .= '<td>Already subscribed</td><td><input type="checkbox" name="removeCategories[]" value="' . $cat . '" /></td></tr>';
				}else
				{
					$html .= '<td><input type="checkbox" name="addCategories[]" value="' . $cat . '" /></td><td>&nbsp;</td></tr>';
				}
			}
		}
		$html .= "</table><br><input type=\"submit\" value=\"Update Subscriptions\"></form>";
		
		$wgOut->addHTML($html);
	}


	private function articleForm($title)
	{
		global $wgOut;
		global $wgUser;
		
		$article_categories = $this->getArticleCategoriesAsArray( $title->getArticleID() );
		$user_categories = $this->getUserCategoriesAsArray($wgUser->getID());
		
		$html = '<h2>'.$title->getText().' belongs to the following categories:</h2>';
		
		#if not the main namespace add namespace text prefix
		if ($title->getNamespace() == '0')
		{
			$html .= '<br><form action="'.$this->getTitle()->escapeLocalURL().'/'.$title->getPartialURL().'" method="post"  >';				
		}
		else
		{
			$html .= '<br><form action="'.$this->getTitle()->escapeLocalURL().'/'.$title->getNsText().':'.$title->getPartialURL().'" method="post"  >';
		}
		
		$html .= '<table style="width: 500px;"><tr><th>Category</th><th>Subscribe</th><th>Unsubscribe</th></tr>';
		
		foreach($article_categories as $cat)
		{
			$html .= '<tr><td>'.$cat.'</td>';
			if ( in_array($cat, $user_categories) )
			{
				$html .= '<td style="text-align: center;">Already subscribed</td><td style="text-align: center;"><input type="checkbox" name="removeCategories[]" value="' . $cat . '" /></td></tr>';
			}else
			{
				$html .= '<td style="text-align: center;"><input type="checkbox" name="addCategories[]" value="' . $cat . '" /></td><td>&nbsp;</td></tr>';
			}
		}
		
		$html .= "</table><br><input type=\"submit\" value=\"Update Subscriptions\"></form>";
		
		$wgOut->addHTML($html);
	}
		
	private function manageAll()
	{
		global $wgOut;
		global $wgUser;
		
		$user_categories = $this->getUserCategoriesAsArray($wgUser->getID());
		
		$html = '<h2>Categories that you are subscribed too</h2>';
		
		
		$html .= '<br><form action="'.$this->getTitle()->escapeLocalURL().'" method="post"  >';
		$html .= '<table style="width: 500px;"><tr><th>Category</th><th>Unsubscribe</th></tr>';
		
		foreach($user_categories as $cat)
		{
			$html .= '<tr><td>'.$cat.'</td><td style="text-align: center;"><input type="checkbox" name="removeCategories[]" value="' . $cat . '" /></td></tr>';
		}
		
		$html .= "</table><br><input type=\"submit\" value=\"Remove Subscriptions\"></form>";
		
		$wgOut->addHTML($html);
	}


	private function getArticleCategoriesAsArray($articleID)
	{
		$dbr =& wfGetDB( DB_SLAVE );
		$res = $dbr->select( 'categorylinks', array('cl_to'), array('cl_from' => $articleID) );
		
		$article_categories = array();
		
		while ( $row = $dbr->fetchObject( $res ) ) {
			$article_categories[] = $row->cl_to;
		}
		
		$dbr->freeResult( $res );
		
		return $article_categories;	
	}
		

	private function getUserCategoriesAsArray($userID)
	{
		$dbr =& wfGetDB( DB_SLAVE );
		$res = $dbr->select( 'category_subscriptions', array('category'), array('user_id' => $userID) );
	
		$user_categories = array();
	
		while ( $row = $dbr->fetchObject( $res ) )
		{
			$user_categories[] = $row->category;
		}
	
		$dbr->freeResult( $res );
			
		return $user_categories;
	}
		
}