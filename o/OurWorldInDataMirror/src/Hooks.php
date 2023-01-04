<?php

namespace OurWorldInDataMirror;

use Html;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MWException;
use Parser;
use PPFrame;

class Hooks implements ParserFirstCallInitHook {
        /**
         * Register the &lt;ourworldindata&gt; tag
         *
         * @param Parser $parser Parser
         * @throws MWException On error
         */
        public function onParserFirstCallInit( $parser ) {
                $parser->setHook( 'ourworldindatamirror', [ $this, 'renderOurWorldInDataMirror' ] );
        }

        /**
         * Handler for the &lt;ourworldindata&gt; tag
         *
         * @param string $input Dataset to embed
         * @param array $args Arguments passed to dataset
         * @param Parser $parser Parser
         * @param PPFrame $frame Parser Frame
         * @return array HTML that will not be processed further
         */
        public function renderOurWorldInDataMirror( string $input, array $args, Parser $parser, PPFrame $frame ) {
                // check for non-url parts (also removes space after graph url)
                $inp_parts = preg_split("/\\s/", $input);
                $graph_url = $inp_parts[0];
                $graph_path = $graph_url;
                // check if we were given a full URL (possibly with parameters)
                // assume any host is owidm
                // path may or may not begin with /grapher/ but everything following taken as graph path
                // even the presence of ?tab=map will cause the input to parse as a url
                $url_parts = wfParseUrl( $graph_url );
                if ( $url_parts !== false ) {
                        if ( preg_match( ',^/grapher/(.*)$,', $url_parts['path'], $matches ))
                                $graph_path = $matches[1];
                        else
                                $graph_path = $url_parts['path'];
                        if ( isset( $url_parts['query'] ) ) {
                                // args passed explicitly into the tag override args present in the URL's query string
                                $args = array_merge( wfCgiToArray( $url_parts['query'] ), $args );
                        }
                }

                $baseUrl = 'https://owidm.wmcloud.org/grapher/' . rawurlencode( $graph_path );
                $url = wfAppendQuery( $baseUrl, $args );
                $parser->getOutput()->addModuleStyles( [ 'ext.owid' ] );
                return [
                        Html::element(
                                'iframe',
                                [
                                        'src' => $url,
                                        'loading' => 'lazy',
                                        'class' => 'owid-frame mw-kartographer-container'
                                ]
                        ),
                        'markerType' => 'nowiki'
                ];
        }
}
