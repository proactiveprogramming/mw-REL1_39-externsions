( function () {

	/**
	 * Recorder for Special:RecordSpeech.
	 *
	 * Uses LinguaRecorder to record speech from user. The recording
	 * is stored in an audio element that the user can preview before
	 * deciding to submit or re-record.
	 *
	 * @class Recorder
	 * @param {LinguaRecorder} linguaRecorder
	 * @param {OO.ui.ButtonWidget} recordButton
	 * @param {OO.ui.LabelWidget} recordStatusLabel
	 * @param {jQuery} $previewPlayer
	 */
	function Recorder(
		linguaRecorder,
		recordButton,
		recordStatusLabel,
		$previewPlayer
	) {
		this.linguaRecorder = linguaRecorder;
		this.recordButton = recordButton;
		this.recordStatusLabel = recordStatusLabel;
		this.$previewPlayer = $previewPlayer;
	}

	/**
	 * Start recording audio.
	 *
	 * @memberof Recorder
	 */
	Recorder.prototype.startRecording = function () {
		var self = this;
		this.recordButton.setDisabled( true );
		// Wait a short moment before starting to listen to not pick
		// up click from mouse or keyboard.
		window.setTimeout(
			function () {
				self.linguaRecorder.start();
			},
			500
		);
	};

	/**
	 * Store recorded data in a preview audio element.
	 *
	 * This allows the user to listen back to the recording and keeps
	 * the data stored for when it needs to be sent to API. Also
	 * updates UI to convey that recording is done.
	 *
	 * @memberof Recorder
	 * @param {AudioRecord} recording
	 */
	Recorder.prototype.finishRecording = function ( recording ) {
		this.$previewPlayer.attr( 'src', recording.getObjectURL() );
		this.setStatus( 'wikispeech-sdc-status-ready' );
		this.recordButton.setDisabled( false );
	};

	/**
	 * Set status label to a message to show the user the state of recording.
	 *
	 * The status is one of the following (see comment in function for
	 * exact message names):
	 * * ready - ready to start when record button is clicked
	 * * listening - recording will start as soon as loud enough audio
	 *   is detected.
	 * * recording - currently recording, will stop when audio is not
	 *   loud enough for a short while.
	 *
	 * @memberof Recorder
	 * @param {string} message
	 */
	Recorder.prototype.setStatus = function ( message ) {
		// Messages that can be used here:
		// * wikispeech-sdc-status-ready
		// * wikispeech-sdc-status-listening
		// * wikispeech-sdc-status-recording
		this.recordStatusLabel.setLabel( mw.msg( message ) );
	};

	module.exports = Recorder;
}() );
