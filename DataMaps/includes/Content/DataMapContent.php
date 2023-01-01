<?php
namespace MediaWiki\Extension\DataMaps\Content;

use FormatJson;
use JsonContent;
use MediaWiki\Extension\DataMaps\Data\DataMapSpec;
use MediaWiki\Extension\DataMaps\Data\DataModelMixinTransformer;
use MediaWiki\Extension\DataMaps\ExtensionConfig;
use MediaWiki\Extension\DataMaps\Rendering\EmbedRenderer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use Parser;
use ParserOutput;
use Status;
use stdClass;
use Title;

class DataMapContent extends JsonContent {
    public const LERR_NOT_FOUND = 1;
    public const LERR_NOT_DATAMAP = 2;

    # Reduce 2-12 numbers in an array onto a single line
    private const JOIN_MULTIPLE_NUMBERS_RE = '/(\n\s+)([+-]?\d+(\.\d+)?([eE][-+]?\d+)?|true|false),(?:\n\s+(?:[+-]?\d+(\.\d+)?([eE][-+]?\d+)?|true|false|null|"[^"\n\t]*"),?){1,12}/';
    # Reduce short arrays of strings onto a single line
    private const JOIN_MULTIPLE_STRINGS_RE = '/\[((?:\n\s+".{1,30}",?\s*$){1,4})\n\s+\]/';
    # Reduces dict fields with only a single line of content (including previously joined multiple fields) to a single line
    private const COLLAPSE_SINGLE_LINE_DICT_RE = '/\{\n\s+("\w+": [^}\n\]]{1,120})\n\s+\}/';
    # Reduce arrays with only a single line of content (including previously joined multiple fields) to a single line
    private const COLLAPSE_SINGLE_LINE_ARRAY_RE = '/\[\s+(.+)\s+\]/';
    # Sets of named fields that should be combined onto a single line
    private const JOIN_LINE_FIELDS = [
        'left|right|top|bottom',
        // Backgrounds
        'name|image',
        // Marker groups
        'fillColor|size',
        'fillColor|borderColor',
        'fillColor|borderColor|size',
        'fillColor|borderColor|borderWidth',
        'fillColor|borderColor|borderWidth|size',
        'icon|size',
        'name|icon',
        'name|icon|size',
        'name|fillColor|size',
        'name|fillColor|size|icon',
        // Layers
        'name|subtleText',
        // Markers
        'id|lat|lon',
        'id|x|y',
        'id|y|x',
        'lat|lon',
        'x|y',
        'y|x',
        'lat|lon|article',
        'x|y|article',
        'y|x|article',
        'lat|lon|image',
        'x|y|image',
        'y|x|image',
        'lat|lon|popupImage',
        'article|popupImage',
        'article|image'
    ];

    private ?DataMapSpec $modelCached = null;

    public function __construct( $text, $modelId = ARK_CONTENT_MODEL_DATAMAP ) {
        parent::__construct( $text, $modelId );
    }

    public static function toJSON( stdclass $raw ) {
        $out = FormatJson::encode( $raw, "\t", FormatJson::ALL_OK );

        foreach ( self::JOIN_LINE_FIELDS as $term ) {
            $part = '(?:("(?:' . $term . ')": [^,\n]+,?))';
            $fieldCount = substr_count( $term, '|' ) + 1;
            $full = '/' . implode( '\s+', array_fill( 0, $fieldCount, $part ) ) . '(\s+)/';
            $subs = implode( ' ', array_map( fn ( $n ) => '$' . $n, range( 1, $fieldCount ) ) ) . "$" . ( $fieldCount + 1 );
            $out = preg_replace( $full, $subs, $out );
        }

        $out = preg_replace_callback( self::JOIN_MULTIPLE_NUMBERS_RE, static function ( array $matches ) {
            $txt = $matches[0];
            $txt = preg_replace( '/\s*\n\s+/', '', $txt );
            $txt = str_replace( ',', ', ', $txt );
            return $matches[1] . $txt;
        }, $out );
        $out = preg_replace( self::COLLAPSE_SINGLE_LINE_DICT_RE, '{ $1 }', $out );
        $out = preg_replace( self::COLLAPSE_SINGLE_LINE_ARRAY_RE, '[ $1 ]', $out );

        return $out;
    }

    public static function loadPage( Title $title ) {
        if ( !$title || !$title->exists() ) {
            return self::LERR_NOT_FOUND;
        }

        $mapPage = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
        $content = $mapPage->getContent( RevisionRecord::RAW );

        if ( !( $content instanceof DataMapContent ) ) {
            return self::LERR_NOT_DATAMAP;
        }

        return $content;
    }

    private function mergeMixins( stdClass $main ) {
        if ( !isset( $main->mixins ) ) {
            return $main;
        }

        // Copy the mixins list to prevent bad behaviour when merging occurs. Mixins should be always stated explicitly.
        $mixins = $main->mixins;

        $finalMixin = null;
        foreach ( $mixins as &$mixinName ) {
            $title = Title::makeTitleSafe( ExtensionConfig::getNamespaceId(), $mixinName );
            $mixinPage = self::loadPage( $title );

            // Mixin failed to load, skip it. There's no way for us to throw an error at this stage without crashing the whole
            // request. However, validation can catch this most of the time.
            if ( is_numeric( $mixinPage ) ) {
                continue;
            }
            $mixin = $mixinPage->getData()->getValue();
            if ( $mixin == null ) {
                continue;
            }

            if ( $finalMixin === null ) {
                // First mixin, keep unmodified
                $finalMixin = $mixin;
            } else {
                // Nth mixin, merge onto collective
                $finalMixin = DataModelMixinTransformer::mergeTwoObjects( $finalMixin, $mixin );
            }
        }

        if ( $finalMixin !== null ) {
            // Merge main onto collective
            $main = DataModelMixinTransformer::mergeTwoObjects( $finalMixin, $main );
        }

        // Remove $mixin field
        if ( isset( $main->{'$mixin'} ) ) {
            unset( $main->{'$mixin'} );
        }

        return $main;
    }

    public function asModel(): DataMapSpec {
        if ( $this->modelCached === null ) {
            $this->modelCached = new DataMapSpec( $this->mergeMixins( $this->getData()->getValue() ) );
        }
        return $this->modelCached;
    }

    public function isMixin(): bool {
        return DataMapSpec::staticIsMixin( $this->getData()->getValue() );
    }

    public function getEmbedRenderer( Title $title, Parser $parser, ParserOutput $parserOutput, array $options = [] ): EmbedRenderer {
        return new EmbedRenderer( $title, $this->asModel(), $parser, $parserOutput, $options );
    }

    public function beautifyJSON() {
        return self::toJSON( $this->getData()->getValue() );
    }

    public function getValidationStatus() {
        $status = new Status();
        if ( !$this->isValid() ) {
            $status->fatal( 'datamap-error-validate-invalid-json' );
        } else {
            if ( $this->isMixin() && isset( $this->getData()->getValue()->mixins ) ) {
                $status->fatal( 'datamap-error-validatespec-map-mixin-with-mixins' );
                return;
            }
            $this->asModel()->validate( $status );
        }
        return $status;
    }
}
