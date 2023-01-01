<?php
class DocxForm extends EditPage
{
	const AS_SUCCESS_UPDATE            = 200;
	const AS_SUCCESS_NEW_ARTICLE       = 201;
	const AS_HOOK_ERROR                = 210;
	const AS_FILTERING                 = 211;
	const AS_HOOK_ERROR_EXPECTED       = 212;
	const AS_BLOCKED_PAGE_FOR_USER     = 215;
	const AS_CONTENT_TOO_BIG           = 216;
	const AS_USER_CANNOT_EDIT          = 217;
	const AS_READ_ONLY_PAGE_ANON       = 218;
	const AS_READ_ONLY_PAGE_LOGGED     = 219;
	const AS_READ_ONLY_PAGE            = 220;
	const AS_RATE_LIMITED              = 221;
	const AS_ARTICLE_WAS_DELETED       = 222;
	const AS_NO_CREATE_PERMISSION      = 223;
	const AS_BLANK_ARTICLE             = 224;
	const AS_CONFLICT_DETECTED         = 225;
	const AS_SUMMARY_NEEDED            = 226;
	const AS_TEXTBOX_EMPTY             = 228;
	const AS_MAX_ARTICLE_SIZE_EXCEEDED = 229;
	const AS_OK                        = 230;
	const AS_END                       = 231;
	const AS_SPAM_ERROR                = 232;
	const AS_IMAGE_REDIRECT_ANON       = 233;
	const AS_IMAGE_REDIRECT_LOGGED     = 234;
	
	var $mArticle;
	var $mTitle;
	var $action;
	var $mId;
	var $value;
	
	function DocxForm( $article, $id ) {
		$this->mArticle =& $article;
		$this->mTitle = $article->getTitle();
		$this->mId = $id;
		$this->action = 'submit';
	}
	/**
	 * Returns an array of html code of the following buttons:
	 * save, diff, preview and live and NEW BUTTON PUBLISH
	 *
	 * @param $tabindex Current tabindex
	 *
	 * @return array
	 */
	public function getEditButtons(&$tabindex) {
		$buttons = array();

		//
		$temp = array(
			'id'        => 'wpSave',
			'name'      => 'wpSave',
			'type'      => 'submit',
			'tabindex'  => ++$tabindex,
			'value'     => wfMsg( 'savePublish' ),
			'accesskey' => wfMsg( 'accesskey-save' ),
			'title'     => wfMsg( 'savePublish' ).' ['.wfMsg( 'accesskey-save' ).']',
		);
		$buttons['save'] = Xml::element('input', $temp, '');

		++$tabindex; // use the same for preview and live preview
		$temp = array(
			'id'        => 'wpPreview',
			'name'      => 'wpPreview',
			'type'      => 'submit',
			'tabindex'  => $tabindex,
			'value'     => wfMsg( 'showpreview' ),
			'accesskey' => wfMsg( 'accesskey-preview' ),
			'title'     => wfMsg( 'tooltip-preview' ) . ' [' . wfMsg( 'accesskey-preview' ) . ']',
		);
		$buttons['preview'] = Xml::element( 'input', $temp, '' );
		$buttons['live'] = '';
		
		// hack new button to save nad not publish
		$temp = array(
			'id'        => 'wpAlterSave',
			'name'      => 'wpAlterSave',
			'type'      => 'submit',
			'tabindex'  => $tabindex,
			'value'     => wfMsg( 'savearticle' ),
			'accesskey' => wfMsg( 'accesskey-save' ),
			'title'     => wfMsg( 'savearticle' ),
		);
		$buttons['publish'] = Xml::element('input', $temp, '');
		
		wfRunHooks( 'EditPageBeforeEditButtons', array( &$this, &$buttons, &$tabindex ) );
		return $buttons;
	}

