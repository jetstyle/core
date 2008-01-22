#
# Структура таблицы `kuzocm_picfiles_lists`
#

CREATE TABLE kuzocm_picfiles_lists (
  id int(11) NOT NULL auto_increment,
  topic_id int(11) NOT NULL default '0',
  title varchar(255) NOT NULL default '',
  descr varchar(255) NOT NULL default '',
  _state tinyint(1) NOT NULL default '0',
  _modified timestamp(14) NOT NULL,
  _created timestamp(14) NOT NULL default '00000000000000',
  _order int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY topic_id (topic_id),
  KEY _state (_state),
  KEY _order (_order)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Структура таблицы `kuzocm_picfiles_lists_topics`
#

CREATE TABLE kuzocm_picfiles_lists_topics (
  id int(11) NOT NULL auto_increment,
  title varchar(255) NOT NULL default '',
  _state tinyint(1) NOT NULL default '0',
  _modified timestamp(14) NOT NULL,
  _created timestamp(14) NOT NULL default '00000000000000',
  _order int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY _state (_state),
  KEY _order (_order)
) TYPE=MyISAM;

    