#
# Table structure for table 'tx_rssecuredownload_codes'
#
CREATE TABLE tx_rssecuredownload_codes (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	title varchar(255) DEFAULT '' NOT NULL,
	description text NOT NULL,
	codeprompt varchar(255) DEFAULT '' NOT NULL,
	code varchar(255) DEFAULT '' NOT NULL,
	file blob NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'tx_rssecuredownload_logs'
#
CREATE TABLE tx_rssecuredownload_logs (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	docid int(11) DEFAULT '0' NOT NULL,
	accesstime int(11) DEFAULT '0' NOT NULL,
	rbrowser varchar(255) DEFAULT '' NOT NULL,
	ripadress varchar(255) DEFAULT '' NOT NULL,
	rname varchar(255) DEFAULT '' NOT NULL,
	error int(11) DEFAULT '0' NOT NULL,
	errortext varchar(255) default '',
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);