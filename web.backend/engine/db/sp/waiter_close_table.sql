-- draft
-- ©2025 Kudryashov Vasili
-- created 2025-06-07 23:00
-- last modified 2025-06-07 23:00
delimiter $$

drop procedure if exists waiter_close_table$$

create procedure waiter_close_table(f_in json, out f_out json)
proc_end:
begin
    declare fid char(36) collate latin1_general_ci default json_value(f_in, '$.f_id');
    update h_tables
    set f_locktime=null,
        f_locksrc=null
    where f_locksrc = json_value(f_in, '$.f_locksrc')
      and f_id = json_value(f_in, '$.f_table');
    insert into log(f_comp, f_date, f_time, f_user, f_invoice, f_action)
    values (json_value(f_in, '$.f_locksrc'), current_date, current_time,
            json_value(f_in, '$.f_current_staff_name'), fid, 'Ելք պատվերից');
    select coalesce(count(*), 0) into @f_count from o_body where f_header = fid;
    if (@f_count = 0) then
        delete from o_header where f_id = fid;
        insert into log(f_comp, f_date, f_time, f_user, f_invoice, f_action)
        values (json_value(f_in, '$.f_locksrc'), current_date, current_time,
                json_value(f_in, '$.f_current_staff_name'), fid, 'Դատարկ պատվերի հեռացում');
    else
        select coalesce(count(*), 0) into @f_count from o_body where f_header = fid and f_state=1;
        if (@f_count = 0) then
            update o_header set f_state=4 where f_id=fid;
            insert into log(f_comp, f_date, f_time, f_user, f_invoice, f_action)
        values (json_value(f_in, '$.f_locksrc'), current_date, current_time,
                json_value(f_in, '$.f_current_staff_name'), fid, 'Դատարկ պատվերի չեղարկում ');
        end if;
    end if;
    set f_out = json_object(
            'status', 1);
end $$