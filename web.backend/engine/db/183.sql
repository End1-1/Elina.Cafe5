#13/07/2026
# applied via run_update.php v219
UPDATE s_app SET f_version='219' WHERE f_app='DB';
ALTER TABLE o_draft_sale_body MODIFY f_emarks VARCHAR(128) NULL DEFAULT NULL;
