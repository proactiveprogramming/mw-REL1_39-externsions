<?php
/**
 * WebVideoTranscode provides:
 *  encode keys
 *  encode settings
 *
 * 	extends api to return all the streams
 *  extends video tag output to provide all the available sources
 */

namespace MediaWiki\TimedMediaHandler\WebVideoTranscode;

use CdnCacheUpdate;
use DeferredUpdates;
use Exception;
use File;
use ForeignDBViaLBRepo;
use IForeignRepoWithMWApi;
use MediaWiki\FileBackend\FSFile\TempFSFileFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\TimedMediaHandler\Handlers\FLACHandler\FLACHandler;
use MediaWiki\TimedMediaHandler\Handlers\ID3Handler\ID3Handler;
use MediaWiki\TimedMediaHandler\Handlers\MidiHandler\MidiHandler;
use MediaWiki\TimedMediaHandler\Handlers\Mp3Handler\Mp3Handler;
use MediaWiki\TimedMediaHandler\Handlers\Mp4Handler\Mp4Handler;
use MediaWiki\TimedMediaHandler\Handlers\OggHandler\OggHandler;
use MediaWiki\TimedMediaHandler\Handlers\WAVHandler\WAVHandler;
use MWException;
use TempFSFile;
use Title;
use Wikimedia\Rdbms\IDatabase;

/**
 * Main WebVideoTranscode Class hold some constants and config values
 */
class WebVideoTranscode {
	/** @var array[] Static cache of transcode state per instantiation */
	public static $transcodeState = [];

