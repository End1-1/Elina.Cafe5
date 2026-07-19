<?php
# (C) 2026 Kudryashov Vasili
# Handlers registry for named C5 reports (migrated from reports_handler table).
# Key = handler UUID (kept for backward compatibility with FrontDesk logic).
# Value = SQL / filter definition (same content as reports_handler.f_query).

return [
    // Delete handler for draft sales report (id=2).
    "67edc699-93e5-11ed-bf0e-1078d2d2b808" =>
        "UPDATE o_draft_sale SET f_state=4 WHERE f_id='%handler%';\n" .
        "UPDATE o_draft_sale_body SET f_state=4 WHERE f_header='%handler%';",

    // Filter handler for sale/income report (id=4): field descriptors "cacheId-fieldname".
    "0baed59d-cc9c-11ed-8a65-1078d2d2b808" =>
        "9-ah.f_partner,28-oh.f_hall",

    // Filter handler for manager salary report (id=6): empty in DB.
    "f2051597-852d-11ee-8b5c-1078d2d2b808" => null,
];
