-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: extensions/Wikispeech/sql/tables.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE wikispeech_utterance (
  wsu_utterance_id SERIAL NOT NULL,
  wsu_remote_wiki_hash CHAR(64) DEFAULT NULL,
  wsu_page_id INT NOT NULL,
  wsu_lang TEXT NOT NULL,
  wsu_seg_hash CHAR(64) NOT NULL,
  wsu_voice VARCHAR(30) NOT NULL,
  wsu_date_stored TIMESTAMPTZ NOT NULL,
  PRIMARY KEY(wsu_utterance_id)
);

CREATE INDEX get_utterance ON wikispeech_utterance (
  wsu_remote_wiki_hash, wsu_page_id,
  wsu_lang, wsu_voice, wsu_seg_hash
);

CREATE INDEX expire_page_utterances ON wikispeech_utterance (
  wsu_remote_wiki_hash, wsu_page_id
);

CREATE INDEX expire_utterances_lang ON wikispeech_utterance (wsu_lang);

CREATE INDEX expire_utterances_lang_voice ON wikispeech_utterance (wsu_lang, wsu_voice);

CREATE INDEX expire_utterances_ttl ON wikispeech_utterance (wsu_date_stored);