	/**
	 * Encoding parameters are set via firefogg encode api
	 *
	 * For clarity and compatibility with passing down
	 * client side encode settings at point of upload
	 *
	 * http://firefogg.org/dev/index.html
	 * @var string[][]
	 */
	public static $derivativeSettings = [

		// WebM transcode:
		'160p.webm' => [
			'maxSize' => '288x160',
			'videoBitrate' => '128',
			'crf' => '10',
			'audioQuality' => '-1',
			'twopass' => 'true',
			'keyframeInterval' => '240',
			'bufDelay' => '256',
			'videoCodec' => 'vp8',
			'slices' => '2',
			'type' => 'video/webm; codecs="vp8, vorbis"',
		],
		'240p.webm' => [
			'maxSize' => '426x240',
			'videoBitrate' => '256',
			'crf' => '10',
			'audioQuality' => '1',
			'twopass' => 'true',
			'keyframeInterval' => '240',
			'bufDelay' => '256',
			'videoCodec' => 'vp8',
			'slices' => '2',
			'type' => 'video/webm; codecs="vp8, vorbis"',
		],
		'360p.webm' => [
			'maxSize' => '640x360',
			'videoBitrate' => '512',
			'crf' => '10',
			'audioQuality' => '1',
			'twopass' => 'true',
			'keyframeInterval' => '240',
			'bufDelay' => '256',
			'videoCodec' => 'vp8',
			'slices' => '2',
			'type' => 'video/webm; codecs="vp8, vorbis"',
		],
		'480p.webm' => [
			'maxSize' => '854x480',
			'videoBitrate' => '1024',
			'crf' => '10',
			'audioQuality' => '2',
			'twopass' => 'true',
			'keyframeInterval' => '240',
			'bufDelay' => '256',
			'videoCodec' => 'vp8',
			'slices' => '4',
			'type' => 'video/webm; codecs="vp8, vorbis"',
		],
		'720p.webm' => [
			'maxSize' => '1280x720',
			'videoBitrate' => '2048',
			'crf' => '10',
			'audioQuality' => '3',
			'twopass' => 'true',
			'keyframeInterval' => '240',
			'videoCodec' => 'vp8',
			'slices' => '4',
			'speed' => '1',
			'type' => 'video/webm; codecs="vp8, vorbis"',
		],
		'1080p.webm' => [
			'maxSize' => '1920x1080',
			'videoBitrate' => '4096',
			'crf' => '10',
			'audioQuality' => '3',
			'twopass' => 'true',
			'keyframeInterval' => '240',
			'videoCodec' => 'vp8',
			'slices' => '4',
			'speed' => '1',
			'type' => 'video/webm; codecs="vp8, vorbis"',
		],
		'1440p.webm' => [
			'maxSize' => '2560x1440',
			'videoBitrate' => '8192',
			'crf' => '10',
			'audioQuality' => '3',
			'twopass' => 'true',
			'keyframeInterval' => '240',
			'videoCodec' => 'vp8',
			'slices' => '8',
			'speed' => '2',
			'type' => 'video/webm; codecs="vp8, vorbis"',
		],
		'2160p.webm' => [
			'maxSize' => '3840x2160',
			'videoBitrate' => '16384',
			'crf' => '10',
			'audioQuality' => '3',
			'twopass' => 'true',
			'keyframeInterval' => '240',
			'videoCodec' => 'vp8',
			'slices' => '8',
			'speed' => '2',
			'type' => 'video/webm; codecs="vp8, vorbis"',
		],

		// WebM VP9 transcode:
		//
		// These configurations are meant to balance
		// * quality (try to maintain quality until size gets too large)
		// * size (aim for smaller files when possible)
		// * speed (use modest "speed" setting to gain speed at a little bandwidth/quality cost)
		//
		// A large bitrate target is used to allow preserving good quality for highly
		// detailed sources and those with high frame rates and motion, which were not
		// served well under the previous VP8 settings. The qmin is set close to the
		// crf constrained quality target to keep from adding any extra bits when not
		// needed; we're always transcoding something with its own compression artifacts
		// and there's no need to reproduce every last bit.
		//
		// This usually results in files at or significantly below target when there's
		// relatively little detail/motion, and files bigger towards the inflated target
		// (set around 4x what we'd really want as a target) are allowed to better handle
		// those high-frame-rate or high-motion/high-detail files.
		//
		// Use of two-pass encoding increases runtime by 2/3 but significantly increases
		// quality through enabling auto alt reference frames. Use of 'speed' param at 2
		// instead of 0 or 2 makes things a little faster at very slight cost of bandwidth.
		//
		// Encoding speed is greatly affected by threading settings; HD videos can use up to
		// 8 threads with a suitable ffmpeg/libvpx and $wgFFmpegVP9RowMT enabled ("row-mt").
		// Ultra-HD can use up to 16 threads. Be sure to set $wgFFmpegThreads to a suitable
		// maximum values!
		//
		'120p.vp9.webm' => [
			'maxSize' => '213x120',
			// target 60 x 2
			'videoBitrate' => '120',
			'crf' => '35',
			'qmin' => '8',
			'twopass' => 'true',
			'altref' => 'true',
			'keyframeInterval' => '240',
			'speed' => '2',
			'videoCodec' => 'vp9',
			'audioCodec' => 'opus',
			'audioBitrate' => '96',
			'samplerate' => '48000',
			'type' => 'video/webm; codecs="vp9, opus"',
		],
		'180p.vp9.webm' => [
			'maxSize' => '320x180',
			// target 100 x 2
			'videoBitrate' => '200',
			'crf' => '35',
			'qmin' => '9',
			'twopass' => 'true',
			'altref' => 'true',
			'keyframeInterval' => '240',
			'speed' => '2',
			'videoCodec' => 'vp9',
			'audioCodec' => 'opus',
			'audioBitrate' => '96',
			'samplerate' => '48000',
			'type' => 'video/webm; codecs="vp9, opus"',
		],
		'240p.vp9.webm' => [
			'maxSize' => '426x240',
			// target 160 x 2
			'videoBitrate' => '320',
			'crf' => '35',
			'qmin' => '11',
			'twopass' => 'true',
			'altref' => 'true',
			'keyframeInterval' => '240',
			'speed' => '2',
			'videoCodec' => 'vp9',
			'audioCodec' => 'opus',
			'samplerate' => '48000',
			'audioBitrate' => '96',
			'type' => 'video/webm; codecs="vp9, opus"',
		],
		'360p.vp9.webm' => [
			'maxSize' => '640x360',
			// target 320 x 2
			'videoBitrate' => '640',
			'crf' => '35',
			'qmin' => '12',
			'twopass' => 'true',
			'altref' => 'true',
			'keyframeInterval' => '240',
			'speed' => '2',
			'videoCodec' => 'vp9',
			'audioCodec' => 'opus',
			'audioBitrate' => '96',
			'samplerate' => '48000',
			'tileColumns' => '1',
			'type' => 'video/webm; codecs="vp9, opus"',
			],
		'480p.vp9.webm' => [
			'maxSize' => '854x480',
			// target 640 x 2
			'videoBitrate' => '1280',
			'crf' => '33',
			'qmin' => '10',
			'twopass' => 'true',
			'altref' => 'true',
			'keyframeInterval' => '240',
			'speed' => '2',
			'videoCodec' => 'vp9',
			'audioCodec' => 'opus',
			'audioBitrate' => '96',
			'samplerate' => '48000',
			'tileColumns' => '1',
			'type' => 'video/webm; codecs="vp9, opus"',
		],
		'720p.vp9.webm' => [
			'maxSize' => '1280x720',
			// target 1280 x 2
			'videoBitrate' => '2560',
			'crf' => '32',
			'qmin' => '10',
			'twopass' => 'true',
			'altref' => 'true',
			'keyframeInterval' => '240',
			'speed' => '2',
			'videoCodec' => 'vp9',
			'audioCodec' => 'opus',
			'audioBitrate' => '96',
			'samplerate' => '48000',
			'tileColumns' => '2',
			'type' => 'video/webm; codecs="vp9, opus"',
		],
		'1080p.vp9.webm' => [
			'maxSize' => '1920x1080',
			// target 2560 x 2
			'videoBitrate' => '5120',
			'crf' => '31',
			'qmin' => '9',
			'twopass' => 'true',
			'altref' => 'true',
			'keyframeInterval' => '240',
			'speed' => '2',
			'videoCodec' => 'vp9',
			'audioCodec' => 'opus',
			'audioBitrate' => '96',
			'samplerate' => '48000',
			'tileColumns' => '4',
			'type' => 'video/webm; codecs="vp9, opus"',
		],
		'1440p.vp9.webm' => [
			'maxSize' => '2560x1440',
			// target 5120 x 2
			'videoBitrate' => '10240',
			'crf' => '24',
			'qmin' => '8',
			'twopass' => 'true',
			'altref' => 'true',
			'keyframeInterval' => '240',
			'speed' => '2',
			'videoCodec' => 'vp9',
			'audioCodec' => 'opus',
			'audioBitrate' => '96',
			'samplerate' => '48000',
			'tileColumns' => '4',
			'type' => 'video/webm; codecs="vp9, opus"',
		],
		'2160p.vp9.webm' => [
			'maxSize' => '3840x2160',
			// target 10240 x 2
			'videoBitrate' => '20480',
			'crf' => '24',
			'qmin' => '8',
			'twopass' => 'true',
			'altref' => 'true',
			'keyframeInterval' => '240',
			'speed' => '2',
			'videoCodec' => 'vp9',
			'audioCodec' => 'opus',
			'audioBitrate' => '96',
			'samplerate' => '48000',
			'tileColumns' => '4',
			'type' => 'video/webm; codecs="vp9, opus"',
		],

		// Loosely defined per PCF guide to mp4 profiles:
		// https://develop.participatoryculture.org/index.php/ConversionMatrix
		// and apple HLS profile guide:
		// https://developer.apple.com/library/ios/#documentation/networkinginternet/conceptual/streamingmediaguide/UsingHTTPLiveStreaming/UsingHTTPLiveStreaming.html#//apple_ref/doc/uid/TP40008332-CH102-DontLinkElementID_24

		'160p.mp4' => [
			'maxSize' => '288x160',
			'videoCodec' => 'h264',
			'videoBitrate' => '160k',
			'audioCodec' => 'aac',
			'channels' => '2',
			'audioBitrate' => '40k',
			'type' => 'video/mp4; codecs="avc1.42E01E, mp4a.40.2"',
		],

		'240p.mp4' => [
			'maxSize' => '426x240',
			'videoCodec' => 'h264',
			'videoBitrate' => '256k',
			'audioCodec' => 'aac',
			'channels' => '2',
			'audioBitrate' => '40k',
			'type' => 'video/mp4; codecs="avc1.42E01E, mp4a.40.2"',
		],

		'320p.mp4' => [
			'maxSize' => '480x320',
			'videoCodec' => 'h264',
			'videoBitrate' => '400k',
			'audioCodec' => 'aac',
			'channels' => '2',
			'audioBitrate' => '40k',
			'type' => 'video/mp4; codecs="avc1.42E01E, mp4a.40.2"',
		],

		'360p.mp4' => [
			'maxSize' => '640x360',
			'videoCodec' => 'h264',
			'videoBitrate' => '512k',
			'audioCodec' => 'aac',
			'channels' => '2',
			'audioBitrate' => '64k',
			'type' => 'video/mp4; codecs="avc1.42E01E, mp4a.40.2"',
		],

		'480p.mp4' => [
			'maxSize' => '854x480',
			'videoCodec' => 'h264',
			'videoBitrate' => '1200k',
			'audioCodec' => 'aac',
			'channels' => '2',
			'audioBitrate' => '64k',
			'type' => 'video/mp4; codecs="avc1.42E01E, mp4a.40.2"',
		],
		'720p.mp4' => [
			'maxSize' => '1280x720',
			'videoCodec' => 'h264',
			'videoBitrate' => '2500k',
			'audioCodec' => 'aac',
			'channels' => '2',
			'audioBitrate' => '128k',
			'type' => 'video/mp4; codecs="avc1.42E01E, mp4a.40.2"',
		],
		'1080p.mp4' => [
			'maxSize' => '1920x1080',
			'videoCodec' => 'h264',
			'videoBitrate' => '5000k',
			'audioCodec' => 'aac',
			'channels' => '2',
			'audioBitrate' => '128k',
			'type' => 'video/mp4; codecs="avc1.42E01E, mp4a.40.2"',
		],
		'1440p.mp4' => [
			'maxSize' => '2560x1440',
			'videoCodec' => 'h264',
			'videoBitrate' => '16384k',
			'audioCodec' => 'aac',
			'channels' => '2',
			'audioBitrate' => '128k',
			'type' => 'video/mp4; codecs="avc1.42E01E, mp4a.40.2"',
		],
		'2160p.mp4' => [
			'maxSize' => '4096x2160',
			'videoCodec' => 'h264',
			'videoBitrate' => '16384k',
			'audioCodec' => 'aac',
			'channels' => '2',
			'audioBitrate' => '128k',
			'type' => 'video/mp4; codecs="avc1.42E01E, mp4a.40.2"',
		],

		// Audio profiles
		'ogg' => [
			'audioCodec' => 'vorbis',
			'audioQuality' => '3',
			'samplerate' => '44100',
			'channels' => '2',
			'noUpscaling' => 'true',
			'novideo' => 'true',
			'type' => 'audio/ogg; codecs="vorbis"',
		],
		'opus' => [
			'audioCodec' => 'opus',
			'audioQuality' => '1',
			'samplerate' => '44100',
			'channels' => '2',
			'noUpscaling' => 'true',
			'novideo' => 'true',
			'type' => 'audio/ogg; codecs="opus"',
		],
		'mp3' => [
			'audioCodec' => 'mp3',
			'audioQuality' => '1',
			'samplerate' => '44100',
			'channels' => '2',
			'noUpscaling' => 'true',
			'novideo' => 'true',
			'type' => 'audio/mpeg',
		],
		'm4a' => [
			'audioCodec' => 'aac',
			'audioQuality' => '1',
			'samplerate' => '44100',
			'channels' => '2',
			'noUpscaling' => 'true',
			'novideo' => 'true',
			'type' => 'audio/mp4; codecs="mp4a.40.5"',
		],
	];

