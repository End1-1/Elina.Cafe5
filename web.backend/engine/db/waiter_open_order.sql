-- draft
-- ©2025 Kudryashov Vasili
-- created 2025-06-11 11:00
-- last modified 2025-06-10 11:00
delimiter $$
drop procedure if exists waiter_open_order$$
create procedure waiter_open_order(fid char(36) COLLATE latin1_general_ci, out f_header json, out f_body json)
begin
select json_object('f_id', o.f_id,
                       'f_staff', o.f_staff,
    'f_table', o.f_table,
    'f_servicefactor', o.f_servicefactor,
    'f_discountfactor', o.f_discountfactor,
                       'f_staff_name', concat_ws(' ', u1.f_last, u1.f_first))
    into f_header
    from o_header o
             left join s_user u1 on u1.f_id = o.f_cashier
    where o.f_id = fid;

    select json_arrayagg(json_object(
                   'f_id', b.f_id,
                   'f_state', b.f_state,
                   'f_dish', b.f_dish,
                   'f_dish_name', d.f_name,
                   'f_qty1', b.f_qty1,
                   'f_qty2', b.f_qty2,
                   'f_price', b.f_price,
                   'f_comment', b.f_comment,
                   'f_comment2', b.f_comment2,
                   'f_guest', b.f_guest,
                         'f_fromtable', b.f_fromtable,
                         'f_emarks', b.f_emarks,
                         'f_printtime', b.f_printtime,
                         'f_appendtime', b.f_appendtime,
                         'f_removetime', b.f_removetime
           ) ) into f_body

    from o_body b
             inner join d_dish d on d.f_id = b.f_dish
    where b.f_header = fid
    order by b.f_guest, b.f_appendtime;
end $$
delimiter ;