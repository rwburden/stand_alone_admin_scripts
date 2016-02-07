<?php

$ftl = array (6=>'authors',3=>'lead_item',2=>'content_type',7=>'media_type',5=>'campaign',4=>'categories',1=>'tags');
global $build_procedures_and_stranded_tables;

$build_procedures_and_stranded_tables = false;

foreach ($ftl as $key => $value)
{
	process_table('data',$key,$value);
	process_table('revision',$key,$value);
}


function process_table ($d_or_r,$key,$value)

{
$table_name = 'field_'.$d_or_r.'_field_'.$value;

global $build_procedures_and_stranded_tables;

if ($d_or_r == 'data' and $build_procedures_and_stranded_tables)
{
print "





drop procedure if exists update_delta_".$value.";

delimiter $$
create procedure update_delta_".$value." (in nid int(10) unsigned,in tid int(10) unsigned)
begin 
  set @d1 := (select delta from max_deltas as m where m.nid = nid);
  if @d1 is not null then
	update max_deltas as m set m.delta = @d1+1 where m.nid = nid;
	update stranded_ti_rows_".$value." as s set s.delta = @d1+1 where s.nid = nid and s.tid = tid;  
  else
	insert into max_deltas (nid,delta) values (nid,0);
  end if;
end $$
delimiter ;

drop procedure if exists update_deltas_".$value.";

delimiter $$
CREATE PROCEDURE update_deltas_".$value."()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE n, t INT(10) unsigned;
  DECLARE cur1 CURSOR FOR SELECT nid,tid FROM stranded_ti_rows_".$value.";
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN cur1;

  read_loop: LOOP
    FETCH cur1 INTO n, t;
    IF done THEN
      LEAVE read_loop;
    END IF;
	call update_delta_".$value."(n,t);
  END LOOP;

  CLOSE cur1;
END $$
DELIMITER ;


";

print "truncate table max_deltas;

";
print "insert into max_deltas (nid,delta)
SELECT t.nid,max(f.delta) as delta FROM ".$table_name." as f, taxonomy_index as t where f.entity_id = t.nid group by t.nid;

drop table if exists stranded_ti_rows_".$value.";
create table stranded_ti_rows_".$value."
select ti.nid,ti.tid,0 as delta from taxonomy_index as ti, taxonomy_term_data as td where td.vid = ".$key." and ti.tid = td.tid and   not exists(select * from ".$table_name." as f where f.entity_id = ti.nid and f.field_".$value."_tid = ti.tid);

call update_deltas_".$value.";

";
}

print "insert into ".$table_name." (entity_type,bundle,deleted,entity_id,revision_id,language,delta,field_".$value."_tid) select 'node' as entity_type,n.type as bundle,0 as deleted,n.nid as entity_id,n.nid as revision_id,n.language,ti.delta,ti.tid as field_".$value."_tid from node as n,stranded_ti_rows_".$value." as ti where n.nid = ti.nid;

";

}

?>