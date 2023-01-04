<?php
/**
 * Streamer
 * Streamer Info Special Page
 *
 * @license		LGPLv3
 * @package		Streamer
 * @link		https://www.mediawiki.org/wiki/Extension:Streamer
 *
 **/

class SpecialStreamerInfo extends SpecialPage {
	/**
	 * Output HTML
	 *
	 * @var		string
	 */
	private $content;

	/**
	 * Main Constructor
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct() {
		parent::__construct('StreamerInfo', 'edit_streamer_info');

		$this->wgRequest	= $this->getRequest();
		$this->wgUser		= $this->getUser();
		$this->output		= $this->getOutput();

		$this->DB = wfGetDB(DB_MASTER);
	}

	/**
	 * Main Executor
	 *
	 * @access	public
	 * @return	void	[Outputs to screen]
	 */
	public function execute($subpage) {
		$this->checkPermissions();

		$this->templates = new TemplateStreamerInfo();

		$this->output->addModules('ext.streamer');

		$this->setHeaders();

		switch ($subpage) {
			case 'delete':
				$this->streamerInfoDelete();
				break;
			case 'edit':
				$this->streamerInfoForm();
				break;
			default:
				$this->streamerInfoPage();
				break;
		}

		$this->output->addHTML($this->content);
	}

	/**
	 * Display database listing of streamer information.
	 *
	 * @access	private
	 * @return	void	[Outputs to screen]
	 */
	private function streamerInfoPage() {
		$result = $this->DB->select(
			['streamer'],
			['*'],
			[],
			__METHOD__
		);

		while ($row = $result->fetchRow()) {
			if (is_array($row) && $row['streamer_id'] > 0) {
				$streamers[] = StreamerInfo::newFromRow($row);
			}
		}

		$this->output->setPageTitle(wfMessage('streamer_info_page_title'));
		$this->content = $this->templates->streamerInfoPage($streamers);
	}

	/**
	 * Streamer Information Form
	 *
	 * @access	public
	 * @return	void	[Outputs to screen]
	 */
	public function streamerInfoForm() {
		$streamerId = $this->wgRequest->getInt('streamer_id');
		if ($streamerId > 0) {
			$result = $this->DB->select(
				['streamer'],
				['*'],
				['streamer_id' => intval($streamerId)],
				__METHOD__
			);
			$row = $result->fetchRow();

			$this->streamer = StreamerInfo::newFromRow($row);
		}

		if (!$this->streamer) {
			$this->streamer = new StreamerInfo;
			$action = wfMessage('streamer_info_form_title_add')->escaped();
		} else {
			$action = wfMessage('streamer_info_form_title_edit', $this->streamer->getRemoteName())->escaped();
		}
		$this->streamer->load();

		$return = $this->streamerInfoSave();

		if ($return['success']) {
			$page = Title::newFromText('Special:StreamerInfo');
			$this->output->redirect($page->getFullURL());
			return;
		}

		$this->output->setPageTitle(wfMessage('streamer_info_form_title', $action));
		$this->content = $this->templates->streamerInfoForm($this->streamer, $return['errors']);
	}

	/**
	 * Save streamer information forms.
	 *
	 * @access	private
	 * @return	array	'success' => Boolean $success, 'errors' => Array of error messages
	 */
	private function streamerInfoSave() {
		$success = false;
		if ($this->wgRequest->getVal('do') == 'save') {
			if (!$this->streamer->setService($this->wgRequest->getInt('service'))) {
				$errors['service'] = wfMessage('error_sis_bad_service')->escaped();
			}
			if (!$this->streamer->setRemoteName($this->wgRequest->getVal('remote_name'))) {
				$errors['remote_name'] = wfMessage('error_sis_bad_remote_name')->escaped();
			}
			if (!$this->streamer->setDisplayName($this->wgRequest->getVal('display_name'))) {
				$errors['display_name'] = wfMessage('error_sis_bad_display_name')->escaped();
			}
			if (!$this->streamer->setPageTitle(Title::newFromText($this->wgRequest->getVal('page_title')))) {
				$errors['page_title'] = wfMessage('error_sis_bad_page_title')->escaped();
			}

			if (!count($errors)) {
				$success = $this->streamer->save();

				if (!$success) {
					$errors['service'] = wfMessage('error_streamer_save_error')->escaped();
				}
			}
		}
		return ['success' => $success, 'errors' => $errors];
	}

	/**
	 * Streamer Information Delete
	 *
	 * @access	public
	 * @return	void	[Outputs to screen]
	 */
	public function streamerInfoDelete() {
		$streamerId = $this->wgRequest->getInt('streamer_id');
		if ($streamerId > 0) {
			$result = $this->DB->select(
				['streamer'],
				['*'],
				['streamer_id' => intval($streamerId)],
				__METHOD__
			);
			$row = $result->fetchRow();

			$this->streamer = StreamerInfo::newFromRow($row);
		} else {
			$this->output->showErrorPage('error_streamer_info', 'streamer_not_found');
			return;
		}

		if ($this->wgRequest->getVal('confirm') == 'true' && $this->wgRequest->wasPosted()) {
			$this->streamer->delete();

			$page = Title::newFromText('Special:StreamerInfo');
			$this->output->redirect($page->getFullURL());
			return;
		}

		$this->output->setPageTitle(wfMessage('streamer_info_delete_title', $this->streamer->getRemoteName()));
		$this->content = $this->templates->streamerInfoDelete($this->streamer);
	}

	/**
	 * Hides special page from SpecialPages special page.
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function isListed() {
		return $this->userCanExecute($this->wgUser);
	}
}
