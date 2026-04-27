-- draft
-- ©2025 Kudryashov Vasili
-- created 2025-06-11 12:00
-- last modified 2025-06-10 12:00

delimiter $$

drop procedure if exists waiter_add_dish$$
create procedure waiter_add_dish(f_in json, out f_out json)
sp:begin
    declare fHeader char(36) collate latin1_general_ci;
    declare menuId int;
    declare dishId int;
    declare dishMenuId int;
    declare price float;
    declare emarks tinytext;
    declare specialComment tinytext;
    declare qty1 float;
    declare guest int;
    declare row int;
    declare currentStaff int;
    set fHeader = json_value(f_in, '$.f_header');
    set menuId = json_value(f_in, '$.menuid');
    set dishMenuId = json_value(f_in, '$.f_dish_menu_id');
    set price = json_value(f_in, '$.f_price');
    set emarks = json_value(f_in, '$.f_emarks');
    set specialComment = json_value(f_in, '$.f_special_comment');
    set qty1 = json_value(f_in, '$.f_qty1');
    set guest = json_value(f_in, '$.f_quest');
    set row = json_value(f_in, '$.f_row');
    set currentStaff  = json_value(f_in, '$.f_current_staff');
    #dish info
    select f_dish, f_addbyqr
    into dishId, @f_addbyqr
                             from d_dish d
                             left join  d_menu m on m.f_dish=d.f_id
                                           where m.f_id=dishMenuId;
    #check stoplist
    select f_qty into @stopListQty from d_stoplist where f_dish=dishId;
    if (@stopListQty is not null and @stopListQty - qty1 < 0) then
        select JSON_OBJECT('status', 0, 'message', 'Stoplist reached') into f_out;
        leave sp;
    end if;

    select uuid() into @f_id;
    insert into o_body (f_id, f_header, f_dish, f_state, f_qty1, f_qty2,
                        f_price, f_service, f_discount, f_total, f_grandtotal,
                        f_store, f_print1, f_print2, f_remind, f_canservice, f_candiscount,
                        f_guest, f_row, f_fromtable,f_appendtime, f_appenduser, f_emarks)
    values (@f_id, fHeader, dishId, 1, qty1, 0,
            price,  0, 0, price, price,
            @f_store, @f_print1, @f_print2, @f_remind, @f_canservice, @f_candiscount,
            guest, row, 0, current_timestamp,
            currentStaff, emarks);

    select json_object('status', 2, 'message', 'adddish_ok') into f_out;
end $$

delimiter ;
