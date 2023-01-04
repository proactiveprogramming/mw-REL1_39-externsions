<?php

use Wikimedia\ParamValidator\ParamValidator;

class ApiQueryAbLinkStatsProp extends ApiQueryBase {

	/**
	 * ApiQueryAbLinkStatsProp constructor.
	 * @param ApiQuery $query
	 * @param $moduleName
	 */
	public function __construct( ApiQuery $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'abls' );
	}

	/**
	 * @inheritDoc
	 * @throws ApiUsageException
	 */
	public function execute() {
		$pages = $this->getPageSet()->getGoodPages();
		$result = $this->getResult();
		$params = $this->extractRequestParams();

		foreach ( $pages as $page ) {
			$id = $page->getArticleID();
			if ( $id == 0 ) continue;

			$result->addValue(
				[ 'query', 'pages', $id ],
				$this->getModuleName(),
				AdvancedBacklinksUtils::GetLinkStats( $page, $params['directonly'], $params['contentonly'],
					$params['redirects'], $params['throughredirects'] )
			);
		}
	}

	public function getAllowedParams() {
		return [
			'directonly' => [
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_DEFAULT => false
			],
			'contentonly' => [
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_DEFAULT => false
			],
			'redirects' => [
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_DEFAULT => false
			],
			'throughredirects' => [
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_DEFAULT => false
			]
		];
	}

	/**
	 * @param array $params
	 * @return string
	 */
	public function getCacheMode( $params ) {
		return 'public';
	}
}
