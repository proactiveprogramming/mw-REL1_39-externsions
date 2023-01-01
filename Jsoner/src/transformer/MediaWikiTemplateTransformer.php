<?php

namespace jsoner\transformer;

use jsoner\Helper;

class MediaWikiTemplateTransformer extends AbstractTransformer
{

	public function transformZero($options)

	{
		$emptyJsonObject = "{}";
		$this->transformMultiple( $emptyJsonObject, $options );
	}

	public function transformOne( $json, $options )

	{
		return $this->transformMultiple( $json);
	}


	public function transformMultiple( $json , $options )
	{
		foreach ($json as $key)
		{
			$value .= '{{'. $options ;
			$value .= $this->QueryKeys( $key );
			$value .= '}}';
		}
		return $value;
	}

	public function QueryKeys( $item )
	{
		$header = "";
		foreach ( $item as $key => $value)
		{
			$header .= '|' . $key ;
			$header .= '='. $value ;
		}
		return $header;
	}

}
