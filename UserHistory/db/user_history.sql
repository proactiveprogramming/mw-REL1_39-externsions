BEGIN;

--
-- Used by the User History extension
--
CREATE TABLE /*_*/user_history (
	uh_user_id INT NOT NULL,
	uh_page_id INT NOT NULL,
	uh_timestamp VARBINARY(14) NULL
)/*$wgDBTableOptions*/;

CREATE INDEX /*i*/uh_user_id ON user_history (uh_user_id);

COMMIT;