	/**
	 * Send the edit form and related headers to $wgOut
	 * @param $formCallback Optional callable that takes an OutputPage
	 *                      parameter; will be called during form output
	 *                      near the top, for captchas and the like.
	 */
	function showEditForm( $formCallback=null ) {
		global $wgOut, $wgUser, $wgTitle;

		# If $wgTitle is null, that means we're in API mode.
		# Some hook probably called this function  without checking
		# for is_null($wgTitle) first. Bail out right here so we don't
		# do lots of work just to discard it right after.
		if ( is_null( $wgTitle ) )
			return;

		wfProfileIn( __METHOD__ );

		$sk = $wgUser->getSkin();

		#need to parse the preview early so that we know which templates are used,
		#otherwise users with "show preview after edit box" will get a blank list
		#we parse this near the beginning so that setHeaders can do the title
		#setting work instead of leaving it in getPreviewText
		$previewOutput = '';
		if ( $this->formtype == 'preview' ) {
			$previewOutput = $this->getPreviewText();
		}

		wfRunHooks( 'EditPage::showEditForm:initial', array( &$this ) );

		$this->setHeaders();

		# Enabled article-related sidebar, toplinks, etc.
		$wgOut->setArticleRelated( true );

		if ( $this->showHeader() === false )
			return;

		
		$action = htmlspecialchars($this->getActionURL());
		
		if ( $wgUser->getOption( 'showtoolbar' ) and !$this->isCssJsSubpage ) {
			# prepare toolbar for edit buttons
			$toolbar = EditPage::getEditToolbar();
		} else {
			$toolbar = '';
		}


		$wgOut->addHTML( $this->editFormPageTop );

		if ( $wgUser->getOption( 'previewontop' ) ) {
			$this->displayPreviewArea( $previewOutput, true );
		}

		$wgOut->addHTML( $this->editFormTextTop );

		$templates = $this->getTemplates();
		$formattedtemplates = $sk->formatTemplates( $templates, $this->preview, $this->section != '');

		$hiddencats = $this->mArticle->getHiddenCategories();
		$formattedhiddencats = $sk->formatHiddenCategories( $hiddencats );

		if ( $this->wasDeletedSinceLastEdit() && 'save' != $this->formtype ) {
			$wgOut->wrapWikiMsg(
				"<div class='error mw-deleted-while-editing'>\n$1</div>",
				'deletedwhileediting' );
		} elseif ( $this->wasDeletedSinceLastEdit() ) {
			// Hide the toolbar and edit area, user can click preview to get it back
			// Add an confirmation checkbox and explanation.
			$toolbar = '';
			// @todo move this to a cleaner conditional instead of blanking a variable
		}

		$wgOut->addHTML( <<<HTML
{$toolbar}
<form id="editform" name="editform" method="post" action="$action" enctype="multipart/form-data">
HTML
);
			
		if ( is_callable( $formCallback ) ) {
			call_user_func_array( $formCallback, array( &$wgOut ) );
		}

		wfRunHooks( 'EditPage::showEditForm:fields', array( &$this, &$wgOut ) );

		// Put these up at the top to ensure they aren't lost on early form submission
		$this->showFormBeforeText();
		
		///// hack input for new title
		$wgOut->addHTML( Xml::inputLabel('Tytuł strony: ','newTitle','mw-input', 150, $this->mTitle->mTextform, array('style' => 'margin:20px;')) );
		
		if ( $this->wasDeletedSinceLastEdit() && 'save' == $this->formtype ) {
			$wgOut->addHTML(
				'<div class="mw-confirm-recreate">' .
				$wgOut->parse( wfMsg( 'confirmrecreate',  $this->lastDelete->user_name , $this->lastDelete->log_comment ) ) .
				Xml::checkLabel( wfMsg( 'recreate' ), 'wpRecreate', 'wpRecreate', false,
					array( 'title' => $sk->titleAttrib( 'recreate' ), 'tabindex' => 1, 'id' => 'wpRecreate' )
				) .
				'</div>'
			);
		}

		# If a blank edit summary was previously provided, and the appropriate
		# user preference is active, pass a hidden tag as wpIgnoreBlankSummary. This will stop the
		# user being bounced back more than once in the event that a summary
		# is not required.
		#####
		# For a bit more sophisticated detection of blank summaries, hash the
		# automatic one and pass that in the hidden field wpAutoSummary.
		if ( $this->missingSummary ||
			( $this->section == 'new' && $this->nosummary ) )
				$wgOut->addHTML( Xml::hidden( 'wpIgnoreBlankSummary', true ) );
		$autosumm = $this->autoSumm ? $this->autoSumm : md5( $this->summary );
		$wgOut->addHTML( Xml::hidden( 'wpAutoSummary', $autosumm ) );

		$wgOut->addHTML( Xml::hidden( 'oldid', $this->mArticle->getOldID() ) );

		if ( $this->section == 'new' ) {
			$this->showSummaryInput( true, $this->summary );
			$wgOut->addHTML( $this->getSummaryPreview( true, $this->summary ) );
		}

		$wgOut->addHTML( $this->editFormTextBeforeContent );
		
		if ( $this->isConflict ) {
			// In an edit conflict bypass the overrideable content form method
			// and fallback to the raw wpTextbox1 since editconflicts can't be
			// resolved between page source edits and custom ui edits using the
			// custom edit ui.
			$this->showTextbox1( null, $this->getContent() );
		} else {
			$this->showContentForm();
		}

		$wgOut->addHTML( $this->editFormTextAfterContent );

		$wgOut->addWikiText( $this->getCopywarn() );
		if ( isset($this->editFormTextAfterWarn) && $this->editFormTextAfterWarn !== '' )
			$wgOut->addHTML( $this->editFormTextAfterWarn );

		$this->showStandardInputs();

		$this->showFormAfterText();

		$this->showTosSummary();
		$this->showEditTools();

		$wgOut->addHTML( <<<HTML
{$this->editFormTextAfterTools}
<div class='templatesUsed'>
{$formattedtemplates}
</div>
<div class='hiddencats'>
{$formattedhiddencats}
</div>
HTML
);

		if ( $this->isConflict )
			$this->showConflict();
		
		$wgOut->addHTML( $this->editFormTextBottom );
		$wgOut->addHTML( "</form>\n" );
		if ( !$wgUser->getOption( 'previewontop' ) ) {
			$this->displayPreviewArea( $previewOutput, false );
		}

		wfProfileOut( __METHOD__ );
	}
	
