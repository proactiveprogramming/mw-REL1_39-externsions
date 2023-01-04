<?php

namespace jsoner;

use Exception;
use jsoner\exceptions\FilterException;
use jsoner\exceptions\HttpUriFormatException;
use jsoner\exceptions\CurlException;
use jsoner\exceptions\TransformerException;
use jsoner\exceptions\ParserException;
use jsoner\filter\Filter;

class Jsoner
{
	/**
	 * @var \jsoner\Config The configuration for Jsoner (global)
	 */
	private $config;

	/**
	 * @var array User provided options in the #jsoner call (per request)
	 */
	private $options;

	/**
	 * Jsoner constructor.
	 * @param \Config $mwConfig Configuration for Jsoner in a MediaWiki data structure.
	 * @param array $options
	 */
	public function __construct( $mwConfig, $options) {
		$this->config = new Config( [
			"BaseUrl" => $mwConfig->get( "BaseUrl" ),
			"User" => $mwConfig->get( "User" ),
			"Pass" => $mwConfig->get( "Pass" ),
			"Parser-ErrorKey" => '_error',
			"SubSelectKeysTryOrder" => ["_title", 'id'], // TODO: Also make configurable?

		] );
		$this->options = $options;
	}

	/**
	 * Here be the plumbing.
	 * @return string
	 */
	public function run(&$requestCache) {
		$url = $this->options['url'];

		// Autoload the composer dependencies, since Mediawiki doesen't do it.
		self::doAutoload();

		$transformerRegistry = new TransformerRegistry( $this->config );
		# $filterRegistry = new FilterRegistry($this->config);

		try {
			// Resolve and cache result for the request (keyed by url)
			$json = null;
			if (array_key_exists($url, $requestCache)) {
				$json = $requestCache[$url];
			} else {
				$resolver = new Resolver( $this->config, $this->options['url'] );
				$json = $resolver->resolve();
				$requestCache[$url] = $json;
			}

			// Parse
			$parser = new Parser( $this->config );
			$json = $parser->parse( $json );

			// Filter
			# $filterKeys = self::getFiltersFromOptions( $this->options );
			# $filters = $filterRegistry->getFiltersByKeys($filterKeys);

			// Resolve the user specified filters and filter params
			$filters_with_params = self::mapUserParametersToFiltersWithParams( $this->options );

			// Filter
			$json = self::applyFilters( $json, $filters_with_params );

			// Transform
			$transformerKey = self::getTransformerKeyFromOptions( $this->options );
			$transformer = $transformerRegistry->getTransformerByKey( $transformerKey );

			return $transformer->transform( $json, $this->options[$transformerKey] );
		} catch ( CurlException $ce ) {
			return Helper::errorMessage( $ce->getMessage() );
		} catch ( ParserException $pe ) {
			return Helper::errorMessage( $pe->getMessage() );
		} catch ( HttpUriFormatException $hufe ) {
			return Helper::errorMessage( $hufe->getMessage() );
		} catch ( TransformerException $nste ) {
			return Helper::errorMessage( $nste->getMessage() );
		} catch ( FilterException $fe ) {
			return Helper::errorMessage( $fe->getMessage() );
		} catch ( \Exception $catchAll ) {
			return Helper::errorMessage( "Unexpected error: " . $catchAll->getMessage() );
		}
	}

	# ##########################################################################
	# Filter ###################################################################

	private static function mapUserParametersToFiltersWithParams( $options ) {
		$filterMap = [
			'f-SelectSubtree' => ['SelectSubtreeFilter', 1], // 1 Argument
			'f-SelectKeys' => ['SelectKeysFilter', -1],      // Varargs
			'f-RemoveKeys' => ['RemoveKeysFilter', -1],      // Varargs
			'f-Reduce' => ['ReduceFilter', 2],               // 2 Arguments
			'f-SelectRecord' => ['SelectRecordFilter', 1]    // 1 Arguments
		];

		$filters = [];
		foreach ( $options as $filterTag => $filterParams ) {

			// Unknown filter
			if ( !array_key_exists( $filterTag, $filterMap ) ) {
				continue;
			}

			// Empty filter args
			if ( empty( trim( $filterParams ) ) ) {
				continue;
			}

			$filterName = $filterMap[$filterTag][0];
			$filterArgc = $filterMap[$filterTag][1];

			$filters[$filterName] = self::parseFilterParams( $filterParams, $filterArgc );
		}
		return $filters;
	}

	/**
	 * @param string $filterParams
	 * @param integer $filterArgc
	 * @return array An array
	 */
	private static function parseFilterParams( $filterParams, $filterArgc ) {
		if ( $filterArgc === 0 ) {
			return null;
		}

		if ( $filterArgc === 1 ) {
			// Single parameter only
			return $filterParams;
		}

		return explode( ',', $filterParams );
	}

	/**
	 * @param $json
	 * @param Filter[] $filters
	 * @return mixed
	 */
	private static function applyFilters( $json, $filters ) {
		foreach ( $filters as $filter_class => $parameter_array ) {
			$function = '\\jsoner\\filter\\' . $filter_class . '::doFilter';

			$json = call_user_func( $function, $json, $parameter_array );
		}
		return $json;
	}

	# ##########################################################################
	# Transformer ##############################################################

	private static function getTransformerKeyFromOptions( $options ) {
		$foundTransformers = [];
		foreach ( $options as $key => $val ) {
			if ( strpos( $key, 't-' ) === 0 ) {
				$foundTransformers[] = $key;
			}
		}

		$numFoundTransformers = count( $foundTransformers );
		if ( $numFoundTransformers == 1 ) {
			return $foundTransformers[0];
		}

		$msg = "Must provide exactly one transformer ($numFoundTransformers provided)";
		( $numFoundTransformers !== 0 ) ? $msg .= ': ' . implode( ', ', $foundTransformers ): $msg .= '!';
		throw new TransformerException( $msg );
	}

	# ##########################################################################
	# Misc #####################################################################

	private static function doAutoload() {
		if ( file_exists( __DIR__ . '/../vendor/autoload.php' ) ) {
			require_once __DIR__ . '/../vendor/autoload.php';
		}
	}
}
