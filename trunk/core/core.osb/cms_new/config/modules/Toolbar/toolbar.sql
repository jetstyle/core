CREATE TABLE _toolbar (
  id int(11) NOT NULL auto_increment,
  title varchar(255) NOT NULL default '',
  href varchar(255) NOT NULL default '',
  _state tinyint(1) NOT NULL default '0',
  _modified timestamp(14) NOT NULL,
  _created timestamp(14) NOT NULL,
  _order int(11) NOT NULL default '0',
  PRIMARY KEY (id),
  KEY _state(_state),
  KEY _order(_order)
) TYPE=MyISAM;