	/**
	 * @param File $file
	 * @param string $transcodeKey
	 * @return string
	 */
	public static function getDerivativeFilePath( $file, $transcodeKey ) {
		return $file->getTranscodedPath( static::getTranscodeFileBaseName( $file, $transcodeKey ) );
	}

	/**
	 * Get the name to use as the base name for the transcode.
	 *
	 * Swift has problems where the url-encoded version of
	 * the path (ie '0/00/filename.ogv/filename.ogv.720p.webm' )
	 * is greater than > 1024 bytes, so shorten in that case.
	 *
	 * Future versions might respect FileRepo::$abbrvThreshold.
	 *
	 * @param File $file
	 * @param string $suffix Optional suffix (e.g. transcode key).
	 * @return string File name, or the string transcode.
	 */
	public static function getTranscodeFileBaseName( $file, $suffix = '' ) {
		$name = $file->getName();
		$length = strlen( urlencode( '0/00/' . $name . '/' . $name . '.' . $suffix ) );
		if ( $length > 1024 ) {
			return 'transcode' . '.' . $suffix;
		}
		return $name . '.' . $suffix;
	}

	/**
	 * Get url for a transcode.
	 *
	 * @param File $file
	 * @param string $suffix Transcode key
	 * @return string
	 */
	public static function getTranscodedUrlForFile( $file, $suffix = '' ) {
		return $file->getTranscodedUrl( static::getTranscodeFileBaseName( $file, $suffix ) );
	}

	/**
	 * Get temp file at target path for video encode
	 *
	 * @param File $file
	 * @param string $transcodeKey
	 *
	 * @return TempFSFile|false at target encode path
	 */
	public static function getTargetEncodeFile( $file, $transcodeKey ) {
		$filePath = static::getDerivativeFilePath( $file, $transcodeKey );
		$ext = strtolower( pathinfo( $filePath, PATHINFO_EXTENSION ) );

		// Create a temp FS file with the same extension
		$tmpFileFactory = new TempFSFileFactory();
		$tmpFile = $tmpFileFactory->newTempFSFile( 'transcode_' . $transcodeKey, $ext );
		if ( !$tmpFile ) {
			return false;
		}
		return $tmpFile;
	}

