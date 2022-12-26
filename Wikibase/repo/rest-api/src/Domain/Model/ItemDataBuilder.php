<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

use LogicException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class ItemDataBuilder {

	private ItemId $id;
	private ?string $type = null;
	private ?TermList $labels = null;
	private ?TermList $descriptions = null;
	private ?AliasGroupList $aliases = null;
	private ?StatementList $statements = null;
	private ?SiteLinkList $siteLinks = null;
	private array $requestedFields;

	public function __construct( ItemId $id, array $requestedFields ) {
		$this->id = $id;
		$this->requestedFields = $requestedFields;
	}

	public function setType( string $type ): self {
		$this->checkRequested( ItemData::FIELD_TYPE );
		$this->type = $type;

		return $this;
	}

	public function setLabels( TermList $labels ): self {
		$this->checkRequested( ItemData::FIELD_LABELS );
		$this->labels = $labels;

		return $this;
	}

	public function setDescriptions( TermList $descriptions ): self {
		$this->checkRequested( ItemData::FIELD_DESCRIPTIONS );
		$this->descriptions = $descriptions;

		return $this;
	}

	public function setAliases( AliasGroupList $aliases ): self {
		$this->checkRequested( ItemData::FIELD_ALIASES );
		$this->aliases = $aliases;

		return $this;
	}

	public function setStatements( StatementList $statements ): self {
		$this->checkRequested( ItemData::FIELD_STATEMENTS );
		$this->statements = $statements;

		return $this;
	}

	public function setSiteLinks( SiteLinkList $siteLinks ): self {
		$this->checkRequested( ItemData::FIELD_SITELINKS );
		$this->siteLinks = $siteLinks;

		return $this;
	}

	public function build(): ItemData {
		return new ItemData(
			$this->id,
			$this->requestedFields,
			$this->type,
			$this->labels,
			$this->descriptions,
			$this->aliases,
			$this->statements,
			$this->siteLinks
		);
	}

	private function checkRequested( string $field ): void {
		if ( !in_array( $field, $this->requestedFields ) ) {
			throw new LogicException( "cannot set unrequested ItemData field '$field'" );
		}
	}

}
