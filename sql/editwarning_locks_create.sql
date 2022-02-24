CREATE TABLE /*_*/editwarning_locks (
  user_id int(10) unsigned NOT NULL,
  user_name varchar(255) NOT NULL,
  article_id int(10) unsigned NOT NULL,
  lock_timestamp int(11) unsigned NOT NULL,
  section int(2) unsigned NOT NULL
);

CREATE INDEX editwarning_locks_user_id ON /*_*/editwarning_locks (user_id);
CREATE INDEX editwarning_locks_article_id ON /*_*/editwarning_locks (article_id);
