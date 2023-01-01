<?php
/**
 * SpriteSheet
 * SpriteSheet Class
 *
 * @author		Alexia E. Smith
 * @license		LGPL v3.0
 * @package		SpriteSheet
 * @link		https://github.com/CurseStaff/SpriteSheet
 *
 **/

class SpriteSheet {
	/**
	 * Mediawiki Database Object
	 *
	 * @var		object
	 */
	private $DB = false;

	/**
	 * Title Object
	 *
	 * @var		object
	 */
	private $title = false;

	/**
	 * Data holder for database values.
	 *
	 * @var		array
	 */
	private $data = [];

	/**
	 * Fully loaded from the database.
	 *
	 * @var		boolean
	 */
	protected $isLoaded = false;

	/**
	 * Where this object was loaded from.
	 *
	 * @var		string
	 */
	public $newFrom = null;

	/**
	 * Memory Cache for already loaded SpriteName objects.
	 *
	 * @var		array
	 */
	private $spriteNameCache = [];

	/**
	 * Memory Cache for already loaded SpriteSheet objects.  This is mainly used by the LogFormatter and parser tag to prevent reloading from the database for each entry.  It is recommended to not use the cache when in the editor interface.
	 *
	 * @var		array
	 */
	static protected $spriteSheets = [];

	/**
	 * Main Constructor
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct() {
		$this->DB = wfGetDB(DB_MASTER);
	}

	/**
	 * Create a new instance of this class from a Sprite Sheet database identification number.
	 *
	 * @access	public
	 * @param	integer	Sprite Sheet database identification number.
	 * @return	mixed	SpriteSheet or false on error.
	 */
	static public function newFromId($id) {
		if ($id < 1) {
			return false;
		}

		$spriteSheet = new self;
		$spriteSheet->setId(intval($id));

		$spriteSheet->newFrom = 'id';

		$success = $spriteSheet->load();

		return ($success ? $spriteSheet : false);
	}

	/**
	 * Create a new instance of this class from a Title object.
	 *
	 * @access	public
	 * @param	object	Title
	 * @param	boolean	[Optional] Stash the object to quick retrieval.
	 * @return	mixed	SpriteSheet or false on error.
	 */
	static public function newFromTitle(Title $title, $useMemoryCache = false) {
		if ($title->getNamespace() != NS_FILE || !$title->getDBkey()) {
			return false;
		}

		if ($useMemoryCache && isset(self::$spriteSheets[$title->getDBkey()])) {
			return self::$spriteSheets[$title->getDBkey()];
		}

		$spriteSheet = new self;
		$spriteSheet->setTitle($title);

		$spriteSheet->newFrom = 'title';

		$success = $spriteSheet->load();

		if (!$spriteSheet->isLoaded && $title->isAlwaysKnown() && !$title->exists()) {
			//This could be a remote file repository title.
			$spriteSheetRemote = SpriteSheetRemote::newFromTitle($title);
			if ($spriteSheetRemote === false) {
				$success = false;
			} else {
				$success = true;
				$spriteSheet = $spriteSheetRemote;
			}
		}

		if ($success) {
			self::$spriteSheets[$title->getDBkey()] = $spriteSheet;

			return $spriteSheet;
		}
		return false;
	}

	/**
	 * Load a new SpriteSheet object from a database row.
	 *
	 * @access	public
	 * @param	array	Database Row
	 * @return	mixed	$spriteSheet or false on error.
	 */
	static public function newFromRow($row) {
		$spriteSheet = new self;

		$spriteSheet->newFrom = 'row';

		$spriteSheet->load($row);

		if (!$spriteSheet->getId()) {
			return false;
		}

		return $spriteSheet;
	}

	/**
	 * Load from the database.
	 *
	 * @access	public
	 * @param	array	[Optional] Database row to load from.
	 * @return	boolean	Success
	 */
	public function load($row = null) {
		if (!$this->isLoaded) {
			if ($this->newFrom != 'row') {
				switch ($this->newFrom) {
					case 'id':
						$where = [
							'spritesheet_id' => $this->getId()
						];
						break;
					case 'title':
						$where = [
							'title' => $this->title->getDBkey()
						];
						break;
				}

				$result = $this->DB->select(
					['spritesheet'],
					['*'],
					$where,
					__METHOD__
				);

				$row = $result->fetchRow();
			}

			if (is_array($row)) {
				$this->data = $row;

				//Title was not set beforehand.
				if ($this->title === false) {
					$title = Title::newFromText($row['title'], NS_FILE);
					if (!$title) {
						return false;
					} else {
						$this->setTitle($title);
					}
				}
				$this->isLoaded = true;
			}
		}

		return true;
	}

