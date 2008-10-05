#
# $Id: $
#

# Table: 'phpbb_speculative_excludes'
CREATE TABLE phpbb_speculative_excludes (
	exclude_id mediumint(8) UNSIGNED NOT NULL auto_increment,
	user_id mediumint(8) UNSIGNED DEFAULT '0' NOT NULL,
	ip_address varchar(40) DEFAULT '' NOT NULL,
	PRIMARY KEY (exclude_id),
	KEY user_id (user_id),
	KEY ip_address (ip_address)
) CHARACTER SET `utf8` COLLATE `utf8_bin`;


# Table: 'phpbb_speculative_ips'
CREATE TABLE phpbb_speculative_ips (
	ip_address varchar(40) DEFAULT '' NOT NULL,
	method smallint(4) UNSIGNED DEFAULT '0' NOT NULL,
	discovered int(11) UNSIGNED DEFAULT '0' NOT NULL,
	real_ip varchar(40) DEFAULT '' NOT NULL,
	info text NOT NULL,
	KEY ip_address (ip_address)
) CHARACTER SET `utf8` COLLATE `utf8_bin`;