	/**
	 * Get the max size of the web stream ( constant bitrate )
	 * @return int
	 */
	public static function getMaxSizeWebStream() {
		$maxSize = 0;
		foreach ( static::enabledVideoTranscodes() as $transcodeKey ) {
			if ( isset( static::$derivativeSettings[$transcodeKey]['videoBitrate'] ) ) {
				// @phan-suppress-next-line PhanTypePossiblyInvalidDimOffset
				$currentSize = static::$derivativeSettings[$transcodeKey]['maxSize'];
				if ( $currentSize > $maxSize ) {
					$maxSize = $currentSize;
				}
			}
		}
		return $maxSize;
	}

	/**
	 * Give a rough estimate on file size
	 * Note this is not always accurate.. especially with variable bitrate codecs ;)
	 * @param File $file
	 * @param string $transcodeKey
	 * @suppress PhanTypePossiblyInvalidDimOffset
	 * @return int
	 */
	public static function getProjectedFileSize( $file, $transcodeKey ) {
		$settings = static::$derivativeSettings[$transcodeKey];
		// FIXME broken, as bitrate settings can contain units (64k)
		if ( $settings[ 'videoBitrate' ] && $settings['audioBitrate'] ) {
			return $file->getLength() * 8 * (
				(int)$settings['videoBitrate']
				+
				(int)$settings['audioBitrate']
			);
		}
		// Else just return the size of the source video
		// ( we have no idea how large the actual derivative size will be )

		/** @var ID3Handler $handler */
		$handler = $file->getHandler();
		'@phan-var ID3Handler $handler';
		return $file->getLength() * $handler->getBitrate( $file ) * 8;
	}

	/**
	 * Static function to get the set of video assets
	 * Checks if the file is local or remote and grabs respective sources
	 * @param File &$file
	 * @param array $options
	 * @return array|mixed
	 */
	public static function getSources( &$file, $options = [] ) {
		if ( $file->isLocal() || $file->repo instanceof ForeignDBViaLBRepo ) {
			return static::getLocalSources( $file, $options );
		}

		if ( $file->getRepo() instanceof IForeignRepoWithMWApi ) {
			return static::getRemoteSources( $file, $options );
		}

		return [];
	}

	/**
	 * Grabs sources from the remote repo via ApiQueryVideoInfo.php entry point.
	 *
	 * TODO: This method could use some rethinking. See comments on PS1 of
	 * 	 <https://gerrit.wikimedia.org/r/#/c/117916/>
	 *
	 * Because this works with commons regardless of whether TimedMediaHandler is installed or not
	 * @param File $file The File must belong to a repo that is an instance of IForeignRepoWithMWApi
	 * @param array $options
	 * @return array|mixed
	 */
	public static function getRemoteSources( $file, $options = [] ) {
		$regenerator = static function () use ( $file, $options ) {
			// Setup source attribute options
			$dataPrefix = in_array( 'nodata', $options, true ) ? '' : 'data-';

			wfDebug( "Get Video sources from remote api for " . $file->getName() . "\n" );
			$namespaceInfo = MediaWikiServices::getInstance()->getNamespaceInfo();
			$query = [
				'action' => 'query',
				'prop' => 'videoinfo',
				'viprop' => 'derivatives',
				'titles' => $namespaceInfo->getCanonicalName( NS_FILE ) . ':' . $file->getTitle()->getText()
			];

			/** @var IForeignRepoWithMWApi $repo */
			$repo = $file->getRepo();
			'@phan-var IForeignRepoWithMWApi $repo';
			$data = $repo->fetchImageQuery( $query );

			if ( isset( $data['warnings']['query'] ) &&
				$data['warnings']['query']['*'] === "Unrecognized value for parameter 'prop': videoinfo"
			) {
				// The target wiki doesn't have TimedMediaHandler.
				// Use the normal file repo system single source:
				return [ static::getPrimarySourceAttributes( $file, [ $dataPrefix ] ) ];
			}

			$sources = [];
			// Generate the source list from the data response:
			if ( isset( $data['query']['pages'] ) ) {
				$vidResult = array_shift( $data['query']['pages'] );
				if ( isset( $vidResult['videoinfo'] ) ) {
					$derResult = array_shift( $vidResult['videoinfo'] );
					$derivatives = $derResult['derivatives'];
					foreach ( $derivatives as $derivativeSource ) {
						$sources[] = $derivativeSource;
					}
				}
			}

			return $sources;
		};

		$repoInfo = $file->getRepo()->getInfo();
		$cacheTTL = $repoInfo['descriptionCacheExpiry'] ?? 0;

		if ( $cacheTTL > 0 ) {
			$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
			$sources = $cache->getWithSetCallback(
				$cache->makeKey( 'WebVideoSources-url', $file->getRepoName(), $file->getName() ),
				$cacheTTL,
				$regenerator
			);
		} else {
			$sources = $regenerator();
		}

		return $sources;
	}

