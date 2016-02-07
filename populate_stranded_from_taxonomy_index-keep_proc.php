<?php

$ftl = array (6=>'authors',3=>'lead_item',2=>'content_type',7=>'media_type',5=>'campaign',4=>'categories',1=>'tags');
global $build_procedures_and_stranded_tables;

$build_stranded_tables = true;

foreach ($ftl as $key => $value)
{
	process_table('data',$key,$value);
	process_table('revision',$key,$value);
}


function process_table ($d_or_r,$key,$value)

{
$table_name = 'field_'.$d_or_r.'_field_'.$value;

global $build_stranded_tables;

if ($d_or_r == 'data' and $build_stranded_tables)
{

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

/*
print "insert into ".$table_name." (entity_type,bundle,deleted,entity_id,revision_id,language,delta,field_".$value."_tid) select 'node' as entity_type,n.type as bundle,0 as deleted,n.nid as entity_id,n.nid as revision_id,n.language,ti.delta,ti.tid as field_".$value."_tid from node as n,stranded_ti_rows_".$value." as ti where n.nid = ti.nid;

";
*/

print "select 'node' as entity_type,n.type as bundle,0 as deleted,n.nid as entity_id,n.nid as revision_id,n.language,ti.delta,ti.tid as field_".$value."_tid from node as n,stranded_ti_rows_".$value." as ti where n.nid = ti.nid;

";

}

?>