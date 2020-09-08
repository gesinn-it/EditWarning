ALTER TABLE editwarning_locks
  ADD CONSTRAINT editwarning_locks_ibfk_1 FOREIGN KEY (user_id) REFERENCES user (user_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT editwarning_locks_ibfk_2 FOREIGN KEY (article_id) REFERENCES page (page_id) ON DELETE CASCADE ON UPDATE CASCADE;