	/**
	 * Based on the $wgEnabledTranscodeSet set of enabled derivatives we
	 * return sources that are ready.
	 *
	 * This will not automatically update or queue anything!
	 *
	 * @param File &$file File object
	 * @param array $options Options, a set of options:
	 * 		'nodata' Strips the data- attribute, useful when your output is not html
	 * @return array an associative array of sources suitable for <source> tag output
	 */
	public static function getLocalSources( &$file, $options = [] ) {
		global $wgEnableTranscode;
		$sources = [];

		// Add the original file:
		$sources[] = static::getPrimarySourceAttributes( $file, $options );

		// If $wgEnableTranscode is false don't look for or add other local sources:
		if ( $wgEnableTranscode === false &&
			!( $file->repo instanceof ForeignDBViaLBRepo ) ) {
			return $sources;
		}

		// If an "oldFile" don't look for other sources:
		if ( $file->isOld() ) {
			return $sources;
		}

		/** @var ID3Handler $handler */
		$handler = $file->getHandler();
		'@phan-var ID3Handler $handler';
		// Now Check for derivatives
		if ( $handler->isAudio( $file ) ) {
			$transcodeSet = static::enabledAudioTranscodes();
		} else {
			$transcodeSet = static::enabledVideoTranscodes();
		}
		foreach ( $transcodeSet as $transcodeKey ) {
			if ( static::isTranscodeEnabled( $file, $transcodeKey ) ) {
				// Try and add the source
				static::addSourceIfReady( $file, $sources, $transcodeKey, $options );
			}
		}

		return $sources;
	}

	/**
	 * Get the transcode state for a given filename and transcodeKey
	 *
	 * @param File $file
	 * @param string $transcodeKey
	 * @return bool
	 */
	public static function isTranscodeReady( $file, $transcodeKey ) {
		// Check if we need to populate the transcodeState cache:
		$transcodeState = static::getTranscodeState( $file );

		// If no state is found the cache for this file is false:
		if ( !isset( $transcodeState[ $transcodeKey ] ) ) {
			return false;
		}
		// Else return boolean ready state ( if not null, then ready ):
		return ( $transcodeState[ $transcodeKey ]['time_success'] ) !== null;
	}

	/**
	 * Clear the transcode state cache:
	 * @param string|null $fileName Optional fileName to clear transcode cache for
	 */
	public static function clearTranscodeCache( $fileName = null ) {
		if ( $fileName ) {
			unset( static::$transcodeState[ $fileName ] );
		} else {
			static::$transcodeState = [];
		}
	}

	/**
	 * Populates the transcode table with the current DB state of transcodes
	 * if transcodes are not found in the database their state is set to "false"
	 *
	 * @param File $file File object
	 * @param IDatabase|false $db
	 * @return array[]
	 */
	public static function getTranscodeState( $file, $db = false ) {
		global $wgTranscodeBackgroundTimeLimit;
		$fileName = $file->getName();
		if ( !isset( static::$transcodeState[$fileName] ) ) {
			if ( $db === false ) {
				$db = $file->repo->getReplicaDB();
			}
			// initialize the transcode state array
			static::$transcodeState[ $fileName ] = [];
			$res = $db->select( 'transcode',
				'*',
				[ 'transcode_image_name' => $fileName ],
				__METHOD__,
				[ 'LIMIT' => 100 ]
			);
			$overTimeout = [];
			$over = time() - ( 2 * $wgTranscodeBackgroundTimeLimit );
			// Populate the per transcode state cache
			foreach ( $res as $row ) {
				// strip the out the "transcode_" from keys
				$transcodeState = [];
				foreach ( $row as $k => $v ) {
					$transcodeState[ str_replace( 'transcode_', '', $k ) ] = $v;
				}
				static::$transcodeState[ $fileName ][ $row->transcode_key ] = $transcodeState;
				if ( $row->transcode_time_startwork !== null
					&& wfTimestamp( TS_UNIX, $row->transcode_time_startwork ) < $over
					&& $row->transcode_time_success === null
					&& $row->transcode_time_error === null
				) {
					$overTimeout[] = $row->transcode_key;
				}
			}
			if ( $overTimeout ) {
				$dbw = wfGetDB( DB_PRIMARY );
				$dbw->update(
					'transcode',
					[
						'transcode_time_error' => $dbw->timestamp(),
						'transcode_error' => 'timeout'
					],
					[
						'transcode_image_name' => $fileName,
						'transcode_key' => $overTimeout
					],
					__METHOD__
				);
			}
		}
		$sorted = static::$transcodeState[ $fileName ];
		uksort( $sorted, 'strnatcmp' );
		return $sorted;
	}

	/**
	 * Remove any transcode files and db states associated with a given $file
	 * Note that if you want to see them again, you must re-queue them by calling
	 * startJobQueue() or updateJobQueue().
	 *
	 * also remove the transcode files:
	 * @param File $file File Object
	 * @param string|false $transcodeKey Optional transcode key to remove only this key
	 */
	public static function removeTranscodes( $file, $transcodeKey = false ) {
		// if transcode key is non-false, non-null:
		if ( $transcodeKey ) {
			// only remove the requested $transcodeKey
			$removeKeys = [ $transcodeKey ];
		} else {
			// Remove any existing files ( regardless of their state )
			$res = $file->repo->getPrimaryDB()->select( 'transcode',
				[ 'transcode_key' ],
				[ 'transcode_image_name' => $file->getName() ],
				__METHOD__
			);
			$removeKeys = [];
			foreach ( $res as $transcodeRow ) {
				$removeKeys[] = $transcodeRow->transcode_key;
			}
		}

		// Remove files by key:
		$urlsToPurge = [];
		foreach ( $removeKeys as $tKey ) {
			$urlsToPurge[] = static::getTranscodedUrlForFile( $file, $tKey );
			$filePath = static::getDerivativeFilePath( $file, $tKey );
			if ( $file->repo->fileExists( $filePath ) ) {
				$res = $file->repo->quickPurge( $filePath );
				if ( !$res ) {
					wfDebug( "Could not delete file $filePath\n" );
				}
			}
		}

		$update = new CdnCacheUpdate( $urlsToPurge );
		DeferredUpdates::addUpdate( $update );

		// Build the sql query:
		$dbw = wfGetDB( DB_PRIMARY );
		$deleteWhere = [ 'transcode_image_name' => $file->getName() ];
		// Check if we are removing a specific transcode key
		if ( $transcodeKey !== false ) {
			$deleteWhere['transcode_key'] = $transcodeKey;
		}
		// Remove the db entries
		$dbw->delete( 'transcode', $deleteWhere, __METHOD__ );

		// Purge the cache for pages that include this video:
		$titleObj = $file->getTitle();
		static::invalidatePagesWithFile( $titleObj );

		// Remove from local WebVideoTranscode cache:
		static::clearTranscodeCache( $titleObj->getDBkey() );
	}

