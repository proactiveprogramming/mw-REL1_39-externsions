<?php

class SparqlResultFormatBase {

	protected $name = "";
	protected $description = "";
	protected $params = array();
	protected $extraOpts = array();
	protected $queryStructure = "";
	protected $complexTypes = array();

	public function getName() {
		return $this->name;
	}

	public function getDescription() {
		return $this->description;
	}

	public function getParams() {
		return $this->params;
	}
	
	public function getComplexTypes() {
		return $this->complexTypes;
	}

	public function getExtraOptions() {
		return $this->extraOpts;
	}

	public function getQueryStructure() {
		return $this->queryStructure;
	}

	protected function getParameterValue( $options, $paramName, $defaultValue, $asBoolean = false ) {
		$paramName = trim( $paramName );
		if ( !isset( $this->params[$paramName] ) ) {
			throw new Exception( "Param $paramName is not defined in params definition. " );
		} else {
			$paramDefinition = $this->params[$paramName];
		}

		if ( isset( $options[$paramName] ) ) {
			if (is_array($options[$paramName])){
				return array_map("html_entity_decode",$options[$paramName]);
			}else {
				if ($asBoolean){
					//ritorno l'elemento come elemento booleano
					return filter_var($options[$paramName], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
				} else {
					return html_entity_decode( $options[$paramName], ENT_QUOTES );
				}
			}
		} else {
			// parametro non passato
			// era obbligatorio
			$mandatory = isset( $paramDefinition["mandatory"] ) ? $paramDefinition["mandatory"] : false;
			if ( $mandatory ) {
				throw new Exception( "Param $paramName must be specified" );
			} else {
				return $defaultValue;
			}
		}
	}

	protected function getSparqlEndpointByName( $endpointName ) {
		global $wgSparqlEndpointDefinition;
		if ( isset( $wgSparqlEndpointDefinition[$endpointName] ) ) {
			return $wgSparqlEndpointDefinition[$endpointName];
		} else {
			throw new Exception( "No endpoint '$endpointName' found in LocalSettings.php" );
		}
	}

	protected function getSparqlEndpointBasicAuthString( $endpointData ) {
		$fieldName = 'basicAuth';
		if ( isset( $endpointData[$fieldName] ) ) {
			$basic = $endpointData[$fieldName];
			$username = isset( $basic['user'] ) ? $basic['user'] : '';
			$password = isset( $basic['password'] ) ? $basic['password'] : '';
			return base64_encode( "$username:$password" );
		} else {
			return '';
		}
	}
	
	protected function getSparqlProxyEndpoint(  ) {
		global $wgScriptPath;
		return "$wgScriptPath/extensions/SparqlResultFormat/api/query/sparql.php";
	}
	
	protected function getDownloadImageEndpoint(){
		global $wgScriptPath;
		return "$wgScriptPath/extensions/SparqlResultFormat/api/download/image.php";
	}
	

	protected function checkExtraOptions( $extra ) {
		if ( is_array( $extra ) ) {
			foreach ( $extra as $value ) {
				$this->checkExtraOptionName( $value );
			}
		} else {
			$this->checkExtraOptionName( $extra );
		}
	}

	private function checkExtraOptionName( $value ) {
		if ( !empty( $value ) ) {
			$pos = strpos( $value, ":" );
			$prop = substr( $value, 0, $pos );
			if ( !isset( $this->extraOpts[$prop] ) ) {
				throw new Exception( "Extra Option $prop is not declared as a valid option for this format!" );
			}
		}
	}

	protected function jsRegisterFunction( $launch ) {
		$out = "if (!window.sparqlResultFormatsElements){
					window.sparqlResultFormatsElements = [];
				}
				window.sparqlResultFormatsElements.push({config:config,
					start:function(config){
						$launch
					}
				});		
				";
		return $out;
	}

}
