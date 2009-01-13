CREATE TABLE htcron (
  id int(11) NOT NULL auto_increment,
  spec varchar(250) NOT NULL default '',
  command varchar(250) NOT NULL default '',
  last varchar(100) NOT NULL default '1',
  chunk varchar(200) NOT NULL default '-1',
  time_last_chunk varchar(100) NOT NULL default '',
  state int(11) NOT NULL default '0',
  param varchar(50) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY command(command)
);
    
