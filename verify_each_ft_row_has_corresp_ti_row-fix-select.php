<?php
$ftl = array (6=>'authors',3=>'lead_item',2=>'content_type',7=>'media_type',5=>'campaign',4=>'categories',1=>'tags');

foreach ($ftl as $key => $value)
{
	process_table('data',$key,$value);
	process_table('revision',$key,$value);
}


function process_table ($d_or_r,$key,$value)

{
$table_name = 'field_'.$d_or_r.'_field_'.$value;

$sql = "select n.nid, f.field_".$value."_tid as tid, n.sticky, n.created from ".$table_name." as f left join taxonomy_index as i on f.entity_id=i.nid and f.field_".$value."_tid = i.tid cross join node as n on f.entity_id = n.nid where i.nid is null or i.tid is null;";

print $sql."\n";
	
}

?>
