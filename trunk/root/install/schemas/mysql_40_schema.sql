#
# $Id: $
#

# Table: 'phpbb_speculative_excludes'
CREATE TABLE phpbb_speculative_excludes (
	exclude_id mediumint(8) UNSIGNED NOT NULL auto_increment,
	user_id mediumint(8) UNSIGNED DEFAULT '0' NOT NULL,
	ip_address varbinary(40) DEFAULT '' NOT NULL,
	PRIMARY KEY (exclude_id),
	KEY user_id (user_id),
	KEY ip_address (ip_address)
);


# Table: 'phpbb_speculative_ips'
CREATE TABLE phpbb_speculative_ips (
	ip_address varbinary(40) DEFAULT '' NOT NULL,
	method tinyint(1) DEFAULT '0' NOT NULL,
	discovered int(11) UNSIGNED DEFAULT '0' NOT NULL,
	real_ip varbinary(40) DEFAULT '' NOT NULL,
	info blob NOT NULL,
	KEY ip_address (ip_address)
);


