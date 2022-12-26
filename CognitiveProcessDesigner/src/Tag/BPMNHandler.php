<?php
namespace CognitiveProcessDesigner\Tag;

use Html;
use MediaWiki\MediaWikiServices;
use Parser;
use PPFrame;

class BPMNHandler {

	/**
	 *
	 * @var string
	 */
	protected $processedInput = '';

	/**
	 *
	 * @var array
	 */
	protected $processedArgs = [];

	/**
	 *
	 * @var Parser
	 */
	protected $parser = null;

	/**
	 *
	 * @var PPFrame
	 */
	protected $frame = null;

	/**
	 * @var string
	 */
	protected $defaultImgType = 'svg';

	/**
	 * @var false|string|string[]
	 */
	protected $tagInput = '';

	/**
	 * @var array
	 */
	protected $tagArgs = [];

	/**
	 * BPMNHandler constructor.
	 * @param string $processedInput
	 * @param array $processedArgs
	 * @param \Parser $parser
	 * @param \PPFrame $frame
	 */
	public function __construct( $processedInput, array $processedArgs,
		 \Parser $parser, \PPFrame $frame
	) {
		$this->tagInput = explode( ' ', trim( $processedInput ) );
		$this->tagArgs = $processedArgs;
		$this->parser = $parser;
		$this->frame = $frame;
	}

	/**
	 * @return string
	 */
	public function handle() {
		if ( !isset( $this->tagArgs['name'] ) || $this->tagArgs['name'] === '' ) {
			return Html::errorBox( '"name" attribute of diagram must be specified!' );
		}

		$this->parser->getOutput()->addModules( [
			'ext.cognitiveProcessDesigner.editor',
			'ext.cognitiveProcessDesignerEdit.styles'
		] );

		$bpmnName = wfStripIllegalFilenameChars( $this->tagArgs['name'] );
		$imgName = $bpmnName . '.' . $this->defaultImgType;
		$img = MediaWikiServices::getInstance()->getRepoGroup()->findFile( $imgName );
		if ( $img ) {
			$imgUrl = $img->getCanonicalUrl();
			$imgHeight = isset( $this->tagArgs['height'] ) ? $this->tagArgs['height'] : $img->getHeight();
			$imgWidth = isset( $this->tagArgs['width'] ) ? $this->tagArgs['width'] : $img->getWidth();
		} else {
			$imgUrl = '';
			$imgHeight = isset( $this->tagArgs['height'] ) ? $this->tagArgs['height'] : 'auto';
			$imgWidth = isset( $this->tagArgs['width'] ) ? $this->tagArgs['width'] : 'auto';
		}

		$id = mt_rand();
		if ( method_exists( $this->parser, 'getUserIdentity' ) ) {
			// MW 1.36+
			$user = $this->parser->getUserIdentity();
		} else {
			// @phan-suppress-next-line PhanUndeclaredMethod
			$user = $this->parser->getUser();
		}
		$readonly = !in_array(
			'cognitiveprocessdesigner-editbpmn',
			MediaWikiServices::getInstance()->getPermissionManager()
				->getUserPermissions( $user )
		);

		$output = Html::openElement( 'div', [
			'id' => 'cpd-' . $id,
			'class' => 'cpd-wrapper'
		] );

		if ( !$readonly ) {
			$output .= Html::openElement(
				'div',
				[
					'class' => 'cpd-toolbar',
					'align' => 'right'
				]
			);
			$output .= Html::element(
				'button',
				[
					'id' => 'cpd-btn-edit-bpmn-id-' . $id,
					'class' => 'cpd-edit-bpmn mw-ui-button',
					'data-id' => $id,
					'data-bpmn-name' => $bpmnName
				],
				wfMessage( 'edit' )->text()
			);
			$output .= Html::closeElement( 'div' );
		}

		$imgClass = '';

		// output image and optionally a placeholder if the image does not exist yet
		if ( !$img ) {
			$imgClass = 'hidden';
			// show placeholder
			$output .= Html::openElement(
				'div',
				[
					'id' => 'cpd-placeholder-' . $id,
					'class' => 'cpd-editor-info-box'
				]
			);

			$output .= Html::element( 'b', [], $bpmnName );
			$output .= Html::element( 'br' );
			$output .= Html::element( 'span', [], wfMessage( 'cpd-empty-diagram' )->text() );

			$output .= Html::closeElement( 'div' );
		}

		// the image or object element must be there' in any case
		// it's hidden as long as there is no content.
		$output .= Html::openElement(
			'object',
			[
				'id' => 'cpd-img-' . $id,
				'data' => $imgUrl,
				'type' => 'image/svg+xml',
				'class' => $imgClass,
				'height' => $imgHeight,
				'width' => $imgWidth,
			]
		);
		$output .= Html::closeElement( 'object' );

		$output .= Html::element(
			'div',
			[ 'id' => 'cpd-wrapper-' . $id, 'class' => 'hidden cpd-js-drop-zone' ]
		);

		return $output;
	}

}
