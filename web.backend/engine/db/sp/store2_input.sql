DROP FUNCTION IF EXISTS sf_store2_input;
DELIMITER $$
CREATE FUNCTION sf_store2_input(params longtext)
    RETURNS longtext
BEGIN
    DECLARE status int DEFAULT 0;
    DECLARE current_version int DEFAULT 0;
    DECLARE current_status int DEFAULT 0;
    DECLARE doc_date datetime DEFAULT JSON_VALUE(params, '$.doc_date');
    DECLARE new_status int DEFAULT JSON_VALUE(params, '$.doc_status');
    DECLARE store_in int DEFAULT JSON_VALUE(params, '$.doc_store_in');
    DECLARE store_out int DEFAULT JSON_VALUE(params, '$.doc_store_out');
    DECLARE doc_uuid char(36) COLLATE latin1_general_ci DEFAULT JSON_VALUE(params, '$.doc_uuid');
    DECLARE user_id char(8) DEFAULT JSON_VALUE(params, '$.doc_user_id');
    SELECT f_version, f_status
    INTO current_version, current_status
    FROM picassodev.store_document
    WHERE f_id = doc_uuid FOR
    UPDATE;
    IF (current_version IS NOT NULL) THEN
        /* first check can make draft */
        IF (current_status = 1 AND new_status = 0) THEN
            SELECT 1 INTO @check FROM store_stock WHERE f_doc = doc_uuid AND f_qty_left < f_qty_in;
            IF EXISTS (SELECT 1 FROM store_stock WHERE f_doc = doc_uuid AND f_qty_left < f_qty_in) THEN
                SELECT 2 INTO status;
                RETURN JSON_COMPACT(JSON_OBJECT('status', CAST(status AS int)));
            END IF;
        END IF;
        IF (current_version <> CAST(JSON_VALUE(params, '$.doc_version') AS int)) THEN
            /* doc changed somewhere */
            SELECT 1 INTO status;
            RETURN JSON_COMPACT(JSON_OBJECT('status', CAST(status AS int)));
        END IF;
    END IF;
    INSERT INTO store_document (f_id, f_user_id, f_status, f_doc_type, f_doc_date, f_store_in, f_store_out, f_sum,
                                f_partner, f_version, f_data)
    VALUES (doc_uuid,
            user_id,
            JSON_VALUE(params, '$.doc_status'),
            JSON_VALUE(params, '$.doc_type'),
            doc_date,
            store_in,
            store_out,
            JSON_VALUE(params, '$.doc_sum'),
            JSON_VALUE(params, '$.doc_partner'),
            JSON_VALUE(params, '$.doc_version') + 1,
            JSON_EXTRACT(params, '$.doc_data'))
    ON DUPLICATE KEY UPDATE f_status    = VALUES(f_status),
                            f_doc_type  = VALUES(f_doc_type),
                            f_doc_date  = VALUES(f_doc_date),
                            f_store_in  = VALUES(f_store_in),
                            f_store_out = VALUES(f_store_out),
                            f_sum       = VALUES(f_sum),
                            f_partner   = VALUES(f_partner),
                            f_version   = JSON_VALUE(params, '$.doc_version') + 1,
                            f_data      = VALUES(f_data);
    /* user data */
    DELETE FROM store_user WHERE f_doc = doc_uuid;
    -- 2. Массово заливаем новые строки через JSON_TABLE
    INSERT INTO store_user (f_id, f_doc, f_item_id, f_qty, f_price, f_total, f_comment)
    SELECT jt.row_uuid,
           doc_uuid,
           jt.item_id,
           jt.qty,
           jt.price,
           jt.qty * jt.price,
           jt.comment
    FROM JSON_TABLE(
                 params,
                 '$.items[*]' -- путь к массиву товаров в твоем JSON
                 COLUMNS (
                     row_uuid CHAR(36) PATH '$.id', -- UUID конкретной строки из фронта
                     item_id INT PATH '$.item_id',
                     qty DECIMAL(14, 4) PATH '$.qty',
                     price DECIMAL(14, 2) PATH '$.price',
                     comment varchar(255) PATH '$.comment'
                     )
         ) AS jt;
    DELETE FROM store_stock WHERE f_doc = doc_uuid;
    DELETE FROM store_moves WHERE f_doc = doc_uuid;
    IF (new_status = 1) THEN
-- Запись в store_stock (формирование партий)
        INSERT INTO store_stock (f_id, f_doc, f_doc_row_id, f_batch_date, f_expiry_date, f_store_id, f_item_id,
                                 f_qty_in, f_qty_left, f_price)
        SELECT jt.row_uuid,
               doc_uuid,
               jt.row_uuid,
               doc_date,
               jt.expire_date,
               store_in,
               jt.item_id,
               jt.qty,
               jt.qty,
               jt.price
        FROM JSON_TABLE(params, '$.items[*]'
                        COLUMNS (
                            row_uuid CHAR(36) PATH '$.id',
                            item_id INT PATH '$.item_id',
                            qty DECIMAL(14, 4) PATH '$.qty',
                            price DECIMAL(14, 2) PATH '$.price',
                            expire_date DATETIME PATH '$.expire_date'
                            )
             ) AS jt;

        -- Запись в store_moves (лог движений)
        INSERT INTO store_moves (f_id, f_doc, f_doc_row_id, f_batch_id, f_store_id, f_item_id, f_qty_in, f_price,
                                 f_total)
        SELECT UUID(),
               doc_uuid,
               jt.row_uuid,
               jt.row_uuid,
               store_in,
               jt.item_id,
               jt.qty,
               jt.price,
               jt.qty * jt.price
        FROM JSON_TABLE(params, '$.items[*]'
                        COLUMNS (
                            row_uuid CHAR(36) PATH '$.id',
                            item_id INT PATH '$.item_id',
                            qty DECIMAL(14, 4) PATH '$.qty',
                            price DECIMAL(14, 2) PATH '$.price'
                            )
             ) AS jt;
    END IF;
    /* all ok */
    RETURN JSON_COMPACT(JSON_OBJECT('status', CAST(status AS int)));
END$$
DELIMITER ;