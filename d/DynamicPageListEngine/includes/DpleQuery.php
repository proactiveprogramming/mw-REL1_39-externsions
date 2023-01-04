<?php

/**
 * @file
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 *
 * @author [RV1971](https://www.mediawiki.org/wiki/User:RV1971)
 */

/**
 * Database query specification and result.
 *
 * This class is simply a wrapper for the parameters and the result of
 * DatabaseBase::select().
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 */
class DpleQuery implements Countable {

	/** DatabaseBase object. */
	private $dbr_;

	/** Tables for DatabaseBase::select(). */
	private $tables_;

	/** Fields for DatabaseBase::select(). */
	private $vars_;

	/** WHERE conditions for DatabaseBase::select(). */
	private $conds_;

	/** Options for DatabaseBase::select(). */
	private $options_;

	/** Join conditions for DatabaseBase::select(). */
	private $joinConds_;

	/** ResultWrapper containing the query result. */
	private $result_;

	/**
	 * @param array|string|null $tables
	 * @param array|string|null $vars
	 * @param array|string|null $conds
	 * @param array|string|null $options
	 * @param array|string|null $joinConds
	 */
	public function __construct(
		$tables = null,
		$vars = null,
		$conds = null,
		$options = null,
		$joinConds = null
	) {
		/** Get a database object. */
		$this->dbr_ = wfGetDB( DB_REPLICA );

		/** Initialize the class members with the arguments. */

		$this->tables_ = (array)$tables;
		$this->vars_ = (array)$vars;
		$this->conds_ = (array)$conds;
		$this->options_ = (array)$options;
		$this->joinConds_ = (array)$joinConds;
	}

	/**
	 * @return int Number of result rows, or 0 if the query has not
	 * yet been executed.
	 */
	public function count() {
		if ( isset( $this->result_ ) ) {
			return $this->result_->numRows();
		} else {
			return 0;
		}
	}

	public function getDbr() {
		return $this->dbr_;
	}

	public function getTables() {
		return $this->tables_;
	}

	public function getVars() {
		return $this->vars_;
	}

	public function getConds() {
		return $this->conds_;
	}

	public function getOptions() {
		return $this->options_;
	}

	public function getJoinConds() {
		return $this->joinConds_;
	}

	public function getResult() {
		return $this->result_;
	}

	/**
	 * @param string|array $tables Additional tables to query.
	 */
	public function addTables( $tables ) {
		$this->tables_ = array_merge( $this->tables_, (array)$tables );
	}

	/**
	 * @param string|array $vars Additional fields to query.
	 */
	public function addVars( $vars ) {
		$this->vars_ = array_merge( $this->vars_, (array)$vars );
	}

	/**
	 * @param string|array $conds Additional WHERE conditions.
	 */
	public function addConds( $conds ) {
		$this->conds_ = array_merge( $this->conds_, (array)$conds );
	}

	/**
	 * @param string $key Option key. To set options which do not have
	 * keys (such as DISTINCT), pass the option as $key and do not
	 * pass $value.
	 *
	 * @param mixed $value Option value.
	 */
	public function setOption( $key, $value = null ) {
		if ( isset( $value ) ) {
			$this->options_[$key] = $value;
		} else {
			$this->options_[] = $key;
		}
	}

	/**
	 * @param string $table Table name or alias.
	 *
	 * @param string $joinType `INNER JOIN`, `LEFT OUTER JOIN` etc.
	 *
	 * @param string|array $conds Join conditions.
	 */
	public function addJoinCond( $table, $joinType, $conds ) {
		$this->joinConds_[$table] = [ $joinType, $conds ];
	}

	/**
	 * Execute the query and store the result in $result_.
	 *
	 * @param string $fname
	 * @return ResultWrapper $result_.
	 */
	public function execute( $fname = __METHOD__ ) {
		$this->result_ = $this->dbr_->select(
			$this->tables_, $this->vars_, $this->conds_, $fname,
			$this->options_, $this->joinConds_ );
		return $this->result_;
	}
}