	function edit() {
		global $wgOut, $wgRequest, $wgUser, $wgTitle;
		
		// Allow extensions to modify/prevent this form or submission
		if ( !wfRunHooks( 'AlternateEdit', array( $this ) ) ) {
			return;
		}
		
		if ($wgRequest->getCheck('newTitle')){
		//hack change article title for new one
			$value = $wgRequest->getText('newTitle');
			$this->mTitle = Title::newFromText($value);
		}
		
		wfProfileIn( __METHOD__ );
		wfDebug( __METHOD__.": enter\n" );

		// This is not an article
		$wgOut->setArticleFlag( false );

		$this->importFormData( $wgRequest );
		$this->firsttime = false;

		if ( $this->live ) {
			$this->livePreview();
			wfProfileOut( __METHOD__ );
			return;
		}

		//// hack to save to convert tabel and not publish
		if ( $wgRequest->getCheck('wpAlterSave') ) {
			
			$this->alterSave($wgRequest->getText('wpTextbox1'),$wgRequest->getText('newTitle') );
			$titleObj = Title::makeTitle( NS_SPECIAL, 'DocxConverter' );
       		$action = $titleObj->escapeLocalURL();
       		
			return $wgOut->redirect($action);
		}
		
		$wgOut->addScriptFile( 'edit.js' );
		
		if ( $wgUser->getOption( 'uselivepreview', false ) ) {
			$wgOut->includeJQuery();
			$wgOut->addScriptFile( 'preview.js' );
		}
		// Bug #19334: textarea jumps when editing articles in IE8
		$wgOut->addStyle( 'common/IE80Fixes.css', 'screen', 'IE 8' );

		$permErrors = $this->getEditPermissionErrors();
		if ( $permErrors ) {
			wfDebug( __METHOD__ . ": User can't edit\n" );
			$this->readOnlyPage( $this->getContent( false ), true, $permErrors, 'edit' );
			wfProfileOut( __METHOD__ );
			return;
		} else {
			if ( $this->save ) {
				$this->formtype = 'save';
			} else if ( $this->preview ) {
				$this->formtype = 'preview';
			} else if ( $this->diff ) {
				$this->formtype = 'diff';
			} else { # First time through
				$this->firsttime = true;
				if ( $this->previewOnOpen() ) {
					$this->formtype = 'preview';
				} else {
					$this->formtype = 'initial';
				}
			}
		}

		// If they used redlink=1 and the page exists, redirect to the main article
		if ( $wgRequest->getBool( 'redlink' ) && $this->mTitle->exists() ) {
			$wgOut->redirect( $this->mTitle->getFullURL() );
		}

		wfProfileIn( __METHOD__."-business-end" );

		$this->isConflict = false;
		// css / js subpages of user pages get a special treatment
		$this->isCssJsSubpage      = $this->mTitle->isCssJsSubpage();
		$this->isCssSubpage        = $this->mTitle->isCssSubpage();
		$this->isJsSubpage         = $this->mTitle->isJsSubpage();
		$this->isValidCssJsSubpage = $this->mTitle->isValidCssJsSubpage();

		# Show applicable editing introductions
		if ( $this->formtype == 'initial' || $this->firsttime )
			$this->showIntro();

		if ( $this->mTitle->isTalkPage() ) {
			$wgOut->addWikiMsg( 'talkpagetext' );
		}

		# Optional notices on a per-namespace and per-page basis
		$editnotice_ns   = 'editnotice-'.$this->mTitle->getNamespace();
		if ( !wfEmptyMsg( $editnotice_ns, wfMsgForContent( $editnotice_ns ) ) ) {
			$wgOut->addWikiText( wfMsgForContent( $editnotice_ns )  );
		}
		if ( MWNamespace::hasSubpages( $this->mTitle->getNamespace() ) ) {
			$parts = explode( '/', $this->mTitle->getDBkey() );
			$editnotice_base = $editnotice_ns;
			while ( count( $parts ) > 0 ) {
				$editnotice_base .= '-'.array_shift( $parts );
				if ( !wfEmptyMsg( $editnotice_base, wfMsgForContent( $editnotice_base ) ) ) {
					$wgOut->addWikiText( wfMsgForContent( $editnotice_base )  );
				}
			}
		}

		# Attempt submission here.  This will check for edit conflicts,
		# and redundantly check for locked database, blocked IPs, etc.
		# that edit() already checked just in case someone tries to sneak
		# in the back door with a hand-edited submission URL.
		if ( 'save' == $this->formtype ) {
			if ( !$this->attemptSave() ) {
				wfProfileOut( __METHOD__."-business-end" );
				wfProfileOut( __METHOD__ );
				$this->updateStatus();
				return;
			}
		}

		# First time through: get contents, set time for conflict
		# checking, etc.
		if ( 'initial' == $this->formtype || $this->firsttime ) {
			if ( $this->initialiseForm() === false ) {
				$this->noSuchSectionPage();
				wfProfileOut( __METHOD__."-business-end" );
				wfProfileOut( __METHOD__ );
				return;
			}
			if ( !$this->mTitle->getArticleId() )
				wfRunHooks( 'EditFormPreloadText', array( &$this->textbox1, &$this->mTitle ) );
			else
				wfRunHooks( 'EditFormInitialText', array( $this ) );
		}

		$this->showEditForm();
		wfProfileOut( __METHOD__."-business-end" );
		wfProfileOut( __METHOD__ );
	}
	
