#
# $Id: $
#


# Table: 'phpbb_speculative_excludes'
CREATE TABLE phpbb_speculative_excludes (
	exclude_id INTEGER NOT NULL,
	user_id INTEGER DEFAULT 0 NOT NULL,
	ip_address VARCHAR(40) CHARACTER SET NONE DEFAULT '' NOT NULL
);;

ALTER TABLE phpbb_speculative_excludes ADD PRIMARY KEY (exclude_id);;

CREATE INDEX phpbb_speculative_excludes_user_id ON phpbb_speculative_excludes(user_id);;
CREATE INDEX phpbb_speculative_excludes_ip_address ON phpbb_speculative_excludes(ip_address);;

CREATE GENERATOR phpbb_speculative_excludes_gen;;
SET GENERATOR phpbb_speculative_excludes_gen TO 0;;

CREATE TRIGGER t_phpbb_speculative_excludes FOR phpbb_speculative_excludes
BEFORE INSERT
AS
BEGIN
	NEW.exclude_id = GEN_ID(phpbb_speculative_excludes_gen, 1);
END;;


# Table: 'phpbb_speculative_ips'
CREATE TABLE phpbb_speculative_ips (
	ip_address VARCHAR(40) CHARACTER SET NONE DEFAULT '' NOT NULL,
	method INTEGER DEFAULT 0 NOT NULL,
	discovered INTEGER DEFAULT 0 NOT NULL,
	real_ip VARCHAR(40) CHARACTER SET NONE DEFAULT '' NOT NULL,
	info BLOB SUB_TYPE TEXT CHARACTER SET UTF8 DEFAULT '' NOT NULL
);;

CREATE INDEX phpbb_speculative_ips_ip_address ON phpbb_speculative_ips(ip_address);;