	/**
	 * @param Title $titleObj
	 */
	public static function invalidatePagesWithFile( $titleObj ) {
		wfDebug( "WebVideoTranscode:: Invalidate pages that include: " . $titleObj->getDBkey() . "\n" );
		// Purge the main image page:
		$titleObj->invalidateCache();

		// TODO if the video is used in over 500 pages add to 'job queue'
		// TODO interwiki invalidation ?
		$limit = 500;
		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			[ 'imagelinks', 'page' ],
			[ 'page_namespace', 'page_title' ],
			[ 'il_to' => $titleObj->getDBkey(), 'il_from = page_id' ],
			__METHOD__,
			[ 'LIMIT' => $limit + 1 ]
		);
		foreach ( $res as $page ) {
			$title = Title::makeTitle( $page->page_namespace, $page->page_title );
			$title->invalidateCache();
		}
	}

	/**
	 * Add a source to the sources list if the transcode job is ready
	 *
	 * If the source is not found, it will not be used yet...
	 * Missing transcodes should be added by write tasks, not read tasks!
	 * @param File $file
	 * @param array &$sources
	 * @param string $transcodeKey
	 * @param array $dataPrefix
	 */
	public static function addSourceIfReady( $file, &$sources, $transcodeKey, $dataPrefix ) {
		// Check if the transcode is ready:
		if ( static::isTranscodeReady( $file, $transcodeKey ) ) {
			$sources[] = static::getDerivativeSourceAttributes( $file, $transcodeKey, $dataPrefix );
		}
	}

	/**
	 * Get the primary "source" asset used for other derivatives
	 * @param File $file
	 * @param array $options
	 * @return array
	 */
	public static function getPrimarySourceAttributes( $file, $options = [] ) {
		global $wgLang;
		$src = in_array( 'fullurl', $options, true ) ? wfExpandUrl( $file->getUrl() ) : $file->getUrl();

		/** @var FLACHandler|MidiHandler|Mp3Handler|Mp4Handler|OggHandler|WAVHandler $handler */
		$handler = $file->getHandler();
		'@phan-var FLACHandler|MidiHandler|Mp3Handler|Mp4Handler|OggHandler|WAVHandler $handler';
		$bitrate = $handler->getBitrate( $file );
		$metadataType = $handler->getMetadataType( $file );

		// Give grep a chance to find the usages: timedmedia-ogg, timedmedia-webm,
		// timedmedia-mp4, timedmedia-flac, timedmedia-wav
		if ( $handler->isAudio( $file ) ) {
			$title = wfMessage( 'timedmedia-source-audio-file-desc',
				wfMessage( 'timedmedia-' . $metadataType )->text() )
				->params( $wgLang->formatBitrate( $bitrate ) )->text();
		} else {
			$title = wfMessage( 'timedmedia-source-file-desc',
				wfMessage( 'timedmedia-' . $metadataType )->text() )
				->numParams( $file->getWidth(), $file->getHeight() )
				->params( $wgLang->formatBitrate( $bitrate ) )->text();
		}

		// Give grep a chance to find the usages: timedmedia-ogg, timedmedia-webm,
		// timedmedia-mp4, timedmedia-flac, timedmedia-wav
		$source = [
			'src' => $src,
			'type' => $handler->getWebType( $file ),
			'title' => $title,
			"shorttitle" => wfMessage(
				'timedmedia-source-file',
				wfMessage( 'timedmedia-' . $metadataType )->text()
			)->text(),
			"width" => (int)$file->getWidth(),
			"height" => (int)$file->getHeight(),
		];

		if ( $bitrate ) {
			$source["bandwidth"] = round( $bitrate );
		}

		// For video include framerate:
		if ( !$handler->isAudio( $file ) ) {
			$framerate = $handler->getFramerate( $file );
			if ( $framerate ) {
				$source[ "framerate" ] = (float)$framerate;
			}
		}
		return $source;
	}

	/**
	 * Get derivative "source" attributes
	 * @param File $file
	 * @param string $transcodeKey
	 * @param array $options
	 * @return array
	 * @suppress PhanTypePossiblyInvalidDimOffset
	 */
	public static function getDerivativeSourceAttributes( $file, $transcodeKey, $options = [] ) {
		$fileName = $file->getTitle()->getDBkey();

		$src = static::getTranscodedUrlForFile( $file, $transcodeKey );

		/** @var ID3Handler $handler */
		$handler = $file->getHandler();
		'@phan-var ID3Handler $handler';
		if ( $handler->isAudio( $file ) ) {
			$width = $height = 0;
		} else {
			[ $width, $height ] = static::getMaxSizeTransform(
				$file,
				static::$derivativeSettings[$transcodeKey]['maxSize']
			);
		}

		$framerate = static::$derivativeSettings[$transcodeKey]['framerate']
			?? $handler->getFramerate( $file );
		// Setup the url src:
		$src = in_array( 'fullurl', $options, true ) ? wfExpandUrl( $src ) : $src;
		$fields = [
			'src' => $src,
			'title' => wfMessage( 'timedmedia-derivative-desc-' . $transcodeKey )->text(),
			'type' => static::$derivativeSettings[ $transcodeKey ][ 'type' ],
			"shorttitle" => wfMessage( 'timedmedia-derivative-' . $transcodeKey )->text(),
			"transcodekey" => $transcodeKey,

			// Add data attributes per emerging DASH / webTV adaptive streaming attributes
			// eventually we will define a manifest xml entry point.
			"width" => (int)$width,
			"height" => (int)$height,
		];

		// a "ready" transcode should have a bitrate:
		if ( isset( static::$transcodeState[$fileName] ) ) {
			$fields["bandwidth"] = (int)static::$transcodeState[$fileName][$transcodeKey]['final_bitrate'];
		}

		if ( !$handler->isAudio( $file ) ) {
			$fields += [ "framerate" => (float)$framerate ];
		}
		return $fields;
	}

	/**
	 * Queue up all enabled transcodes if missing.
	 * @param File $file File object
	 */
	public static function startJobQueue( File $file ) {
		$keys = static::enabledTranscodes();

		// 'Natural sort' puts the transcodes in ascending order by resolution,
		// which roughly gives us fastest-to-slowest order.
		natsort( $keys );

		foreach ( $keys as $tKey ) {
			// Note the job queue will de-duplicate and handle various errors, so we
			// can just blast out the full list here.
			static::updateJobQueue( $file, $tKey );
		}
	}

	/**
	 * Make sure all relevant transcodes for the given file are tracked in the
	 * transcodes table; add entries for any missing ones.
	 *
	 * @param File $file File object
	 */
	public static function cleanupTranscodes( File $file ) {
		$fileName = $file->getTitle()->getDBkey();
		$db = $file->repo->getPrimaryDB();

		$transcodeState = static::getTranscodeState( $file, $db );

		$keys = static::enabledTranscodes();
		foreach ( $keys as $transcodeKey ) {
			if ( !static::isTranscodeEnabled( $file, $transcodeKey ) ) {
				// This transcode is no longer enabled or erroneously included...
				// Leave it in place, allowing it to be removed manually;
				// it won't be used in playback and should be doing no harm.
				continue;
			}
			if ( !isset( $transcodeState[ $transcodeKey ] ) ) {
				$db->insert(
					'transcode',
					[
						'transcode_image_name' => $fileName,
						'transcode_key' => $transcodeKey,
						'transcode_time_addjob' => null,
						'transcode_error' => "",
						'transcode_final_bitrate' => 0
					],
					__METHOD__,
					[ 'IGNORE' ]
				);
			}
		}

		// Remove from local WebVideoTranscode cache:
		static::clearTranscodeCache( $fileName );
	}

	/**
	 * Check if the given transcode key is appropriate for the file.
	 *
	 * @param File $file File object
	 * @param string $transcodeKey transcode key
	 * @return bool
	 * @suppress PhanTypePossiblyInvalidDimOffset
	 */
	public static function isTranscodeEnabled( File $file, $transcodeKey ) {
		/** @var FLACHandler|MidiHandler|Mp3Handler|Mp4Handler|OggHandler|WAVHandler $handler */
		$handler = $file->getHandler();
		'@phan-var FLACHandler|MidiHandler|Mp3Handler|Mp4Handler|OggHandler|WAVHandler $handler';
		$audio = $handler->isAudio( $file );
		if ( $audio ) {
			$keys = static::enabledAudioTranscodes();
		} else {
			$keys = static::enabledVideoTranscodes();
		}

		if ( in_array( $transcodeKey, $keys, true ) ) {
			$settings = static::$derivativeSettings[$transcodeKey];
			if ( $audio ) {
				$sourceCodecs = $handler->getStreamTypes( $file );
				$sourceCodec = $sourceCodecs ? strtolower( $sourceCodecs[0] ) : '';
				return ( $sourceCodec !== $settings['audioCodec'] );
			}
			if ( static::isTargetLargerThanFile( $file, $settings['maxSize'] ) ) {
				// Are we the smallest enabled transcode for this type?
				// Then go ahead and make a wee little transcode for compat.
				return static::isSmallestTranscodeForCodec( $transcodeKey );
			}
			return true;
		}
		// Transcode key is invalid or has been disabled.
		return false;
	}

	/**
	 * Update the job queue if the file is not already in the job queue:
	 * @param File &$file File object
	 * @param string $transcodeKey transcode key
	 */
	public static function updateJobQueue( &$file, $transcodeKey ) {
		$fileName = $file->getTitle()->getDBkey();
		$db = $file->repo->getPrimaryDB();

		$transcodeState = static::getTranscodeState( $file, $db );

		if ( !static::isTranscodeEnabled( $file, $transcodeKey ) ) {
			return;
		}

		// If the job hasn't been added yet, attempt to do so
		if ( !isset( $transcodeState[ $transcodeKey ] ) ) {
			$db->insert(
				'transcode',
				[
					'transcode_image_name' => $fileName,
					'transcode_key' => $transcodeKey,
					'transcode_time_addjob' => $db->timestamp(),
					'transcode_error' => "",
					'transcode_final_bitrate' => 0
				],
				__METHOD__,
				[ 'IGNORE' ]
			);

			if ( !$db->affectedRows() ) {
				// There is already a row for that job added by another request, no need to continue
				return;
			}

			// Set the priority
			$prioritized = static::isTranscodePrioritized( $file, $transcodeKey );

			$job = new WebVideoTranscodeJob( $file->getTitle(), [
				'transcodeMode' => 'derivative',
				'transcodeKey' => $transcodeKey,
				'prioritized' => $prioritized
			] );

			try {
				MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup()->push( $job );
				// Clear the state cache ( now that we have updated the page )
				static::clearTranscodeCache( $fileName );
			} catch ( Exception $ex ) {
				// Adding job failed, update transcode row
				$db->update(
					'transcode',
					[
						'transcode_time_error' => $db->timestamp(),
						'transcode_error' => "Failed to insert Job."
					],
					[
						'transcode_image_name' => $fileName,
						'transcode_key' => $transcodeKey,
					],
					__METHOD__
				);
			}
		}
	}

	/**
	 * Check if this transcode belongs to the high-priority queue.
	 * @param File $file
	 * @param string $transcodeKey
	 * @return bool
	 */
	public static function isTranscodePrioritized( File $file, $transcodeKey ) {
		global $wgTmhPriorityResolutionThreshold, $wgTmhPriorityLengthThreshold;

		$transcodeHeight = 0;
		$matches = [];
		if ( preg_match( '/^(\d+)p/', $transcodeKey, $matches ) ) {
			$transcodeHeight = (int)$matches[0];
		}
		return ( $transcodeHeight <= $wgTmhPriorityResolutionThreshold )
			&& ( $file->getLength() <= $wgTmhPriorityLengthThreshold );
	}

	/**
	 * Return job queue length for the queue that will run this transcode.
	 * @param File $file
	 * @param string $transcodeKey
	 * @return int
	 */
	public static function getQueueSize( File $file, $transcodeKey ) {
		// Warning: this won't treat the prioritized queue separately.
		$db = $file->repo->getPrimaryDB();
		$count = $db->selectField( 'transcode',
			'COUNT(*)',
			[
				'transcode_time_addjob IS NOT NULL',
				'transcode_time_startwork IS NULL',
				'transcode_time_success IS NULL',
				'transcode_time_error IS NULL',
			],
			__METHOD__
		);
		return (int)$count;
	}

	/**
	 * Transforms the size per a given "maxSize"
	 *  if maxSize is > file, file size is used
	 * @param File $file
	 * @param string $targetMaxSize
	 * @return int[]
	 */
	public static function getMaxSizeTransform( $file, $targetMaxSize ) {
		$maxSize = static::getMaxSize( $targetMaxSize );
		$sourceWidth = (int)$file->getWidth();
		$sourceHeight = (int)$file->getHeight();
		if ( $sourceHeight === 0 ) {
			// Audio file
			return [ 0, 0 ];
		}
		$sourceAspect = $sourceWidth / $sourceHeight;
		$targetWidth = $sourceWidth;
		$targetHeight = $sourceHeight;
		if ( $sourceAspect <= $maxSize['aspect'] ) {
			if ( $sourceHeight > $maxSize['height'] ) {
				$targetHeight = $maxSize['height'];
				$targetWidth = (int)( $targetHeight * $sourceAspect );
			}
		} else {
			if ( $sourceWidth > $maxSize['width'] ) {
				$targetWidth = $maxSize['width'];
				$targetHeight = (int)( $targetWidth / $sourceAspect );
				// some players do not like uneven frame sizes
			}
		}
		// some players do not like uneven frame sizes
		$targetWidth += $targetWidth % 2;
		$targetHeight += $targetHeight % 2;
		return [ $targetWidth, $targetHeight ];
	}

	/**
	 * Test if a given transcode target is larger than the source file
	 *
	 * @param File &$file File object
	 * @param string $targetMaxSize
	 * @return bool
	 */
	public static function isTargetLargerThanFile( &$file, $targetMaxSize ) {
		$maxSize = static::getMaxSize( $targetMaxSize );
		$sourceWidth = $file->getWidth();
		$sourceHeight = $file->getHeight();
		$sourceAspect = (int)$sourceWidth / (int)$sourceHeight;
		if ( $sourceAspect <= $maxSize['aspect'] ) {
			return ( $maxSize['height'] > $sourceHeight );
		}
		return ( $maxSize['width'] > $sourceWidth );
	}

	/**
	 * Is the given transcode key the smallest configured transcode for
	 * its video codec?
	 * @param string $transcodeKey
	 * @return bool
	 * @suppress PhanTypePossiblyInvalidDimOffset
	 */
	public static function isSmallestTranscodeForCodec( $transcodeKey ) {
		$settings = static::$derivativeSettings[$transcodeKey];
		$vcodec = $settings['videoCodec'];
		$maxSize = static::getMaxSize( $settings['maxSize'] );

		foreach ( static::enabledVideoTranscodes() as $tKey ) {
			$tsettings = static::$derivativeSettings[$tKey];
			if ( $tsettings['videoCodec'] === $vcodec ) {
				$tmaxSize = static::getMaxSize( $tsettings['maxSize'] );
				if ( $tmaxSize['width'] < $maxSize['width'] ) {
					return false;
				}
				if ( $tmaxSize['height'] < $maxSize['height'] ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Return maxSize array for given maxSize setting
	 *
	 * @param string $targetMaxSize
	 * @return array
	 */
	public static function getMaxSize( $targetMaxSize ) {
		$maxSize = [];
		$targetMaxSize = explode( 'x', $targetMaxSize, 2 );
		$maxSize['width'] = (int)$targetMaxSize[0];
		if ( count( $targetMaxSize ) === 1 ) {
			$maxSize['height'] = (int)$targetMaxSize[0];
		} else {
			$maxSize['height'] = (int)$targetMaxSize[1];
		}
		// check for zero size ( audio )
		if ( $maxSize['width'] === 0 || $maxSize['height'] === 0 ) {
			$maxSize['aspect'] = 0;
		} else {
			$maxSize['aspect'] = $maxSize['width'] / $maxSize['height'];
		}
		return $maxSize;
	}

	/**
	 * @param array $set
	 *
	 * @return array
	 */
	private static function filterAndSort( array $set ) {
		$keys = array_keys( array_filter( $set ) );
		natsort( $keys );
		return $keys;
	}

	public static function enabledTranscodes() {
		global $wgEnabledTranscodeSet, $wgEnabledAudioTranscodeSet;
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable These globals are arrays
		return static::filterAndSort( array_merge( $wgEnabledTranscodeSet, $wgEnabledAudioTranscodeSet ) );
	}

	public static function enabledVideoTranscodes() {
		global $wgEnabledTranscodeSet;
		return static::filterAndSort( $wgEnabledTranscodeSet );
	}

	public static function enabledAudioTranscodes() {
		global $wgEnabledAudioTranscodeSet;
		return static::filterAndSort( $wgEnabledAudioTranscodeSet );
	}

	public static function validateTranscodeConfiguration() {
		foreach ( static::enabledTranscodes() as $transcodeKey ) {
			if ( !isset( static::$derivativeSettings[ $transcodeKey ] ) ) {
				throw new MWException(
					__METHOD__ . ": Invalid key '$transcodeKey' specified in"
						. " wgEnabledTranscodeSet or wgEnabledAudioTranscodeSet."
				);
			}
		}
	}
}
