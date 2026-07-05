#18/06/2025
# applied via run_update.php v218
UPDATE s_app SET f_version='218' WHERE f_app='DB';
CREATE TABLE IF NOT EXISTS c_goods_mark (f_id INTEGER PRIMARY KEY AUTO_INCREMENT, f_name VARCHAR(64));
ALTER TABLE c_groups ADD COLUMN f_mark INT DEFAULT 0;
