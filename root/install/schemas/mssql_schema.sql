/*

 $Id: $

*/

BEGIN TRANSACTION
GO

/*
	Table: 'phpbb_speculative_excludes'
*/
CREATE TABLE [phpbb_speculative_excludes] (
	[exclude_id] [int] IDENTITY (1, 1) NOT NULL ,
	[user_id] [int] DEFAULT (0) NOT NULL ,
	[ip_address] [varchar] (40) DEFAULT ('') NOT NULL 
) ON [PRIMARY]
GO

ALTER TABLE [phpbb_speculative_excludes] WITH NOCHECK ADD 
	CONSTRAINT [PK_phpbb_speculative_excludes] PRIMARY KEY  CLUSTERED 
	(
		[exclude_id]
	)  ON [PRIMARY] 
GO

CREATE  INDEX [user_id] ON [phpbb_speculative_excludes]([user_id]) ON [PRIMARY]
GO

CREATE  INDEX [ip_address] ON [phpbb_speculative_excludes]([ip_address]) ON [PRIMARY]
GO


/*
	Table: 'phpbb_speculative_ips'
*/
CREATE TABLE [phpbb_speculative_ips] (
	[ip_address] [varchar] (40) DEFAULT ('') NOT NULL ,
	[method] [int] DEFAULT (0) NOT NULL ,
	[discovered] [int] DEFAULT (0) NOT NULL ,
	[real_ip] [varchar] (40) DEFAULT ('') NOT NULL ,
	[info] [varchar] (4000) DEFAULT ('') NOT NULL 
) ON [PRIMARY]
GO

CREATE  INDEX [ip_address] ON [phpbb_speculative_ips]([ip_address]) ON [PRIMARY]
GO



COMMIT
GO

