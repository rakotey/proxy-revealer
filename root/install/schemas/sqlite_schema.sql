#
# $Id: $
#

BEGIN TRANSACTION;

# Table: 'phpbb_speculative_excludes'
CREATE TABLE phpbb_speculative_excludes (
	exclude_id INTEGER PRIMARY KEY NOT NULL ,
	user_id INTEGER UNSIGNED NOT NULL DEFAULT '0',
	ip_address varchar(40) NOT NULL DEFAULT ''
);

CREATE INDEX phpbb_speculative_excludes_user_id ON phpbb_speculative_excludes (user_id);
CREATE INDEX phpbb_speculative_excludes_ip_address ON phpbb_speculative_excludes (ip_address);

# Table: 'phpbb_speculative_ips'
CREATE TABLE phpbb_speculative_ips (
	ip_address varchar(40) NOT NULL DEFAULT '',
	method tinyint(1) NOT NULL DEFAULT '0',
	discovered INTEGER UNSIGNED NOT NULL DEFAULT '0',
	real_ip varchar(40) NOT NULL DEFAULT '',
	info text(65535) NOT NULL DEFAULT ''
);

CREATE INDEX phpbb_speculative_ips_ip_address ON phpbb_speculative_ips (ip_address);


COMMIT;