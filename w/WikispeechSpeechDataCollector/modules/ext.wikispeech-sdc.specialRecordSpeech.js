( function () {
	/**
	 * Add functionality to Special:RecordSpeech.
	 */

	var Recorder, $content, recordButton, recordStatusLabel, $previewPlayer,
		linguaRecorder, recorder;

	Recorder = require( './ext.wikispeech-sdc.recorder.js' );
	// eslint-disable-next-line no-jquery/no-global-selector
	$content = $( '#mw-content-text' );
	recordButton = OO.ui.ButtonWidget.static.infuse(
		$content.find( '.ext-wikispeech-sdc-record' )
	);
	recordStatusLabel = OO.ui.LabelWidget.static.infuse(
		$content.find( '.ext-wikispeech-sdc-status' )
	);
	$previewPlayer = $content.find( '.ext-wikispeech-sdc-preview-player' );
	// eslint-disable-next-line no-undef
	linguaRecorder = new LinguaRecorder( {
		autoStart: true,
		autoStop: true,
		stopDuration: 1.0
	} );
	recorder = new Recorder(
		linguaRecorder,
		recordButton,
		recordStatusLabel,
		$previewPlayer
	);
	recordButton.on( 'click', recorder.startRecording.bind( recorder ) );
	linguaRecorder.on( 'listening', function () {
		recorder.setStatus( 'wikispeech-sdc-status-listening' );
	} );
	linguaRecorder.on( 'started', function () {
		recorder.setStatus( 'wikispeech-sdc-status-recording' );
	} );
	linguaRecorder.on( 'stoped', recorder.finishRecording.bind( recorder ) );
	linguaRecorder.on( 'ready', function () {
		recorder.recordButton.setDisabled( false );
		recorder.setStatus( 'wikispeech-sdc-status-ready' );
	} );
}() );