	//// hack 
	protected function getActionURL() {
		return '/index.php/Specjalna:DocxConverter?action=edit&id=' . $this->mId ;
	}
	
	protected function alterSave()
	{
		$dbw = wfGetDB( DB_MASTER );
		$dbw->update('converter',
				array(	'title' 	=> $this->mTitle,
					  	'text'		=> $this->textbox1,
				),
				array('id' => $this->mId),
				'DatabaseBase::update',
				array()
		);
		return;
	}
	
	protected function updateStatus()
	{
		if($this->value == 201 || $this->value == 200){
			$titleObj = Title::makeTitle( NS_MAIN, $this->mTitle );
      		$action = $titleObj->escapeLocalURL();
		}
       				
		$dbw = wfGetDB( DB_MASTER );
		$dbw->update('converter',
				array(	'title' 	=> $this->mTitle,
					  	'text'		=> $this->textbox1,
						'status'	=> $this->value,
						'url'		=> $action
				),
				array('id' => $this->mId),
				'DatabaseBase::update',
				array()
		);
		return;
		
	}
/**
	 * Attempt submission
	 * @return bool false if output is done, true if the rest of the form should be displayed
	 */
	function attemptSave() {
		global $wgUser, $wgOut, $wgTitle;

		$resultDetails = false;
		# Allow bots to exempt some edits from bot flagging
		$bot = $wgUser->isAllowed( 'bot' ) && $this->bot;
		$this->value = $this->internalAttemptSave( $resultDetails, $bot );
		if ( $this->value == self::AS_SUCCESS_UPDATE || $this->value == self::AS_SUCCESS_NEW_ARTICLE ) {
			$this->didSave = true;
		}
		
		switch ( $this->value ) {
			
			case self::AS_HOOK_ERROR_EXPECTED:
			case self::AS_CONTENT_TOO_BIG:
		 	case self::AS_ARTICLE_WAS_DELETED:
			case self::AS_CONFLICT_DETECTED:
			case self::AS_SUMMARY_NEEDED:
			case self::AS_TEXTBOX_EMPTY:
			case self::AS_MAX_ARTICLE_SIZE_EXCEEDED:
			case self::AS_END:
				return true;

			case self::AS_HOOK_ERROR:
			case self::AS_FILTERING:
			case self::AS_SUCCESS_NEW_ARTICLE:
			case self::AS_SUCCESS_UPDATE:
				return false;

			case self::AS_SPAM_ERROR:
				$this->spamPage( $resultDetails['spam'] );
				return false;

			case self::AS_BLOCKED_PAGE_FOR_USER:
				$this->blockedPage();
				return false;

			case self::AS_IMAGE_REDIRECT_ANON:
				$wgOut->showErrorPage( 'uploadnologin', 'uploadnologintext' );
				return false;

			case self::AS_READ_ONLY_PAGE_ANON:
				$this->userNotLoggedInPage();
				return false;

		 	case self::AS_READ_ONLY_PAGE_LOGGED:
		 	case self::AS_READ_ONLY_PAGE:
		 		$wgOut->readOnlyPage();
		 		return false;

		 	case self::AS_RATE_LIMITED:
		 		$wgOut->rateLimited();
		 		return false;

		 	case self::AS_NO_CREATE_PERMISSION:
		 		$this->noCreatePermission();
		 		return;

			case self::AS_BLANK_ARTICLE:
		 		$wgOut->redirect( $wgTitle->getFullURL() );
		 		return false;

			case self::AS_IMAGE_REDIRECT_LOGGED:
				$wgOut->permissionRequired( 'upload' );
				return false;
		}
	}
}