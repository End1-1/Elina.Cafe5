-- draft
-- 06/06/2025
delimiter $$
drop procedure if exists store_decompilation$$
create procedure store_decompilation(in p_in json, out p_out json)
begin
    declare aid char(36) collate latin1_general_ci;
end$$

delimiter ;