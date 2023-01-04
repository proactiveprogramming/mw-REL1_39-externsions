-- This is the relational database mapping of class Recording.
-- For details on fields, see Recording.php
-- For details on mapping, see RecordingCrud.php
CREATE TABLE IF NOT EXISTS /*_*/wikispeech_sdc_recording(
    wssdcr_identity varbinary(32) NOT NULL PRIMARY KEY,
    wssdcr_recorded varbinary(14) NOT NULL default '',
    wssdcr_voice_of varbinary(32) NOT NULL default '',
    wssdcr_spoken_dialect varbinary(32),
    wssdcr_manuscript_prompt varbinary(32) NOT NULL default '',
    wssdcr_audio_file_wiki_page_identity int(10) unsigned NOT NULL
)/*$wgDBTableOptions*/;

CREATE INDEX /*i*/list_by_voice_of ON /*_*/wikispeech_sdc_recording (wssdcr_voice_of);
CREATE INDEX /*i*/list_by_manuscript_prompt ON /*_*/wikispeech_sdc_recording (wssdcr_manuscript_prompt);
CREATE INDEX /*i*/get_by_voice_of_and_manuscript_prompt ON /*_*/wikispeech_sdc_recording (wssdcr_voice_of, wssdcr_manuscript_prompt);
CREATE INDEX /*i*/get_by_audio_file_wiki_page_identity ON /*_*/wikispeech_sdc_recording (wssdcr_audio_file_wiki_page_identity);
