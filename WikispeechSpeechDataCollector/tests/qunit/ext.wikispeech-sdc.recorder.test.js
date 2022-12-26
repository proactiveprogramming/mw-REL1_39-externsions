( function () {
	QUnit.module( 'ext.wikispeech-sdc.recorder', QUnit.newMwEnvironment( {
		setup: function () {
			var Recorder = require( '../../modules/ext.wikispeech-sdc.recorder.js' );
			this.recorder = new Recorder();
			this.recorder.linguaRecorder = { start: sinon.stub() };
			this.recorder.recordButton = { setDisabled: sinon.stub() };
			this.recorder.recordStatusLabel = { setLabel: sinon.stub() };
		}
	} ) );

	QUnit.test( 'startRecording()', function () {
		var clock = this.sandbox.useFakeTimers();
		this.recorder.startRecording();
		clock.tick( 500 );

		sinon.assert.calledOnce( this.recorder.linguaRecorder.start );
		clock.restore();
	} );

	QUnit.test( 'startRecording(): disable record button', function () {
		this.recorder.startRecording();

		sinon.assert.calledOnce( this.recorder.recordButton.setDisabled );
		sinon.assert.calledWithExactly(
			this.recorder.recordButton.setDisabled,
			true
		);
	} );

	QUnit.test( 'startRecording(): do not start immediately', function () {
		this.recorder.startRecording();

		sinon.assert.notCalled( this.recorder.linguaRecorder.start );
	} );

	QUnit.test( 'finishRecording()', function () {
		var recording = { getObjectURL: sinon.stub().returns( 'audio url' ) };
		this.recorder.$previewPlayer = { attr: sinon.stub() };

		this.recorder.finishRecording( recording );

		sinon.assert.calledOnce( this.recorder.$previewPlayer.attr );
		sinon.assert.calledWithExactly(
			this.recorder.$previewPlayer.attr,
			'src',
			'audio url'
		);
		sinon.assert.calledOnce( this.recorder.recordButton.setDisabled );
		sinon.assert.calledWithExactly(
			this.recorder.recordButton.setDisabled,
			false
		);
	} );

	QUnit.test( 'setStatus()', function () {
		this.recorder.setStatus( 'message' );

		sinon.assert.calledOnce( this.recorder.recordStatusLabel.setLabel );
		sinon.assert.calledWithExactly(
			this.recorder.recordStatusLabel.setLabel,
			mw.msg( 'message' )
		);
	} );
}() );
