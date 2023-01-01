<?php
namespace MediaWiki\Extensions\LunarFunctions;

use Parser;
use PPFrame;

/**
 * Lunar function handlers
 *
 * @link https://www.mediawiki.org/wiki/Extension:LunarFunctions
 */
class LunarFunctions {
	private static $mTimeCache = [];
	private static $mTimeChars = 0;

	/** ~10 seconds */
	const MAX_TIME_CHARS = 6000;

	/**
	 * Register ParserClearState hook.
	 * We defer this until needed to avoid the loading of the code of this file
	 * when no parser function is actually called.
	 */
	private static function registerClearHook() {
		static $done = false;
		if ( !$done ) {
			global $wgHooks;
			$wgHooks['ParserClearState'][] = function () {
				self::$mTimeChars = 0;
			};
			$done = true;
		}
	}

	/**
	 * @param string $year
	 * @param string $month
	 * @param string $day
	 * @param string $hour
	 * @return array
	 */
	private static function ConvertToLunar($year, $month, $day, $hour) {
	
	    $Lunar = Calendar::get_instance()->solar2lunar($year, $month, $day, $hour);

		return $Lunar;
	}
	
	private static function sprintfLunar($format, $Lunar) {
		/*
		$Lunar = [
            'lunar_year' => (string) $lunarYear,
            'lunar_month' => sprintf('%02d', $lunarMonth),
            'lunar_day' => sprintf('%02d', $lunarDay),
            'lunar_hour' => $hour,
            'lunar_year_chinese' => $this->toChinaYear($lunarYear),
            'lunar_month_chinese' => ($isLeap ? '闰' : '').$this->toChinaMonth($lunarMonth),
            'lunar_day_chinese' => $this->toChinaDay($lunarDay),
            'lunar_hour_chinese' => $lunarHour,
            'ganzhi_year' => $ganZhiYear,
            'ganzhi_month' => $ganZhiMonth,
            'ganzhi_day' => $ganZhiDay,
            'ganzhi_hour' => $ganZhiHour,
            'wuxing_year' => $this->getWuXing($ganZhiYear),
            'wuxing_month' => $this->getWuXing($ganZhiMonth),
            'wuxing_day' => $this->getWuXing($ganZhiDay),
            'wuxing_hour' => $this->getWuXing($ganZhiHour),
            'color_year' => $this->getColor($ganZhiYear),
            'color_month' => $this->getColor($ganZhiMonth),
            'color_day' => $this->getColor($ganZhiDay),
            'color_hour' => $this->getColor($ganZhiHour),
            'animal' => $this->getAnimal($lunarYear, $termIndex),
            'term' => $term,
            'is_leap' => $isLeap,
        ];
		*/		
		$result = '';
		if (isset($Lunar[$format])) {
			$result = $Lunar[$format];
		} else {
			$result = '<strong class="error">' .
						wfMessage( 'lunarfunc_format_error' )->inContentLanguage()->escaped() .
						'</strong>';
		}

		return $result;
	}
	
	  	/**
	 * {{#lunar: format string }}
	 * {{#lunar: format string | date/time object }}
	 *
	 * @link https://www.mediawiki.org/wiki/Extension:LunarFunctions
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array $args
	 * @return string
	 */
	public static function renderLunar( Parser $parser, PPFrame $frame, array $args ) {
		$format = isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
		$date = isset( $args[1] ) ? trim( $frame->expand( $args[1] ) ) : '';
		
		self::registerClearHook();
		
		$invalidTime = false;
		
		if ( $date === '' ) {
			$cacheKey = $parser->getOptions()->getTimestamp();
			$date = date('Y-m-d H:00');
			$ts = strtotime($date);
			$useTTL = true;
		} else {
			$ts = strtotime($date);
			$cacheKey = $date;
			$useTTL = false;
		}
		if ($ts === false) {
			$invalidTime = true;
		} else {
			$date = date("Y-m-d H:00",$ts);
			$year = date('Y',$ts);
			$month = date('n',$ts);
			$day = date('j',$ts);
			$hour = date('H',$ts);
		}

		//快取機制，避免重複運算相同的日期
		if ( isset( self::$mTimeCache[$date][$cacheKey] ) ) {
			$cachedVal = self::$mTimeCache[$date][$cacheKey];
			
			if ( $useTTL && $cachedVal[1] !== null && $frame ) {
				$frame->setTTL( $cachedVal[1] );
			}

			return self::sprintfLunar($format, $cachedVal[0]);
		}


		$ttl = null;
		# format the timestamp and return the result
		if ( $invalidTime ) {
			$result = '<strong class="error">' .
					wfMessage( 'lunarfunc_time_error' )->inContentLanguage()->escaped() .
					'</strong>';
		} else {
			self::$mTimeChars += strlen( $format );
			if ( self::$mTimeChars > self::MAX_TIME_CHARS ) {
				return '<strong class="error">' .
					wfMessage( 'lunarfunc_time_too_long' )->inContentLanguage()->escaped() .
					'</strong>';
			} else {
				if ( $year < 1900 ) { 
					return '<strong class="error">' .
						wfMessage( 'lunarfunc_time_too_small' )->inContentLanguage()->escaped() .
						'</strong>';
				} elseif ( $year <= 2100 ) { // Language can't deal with years after 2100
					$result = self::ConvertToLunar($year, $month, $day, $hour);
				} else {
					return '<strong class="error">' .
						wfMessage( 'lunarfunc_time_too_big' )->inContentLanguage()->escaped() .
						'</strong>';
				}
			}
		}

		self::$mTimeCache[$date][$cacheKey] = [ $result, $ttl ];
		if ( $useTTL && $ttl !== null && $frame ) {
			$frame->setTTL( $ttl );
		}
		return self::sprintfLunar($format, $result);
	}
}