	/**
	 * Save Sprite Sheet to the database.
	 *
	 * @access	public
	 * @return	boolean	Success
	 */
	public function save() {
		$success = false;

		$save = $this->data;
		unset($save['spritesheet_id']);
		$save['edited'] = time();

		$spriteSheetId = $this->getId();

		$this->DB->startAtomic(__METHOD__);
		if ($spriteSheetId > 0) {
			$oldResult = $this->DB->select(
				['spritesheet'],
				['*'],
				['spritesheet_id' => $spriteSheetId],
				__METHOD__
			);
			$oldRow = $oldResult->fetchRow();
			if (is_array($oldRow)) {
				$this->DB->insert(
					'spritesheet_rev',
					$oldRow,
					__METHOD__
				);
			}

			//Do the update.
			$result = $this->DB->update(
				'spritesheet',
				$save,
				['spritesheet_id' => $spriteSheetId],
				__METHOD__
			);
		} else {
			//Do the insert.
			$result = $this->DB->insert(
				'spritesheet',
				$save,
				__METHOD__
			);
			$spriteSheetId = $this->DB->insertId();
		}

		if ($result !== false) {
			global $wgUser;

			//Enforce sanity on data.
			$this->data['spritesheet_id']	= $spriteSheetId;
			$this->data['edited']			= $save['edited'];

			$this->DB->endAtomic(__METHOD__);

			$extra = [];
			$oldSpriteSheet = $this->getPreviousRevision();

			if ($oldSpriteSheet instanceOf SpriteSheet && $oldSpriteSheet->getRevisionId() !== false) {
				$extra['4:spritesheet_rev_id'] = $oldSpriteSheet->getRevisionId();
			}

			$log = new ManualLogEntry('sprite', 'sheet');
			$log->setPerformer($wgUser);
			$log->setTarget($this->getTitle());
			$log->setComment(null);
			$log->setParameters($extra);
			$logId = $log->insert();
			$log->publish($logId);

			$success = true;
		} else {
			$this->DB->cancelAtomic(__METHOD__);
		}

		return $success;
	}

