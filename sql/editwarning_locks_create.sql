CREATE TABLE /*_*/editwarning_locks (
  user_id int(10) unsigned NOT NULL,
  user_name varchar(255) NOT NULL,
  article_id int(10) unsigned NOT NULL,
  timestamp int(11) unsigned NOT NULL,
  section int(2) unsigned NOT NULL,
  KEY user_id (user_id),
  KEY article_id (article_id)
);