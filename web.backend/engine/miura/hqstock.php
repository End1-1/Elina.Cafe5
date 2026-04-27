<?php
require_once __DIR__ . "/miura.php";

int stock = fJsonIn[pkStock].toInt();
    int group = fJsonIn[pkGroup].toInt();
    QString sql =
        "select s.f_goods as goodsid, gr.f_name as groupname, g.f_name as goodsname, sum(s.f_qty*s.f_type) as qty "
        "from a_store s "
        "left join c_goods g on g.f_id=s.f_goods "
        "left join c_groups gr on gr.f_id=g.f_group "
        "left join c_storages st on st.f_id=s.f_store ";
    QString where = "where g.f_enabled=1 and st.f_state=1 ";
    if (stock > 0) {
        where += QString(" and s.f_store=%1 ").arg(stock);
    }
    if (group > 0) {
        where += QString(" and g.f_group=%1").arg(group);
    }
    sql += where;
    sql += " group by g.f_name";
    if (!fDb.exec(sql)) {
        return dbFail();
    }
    QJsonArray ja;
    dbToArray(fDb, ja);
    fJsonOut[pkData] = ja;
    fJsonOut[pkOk] = 1;
    return true;