<?php

namespace jsoner\filter;

interface Filter
{
	public static function doFilter( $array, $params );
}
