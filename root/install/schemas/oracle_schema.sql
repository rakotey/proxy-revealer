/*

 $Id: $

*/

/*
  This first section is optional, however its probably the best method
  of running phpBB on Oracle. If you already have a tablespace and user created
  for phpBB you can leave this section commented out!

  The first set of statements create a phpBB tablespace and a phpBB user,
  make sure you change the password of the phpBB user before you run this script!!
*/

/*
CREATE TABLESPACE "PHPBB"
	LOGGING
	DATAFILE 'E:\ORACLE\ORADATA\LOCAL\PHPBB.ora'
	SIZE 10M
	AUTOEXTEND ON NEXT 10M
	MAXSIZE 100M;

CREATE USER "PHPBB"
	PROFILE "DEFAULT"
	IDENTIFIED BY "phpbb_password"
	DEFAULT TABLESPACE "PHPBB"
	QUOTA UNLIMITED ON "PHPBB"
	ACCOUNT UNLOCK;

GRANT ANALYZE ANY TO "PHPBB";
GRANT CREATE SEQUENCE TO "PHPBB";
GRANT CREATE SESSION TO "PHPBB";
GRANT CREATE TABLE TO "PHPBB";
GRANT CREATE TRIGGER TO "PHPBB";
GRANT CREATE VIEW TO "PHPBB";
GRANT "CONNECT" TO "PHPBB";

COMMIT;
DISCONNECT;

CONNECT phpbb/phpbb_password;
*/
/*
	Table: 'phpbb_speculative_excludes'
*/
CREATE TABLE phpbb_speculative_excludes (
	exclude_id number(8) NOT NULL,
	user_id number(8) DEFAULT '0' NOT NULL,
	ip_address varchar2(40) DEFAULT '' ,
	CONSTRAINT pk_phpbb_speculative_excludes PRIMARY KEY (exclude_id)
)
/

CREATE INDEX phpbb_speculative_excludes_user_id ON phpbb_speculative_excludes (user_id)
/
CREATE INDEX phpbb_speculative_excludes_ip_address ON phpbb_speculative_excludes (ip_address)
/

CREATE SEQUENCE phpbb_speculative_excludes_seq
/

CREATE OR REPLACE TRIGGER t_phpbb_speculative_excludes
BEFORE INSERT ON phpbb_speculative_excludes
FOR EACH ROW WHEN (
	new.exclude_id IS NULL OR new.exclude_id = 0
)
BEGIN
	SELECT phpbb_speculative_excludes_seq.nextval
	INTO :new.exclude_id
	FROM dual;
END;
/


/*
	Table: 'phpbb_speculative_ips'
*/
CREATE TABLE phpbb_speculative_ips (
	ip_address varchar2(40) DEFAULT '' ,
	method number(1) DEFAULT '0' NOT NULL,
	discovered number(11) DEFAULT '0' NOT NULL,
	real_ip varchar2(40) DEFAULT '' ,
	info clob DEFAULT '' 
)
/

CREATE INDEX phpbb_speculative_ips_ip_address ON phpbb_speculative_ips (ip_address)
/