	/**
	 * Set the Sprite Sheet ID
	 *
	 * @access	public
	 * @param	integer	Sprite Sheet ID
	 * @return	boolean	True on success, false if the ID is already set.
	 */
	public function setId($id) {
		if (!$this->data['spritesheet_id']) {
			$this->data['spritesheet_id'] = intval($id);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Return the database identification number for this Sprite Sheet.
	 *
	 * @access	public
	 * @return	integer	Sprite Sheet ID
	 */
	public function getId() {
		return !isset($this->data['spritesheet_id']) ? 0 : intval($this->data['spritesheet_id']);
	}

	/**
	 * Return if this sprite sheet exists.
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function exists() {
		return isset($this->data['spritesheet_id']) ? $this->data['spritesheet_id'] > 0 : false;
	}

	/**
	 * Set the Title object.
	 *
	 * @access	public
	 * @param	object	Title
	 * @return	void
	 */
	public function setTitle(Title $title) {
		$this->title = $title;

		$this->data['title'] = $this->title->getDBkey();
	}

	/**
	 * Return the current Tltle object.
	 *
	 * @access	public
	 * @return	object	Title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Set the number of columns.
	 *
	 * @access	public
	 * @param	integer	Columns
	 * @return	void
	 */
	public function setColumns($columns) {
		$this->data['columns'] = abs(intval($columns));
	}

	/**
	 * Return the number of columns.
	 *
	 * @access	public
	 * @return	integer	Columns
	 */
	public function getColumns() {
		return !isset($this->data['columns']) ? 0 : intval($this->data['columns']);
	}

	/**
	 * Set the number of rows.
	 *
	 * @access	public
	 * @param	integer	Rows
	 * @return	void
	 */
	public function setRows($rows) {
		$this->data['rows'] = abs(intval($rows));
	}

	/**
	 * Return the number of rows.
	 *
	 * @access	public
	 * @return	integer	Rows
	 */
	public function getRows() {
		return !isset($this->data['rows']) ? 0 : intval($this->data['rows']);
	}

	/**
	 * Set the sprite inset.
	 *
	 * @access	public
	 * @param	integer	Inset
	 * @return	void
	 */
	public function setInset($inset) {
		$this->data['inset'] = abs(intval($inset));
	}

	/**
	 * Return the sprite inset.
	 *
	 * @access	public
	 * @return	integer	Inset
	 */
	public function getInset() {
		return !isset($this->data['inset']) ? 0 : intval($this->data['inset']);
	}

	/**
	 * Returns false if the column or row are out of bounds.
	 *
	 * @access	public
	 * @param	integer	Column
	 * @param	integer	Row
	 * @return	boolean	Valid
	 */
	public function validateSpriteCoordindates($column, $row) {
		$column++;
		$row++;

		if ($column > $this->getColumns() || $column < 0) {
			return false;
		}
		if ($row > $this->getRows() || $row < 0) {
			return false;
		}
		return true;
	}

	/**
	 * Return sprite HTML at coordinate position.
	 *
	 * @access	public
	 * @param	integer	Column
	 * @param	integer	Row
	 * @param	integer	[Optional] Thumbnail Width
	 * @param	integer	[Optional] Page to link to.
	 * @return	mixed	HTML or false on error.
	 */
	public function getSpriteHtml($column, $row, $thumbWidth = null, $link = false) {
		$file = wfFindFile($this->getTitle());

		$column = intval($column);
		$row = intval($row);

		$link = $this->getUrlFromText($link);

		if (is_object($file) && $file->exists()) {
			if ($thumbWidth > 0) {
				$originalWidth = $file->getWidth();

				$scaling = $thumbWidth / ($originalWidth / $this->getColumns());

				if ($scaling > 1) {
					//Reset because Mediawiki will never resize past 100%.
					$scaling = 1;
				}

				$file = $file->transform(['width' => $file->getWidth() * $scaling, 'height' => $file->getHeight() * $scaling]);
			}

			$spriteWidth = ($file->getWidth() / $this->getColumns());
			$spriteHeight = ($file->getHeight() / $this->getRows());

			$spriteX = ($spriteWidth * $column) + $this->getInset();
			$spriteY = ($spriteHeight * $row) + $this->getInset();

			$spriteWidth = $spriteWidth - ($this->getInset() * 2);
			$spriteHeight = $spriteHeight - ($this->getInset() * 2);

			return "<div class='sprite' style='width: {$spriteWidth}px; height: {$spriteHeight}px; overflow: hidden; position: relative;'>".($link !== false ? "<a href='{$link}'>" : '')."<img src='".$file->getUrl()."' style='position: absolute; left: -{$spriteX}px; top: -{$spriteY}px;'/>".($link !== false ? "</a>" : '')."</div>";
		}
		return false;
	}

	/**
	 * Get the HTML representation of a named sprite.
	 *
	 * @access	public
	 * @param	string	Sprite Name
	 * @param	integer	[Optional] Thumbnail Width
	 * @param	integer	[Optional] Page to link to.
	 * @return	mixed	HTML or false on error.
	 */
	public function getSpriteHtmlFromName($name, $thumbWidth = null, $link = false) {
		$spriteName = $this->getSpriteName($name);

		if ($spriteName->exists()) {
			$values = $spriteName->getValues();
			return $this->getSpriteHtml($values['xPos'], $values['yPos'], $thumbWidth, $link);
		}
		return false;
	}

	/**
	 * Get a new SpriteName class and cache it as needed.
	 *
	 * @access	public
	 * @param	string	Name
	 * @return	object	SpriteName
	 */
	public function getSpriteName($name) {
		if (array_key_exists($name, $this->spriteNameCache)) {
			$spriteName = $this->spriteNameCache[$name];
		} else {
			$spriteName = SpriteName::newFromName($name, $this);
			if ($spriteName->exists()) {
				$this->spriteNameCache[$name] = $spriteName;
			}
		}
		return $spriteName;
	}

	/**
	 * Convenience function to get to getSpriteName().
	 *
	 * @access	public
	 * @param	string	Name
	 * @return	object	SpriteName
	 */
	public function getSliceName($name) {
		return $this->getSpriteName($name);
	}

	/**
	 * Return all named sprites/slices for thie sprite sheet.
	 *
	 * @access	public
	 * @return	array	Named Sprite Cache
	 */
	public function getAllSpriteNames() {
		$result = $this->DB->select(
			['spritename'],
			['*'],
			[
				'spritesheet_id'	=> $this->getId()
			],
			__METHOD__
		);

		while ($row = $result->fetchRow()) {
			$spriteName = SpriteName::newFromRow($row, $this);
			if ($spriteName->exists()) {
				$this->spriteNameCache[$spriteName->getName()] = $spriteName;
			}
		}
		return $this->spriteNameCache;
	}

	/**
	 * Returns false if the slice percentages are out of bounds.
	 *
	 * @access	public
	 * @param	integer	X coordinate, percentage
	 * @param	integer	Y coordinate, percentage
	 * @param	integer	Width, percentage
	 * @param	integer	Height, percentage
	 * @return	boolean	Valid
	 */
	public function validateSlicePercentages($xPercent, $yPercent, $widthPrecent, $heightPercent) {
		$position = [
			$xPercent,
			$yPercent
		];

		foreach ($position as $value) {
			if ($value >= 100 || $value < 0) {
				return false;
			}
		}

		$dimensions = [
			$widthPrecent,
			$heightPercent
		];

		foreach ($dimensions as $value) {
			if ($value > 100 || $value <= 0) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Return slice HTML from input.
	 *
	 * @access	public
	 * @param	integer	X coordinate, percentage
	 * @param	integer	Y coordinate, percentage
	 * @param	integer	Width, percentage
	 * @param	integer	Height, percentage
	 * @param	integer	[Optional] Thumbnail Width
	 * @param	integer	[Optional] Page to link to.
	 * @param	boolean	[Optional] Use pixel positioning instead of percentage.
	 * @return	mixed	HTML or false on error.
	 */
	public function getSliceHtml($x, $y, $width, $height, $thumbWidth = null, $link = false, $pixelMode = false) {
		$file = wfFindFile($this->getTitle());

		$link = $this->getUrlFromText($link);

		if (is_object($file) && $file->exists()) {
			$scaling = 1;
			if ($thumbWidth > 0) {
				$originalWidth = $file->getWidth();

				if ($pixelMode) {
					$scaling = $thumbWidth / $width;
				} else {
					$scaling = $thumbWidth / ($originalWidth * ($width / 100));
				}
				if ($scaling > 1) {
					//Reset because Mediawiki will never resize past 100%.
					$scaling = 1;
				}

				$file = $file->transform(['width' => $file->getWidth() * $scaling, 'height' => $file->getHeight() * $scaling]);
			}

			if (!$pixelMode) {
				//Scaling does not need to be applied when using percentages.
				$sliceX = $file->getWidth() * ($x / 100);
				$sliceY = $file->getHeight() * ($y / 100);

				$sliceWidth = $file->getWidth() * ($width / 100);
				$sliceHeight = $file->getHeight() * ($height / 100);
			} else {
				$sliceX = $x * $scaling;
				$sliceY = $y * $scaling;

				$sliceWidth = $width * $scaling;
				$sliceHeight = $height * $scaling;
			}

			return "<div class='sprite' style='width: {$sliceWidth}px; height: {$sliceHeight}px; overflow: hidden; position: relative;'>".($link !== false ? "<a href='{$link}'>" : '')."<img src='".$file->getUrl()."' style='position: absolute; left: -{$sliceX}px; top: -{$sliceY}px;'/>".($link !== false ? "</a>" : '')."</div>";
		}
		return false;
	}

	/**
	 * Get the HTML representation of a named slice.
	 *
	 * @access	public
	 * @param	string	Slice Name
	 * @param	integer	[Optional] Thumbnail Width
	 * @param	integer	[Optional] Page to link to.
	 * @param	boolean	[Optional] Use pixel positioning instead of percentage.
	 * @return	mixed	HTML or false on error.
	 */
	public function getSliceHtmlFromName($name, $thumbWidth = null, $link = false, $pixelMode = false) {
		$sliceName = $this->getSliceName($name);

		if ($sliceName->exists()) {
			$values = $sliceName->getValues();
			return $this->getSliceHtml($values['xPercent'], $values['yPercent'], $values['widthPercent'], $values['heightPercent'], $thumbWidth, $link, $pixelMode);
		}
		return false;
	}

	/**
	 * Is this an old revision?
	 *
	 * @access	public
	 * @return	boolean	Is an old revision.
	 */
	public function isRevision() {
		return (bool) $this->data['spritesheet_rev_id'];
	}

	/**
	 * Get the previous revision for this spritesheet.
	 *
	 * @access	public
	 * @return	mixed	SpriteSheet or false for no previous revision.
	 */
	public function getPreviousRevision() {
		$where['spritesheet_id'] = $this->getId();
		if ($this->isRevision()) {
			$where[] = "spritesheet_rev_id < ".intval($this->data['spritesheet_rev_id']);
		}

		$oldResult = $this->DB->select(
			['spritesheet_rev'],
			['*'],
			$where,
			__METHOD__,
			[
				'ORDER BY'	=> 'spritesheet_rev_id DESC'
			]
		);

		$oldRow = $oldResult->fetchRow();

		$spriteSheet = false;
		if (is_array($oldRow)) {
			$spriteSheet = SpriteSheet::newFromRow($oldRow);
		}

		return $spriteSheet;
	}

	/**
	 * Get a previous revision for this spritename by its revision ID.
	 *
	 * @access	public
	 * @param	integer	Revision ID
	 * @return	mixed	SpriteSheet or false for no previous revision.
	 */
	public function getRevisionById($revisionId) {
		$revResult = $this->DB->select(
			['spritesheet_rev'],
			['*'],
			[
				'spritesheet_rev_id'	=> intval($revisionId),
				'spritesheet_id'		=> $this->getId()
			],
			__METHOD__
		);

		$revRow = $revResult->fetchRow();

		$spriteSheet = false;
		if (is_array($revRow)) {
			$spriteSheet = SpriteSheet::newFromRow($revRow);
		}

		return $spriteSheet;
	}

	/**
	 * Return the old revision ID if this is an old revision.
	 *
	 * @access	public
	 * @return	mixed	Revision ID or false if this is the current revision.
	 */
	public function getRevisionId() {
		if ($this->isRevision()) {
			return $this->data['spritesheet_rev_id'];
		}
		return false;
	}

	/**
	 * Return the revision ID that comes after the supplied revision ID.
	 *
	 * @access	public
	 * @param	integer	Old ID
	 * @return	mixed	Next revision ID or false if it is not an old revision.
	 */
	static public function getNextRevisionId($revisionId) {
		$DB = wfGetDB(DB_MASTER);

		$revResult = $DB->select(
			['spritesheet_rev'],
			['*'],
			["spritesheet_rev_id > ".intval($revisionId)],
			__METHOD__,
			[
				'ORDER BY'	=> 'spritesheet_rev_id ASC'
			]
		);

		$revRow = $revResult->fetchRow();
		if (is_array($revRow)) {
			return intval($revRow['spritesheet_rev_id']);
		}
		return false;
	}

	/**
	 * Return a set of revision links(diff, revert) for the change log.
	 *
	 * @access	public
	 * @param	integer	[Optional] The previous ID to use.  This will automatically populate if not provided.
	 * @return	array	Links for performing actions against revisions.
	 */
	public function getRevisionLinks($previousId = false) {
		global $wgUser;

		if ($previousId === false) {
			$previousRevision = $this->getPreviousRevision();
			$arguments['sheetPreviousId'] = $previousRevision->getId();
		} else {
			$arguments['sheetPreviousId'] = intval($previousId);
		}

		$links['diff'] = Linker::link($this->getTitle(), wfMessage('diff')->escaped(), [], array_merge($arguments, ['sheetAction' => 'diff']));

		if ($wgUser->isAllowed('spritesheet_rollback')) {
			$links['rollback'] = Linker::link($this->getTitle(), wfMessage('rollbacklink')->escaped(), [], array_merge($arguments, ['sheetAction' => 'rollback']));
		}

		return $links;
	}

	/**
	 * Return if this is a local SpriteSheet.
	 *
	 * @access	public
	 * @return	boolean	True
	 */
	public function isLocal() {
		return true;
	}

	/**
	 * Get a URL from unsanitized input.  Could be a Title value or a proper URL.
	 *
	 * @access	private
	 * @return	mixed	Full URL or false on error.
	 */
	private function getUrlFromText($text) {
		if (empty($text)) {
			return false;
		}

		if (filter_var($text, FILTER_VALIDATE_URL)) {
			//Yep, it's a URL.
			return $text;
		}
		$title = Title::newFromText($text);
		if ($title instanceOf Title) {
			return $title->getFullURL();
		}
		return false;
	}
}

class SpriteSheetRemote extends SpriteSheet {
	/**
	 * Last API Error Message
	 *
	 * @var		string
	 */
	private $lastApiErrorMessage = false;

	/**
	 * Image storage from the remote repository.
	 *
	 * @var		object
	 */
	private $image = null;

	/**
	 * Create a new instance of this class from a Title object.
	 *
	 * @access	public
	 * @param	object	Title
	 * @param	boolean	[Optional] Stash the object to quick retrieval.
	 * @return	mixed	SpriteSheet or false on error.
	 */
	static public function newFromTitle(Title $title, $useMemoryCache = false) {
		if ($title->getNamespace() != NS_FILE || !$title->getDBkey()) {
			return false;
		}

		if ($useMemoryCache && isset(self::$spriteSheets[$title->getDBkey()])) {
			return self::$spriteSheets[$title->getDBkey()];
		}

		$spriteSheet = new self();
		$spriteSheet->setTitle($title);

		$spriteSheet->newFrom = 'remote';

		$success = $spriteSheet->load();

		if ($success) {
			self::$spriteSheets[$title->getDBkey()] = $spriteSheet;

			return $spriteSheet;
		}
		return false;
	}

	/**
	 * Load from the remote API.
	 *
	 * @access	public
	 * @param	array	[Unused]
	 * @return	boolean	Success
	 */
	public function load($row = null) {
		if (!$this->isLoaded) {
			$this->image = wfFindFile($this->getTitle());

			if ($this->image !== false && $this->image->exists() && !$this->image->isLocal() && $this->image->getRepo() instanceof ForeignAPIRepo) {
				$query = [
					'action'	=> 'spritesheet',
					'do'		=> 'getSpriteSheet',
					'title'		=> $this->getTitle()->getDBkey(), //DO NOT MOVE THIS TO THE BOTTOM.  NEVER.  Mediawiki has a dumb as fuck bug called "class IEUrlExtension" which will block all requests if the file name is at the end of the parameter list.
					'format'	=> 'json'
				];

				//Make sure to change this cache piece back to 300 seconds once this extension is out of development.
				$data = $this->image->getRepo()->httpGetCached('SpriteSheet', $query, 0);

				if ($data) {
					$spriteData = FormatJson::decode($data, true);
					if ($spriteData['success'] === true && is_array($spriteData['data']) && $spriteData['data']['title'] == $this->getTitle()->getDBkey()) {
						$this->setColumns($spriteData['data']['columns']);
						$this->setRows($spriteData['data']['rows']);
						$this->setInset($spriteData['data']['inset']);
						$this->setTitle($this->getTitle());

						$this->isLoaded = true;

						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Return the last error message from the remote API if produced.
	 *
	 * @access	public
	 * @return	mixed	String error message or false if none has been set.
	 */
	public function getLastApiErrorMessage() {
		return $this->lastApiErrorMessage;
	}

	/**
	 * Dummy function to prevent attempts to save the remote SpriteSheet locally.
	 *
	 * @access	public
	 * @return	boolean	Success
	 */
	public function save() {
		return true;
	}

	/**
	 * Return if this is a local SpriteSheet.
	 *
	 * @access	public
	 * @return	boolean	False
	 */
	public function isLocal() {
		return false;
	}

	/**
	 * Return all named sprites/slices for thie sprite sheet.
	 *
	 * @access	public
	 * @return	array	Named Sprite Cache
	 */
	public function getAllSpriteNames() {
		if ($this->image !== false && $this->image->exists() && !$this->image->isLocal()) {
			$query = [
				'action'	=> 'spritesheet',
				'do'		=> 'getAllSpriteNames',
				'title'		=> $this->getTitle()->getDBkey(), //DO NOT MOVE THIS TO THE BOTTOM.  NEVER.  Mediawiki has a dumb as fuck bug called "class IEUrlExtension" which will block all requests if the file name is at the end of the parameter list.
				'format'	=> 'json'
			];

			//Make sure to change this cache piece back to 300 seconds once this extension is out of development.
			$data = $this->image->getRepo()->httpGetCached('SpriteSheet', $query, 0);
			return;
			if ($data) {
				$spriteData = FormatJson::decode($data, true);
				if ($spriteData['success'] === true && is_array($spriteData['data']) && $spriteData['data']['title'] == $this->getTitle()->getDBkey()) {
					$this->setColumns($spriteData['data']['columns']);
					$this->setRows($spriteData['data']['rows']);
					$this->setInset($spriteData['data']['inset']);
					$this->setTitle($this->getTitle());

					$this->isLoaded = true;

					return true;
				}
			}
		}

		while ($row = $result->fetchRow()) {
			$spriteName = SpriteName::newFromRow($row, $this);
			if ($spriteName->exists()) {
				$this->spriteNameCache[$spriteName->getName()] = $spriteName;
			}
		}
		return $this->spriteNameCache;
	}
}