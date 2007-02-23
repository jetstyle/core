# phpMyAdmin MySQL-Dump
# version 2.4.0
# http://www.phpmyadmin.net/ (download page)
#
# Хост: localhost
# Время создания: Сен 17 2004 г., 10:33
# Версия сервера: 3.23.32
# Версия PHP: 4.3.7
# БД : `rocket_test`
# --------------------------------------------------------

#
# Структура таблицы `rocket_users`
#

CREATE TABLE rocket_users (
  user_id int(11) NOT NULL auto_increment,
  login varchar(32) NOT NULL default '',
  realm varchar(32) NOT NULL default '',
  password varchar(32) NOT NULL default '',
  stored_invariant varchar(32) NOT NULL default '',
  temp_password varchar(32) NOT NULL default '',
  temp_timeout datetime NOT NULL default '0000-00-00 00:00:00',
  active int(11) NOT NULL default '1',
  name varchar(250) NOT NULL default '',
  email varchar(250) NOT NULL default '',
  email_confirmed int(11) NOT NULL default '0',
  roles varchar(250) NOT NULL default 'user',
  login_datetime datetime NOT NULL default '0000-00-00 00:00:00',
  _created_datetime datetime NOT NULL default '0000-00-00 00:00:00',
  _edited_datetime datetime NOT NULL default '0000-00-00 00:00:00',
  _created_user_id int(11) NOT NULL default '0',
  _edited_user_id int(11) NOT NULL default '0',
  PRIMARY KEY (user_id),
  KEY active(active,realm,login),
  KEY login(login),
  KEY realm(realm,login),
  KEY created_datetime(created_datetime),
  KEY active_2(active,created_datetime),
  KEY login_datetime(login_datetime),
  KEY active_3(active,login_datetime)
) TYPE=MyISAM COMMENT='Principal profiles storage (storage model &quot;db&quot;)';

#
# Дамп данных таблицы `rocket_users`
#

INSERT INTO rocket_users VALUES (1, 'guest', '', '', '', '', '0000-00-00 00:00:00', 1, 'Посетитель сайта', '', 0, 'guest', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, 0);

    