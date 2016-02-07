<?php
include 'cmdline-settings.inc';

$ftl = array (6=>'authors',3=>'lead_item',2=>'content_type',7=>'media_type',5=>'campaign',4=>'categories',1=>'tags');

foreach ($ftl as $key => $value)
{
	process_table('data',$key,$value);
	process_table('revision',$key,$value);
}


function process_table ($d_or_r,$key,$value)

{
$table_name = 'field_'.$d_or_r.'_field_'.$value;

global $pdo;

$sql = "select n.nid, n.title, d.tid, d.vid, d.name from ".$table_name." as f left join taxonomy_index as i on f.entity_id=i.nid and f.field_".$value."_tid = i.tid left join taxonomy_term_data as d on f.field_".$value."_tid = d.tid cross join node as n on f.entity_id = n.nid where i.nid is null or i.tid is null";

print "<p>".$sql."</p>\n";
$statement = $pdo->query($sql);
print "<table>\n";
			$first_row = true;
		while ($match = $statement->fetch(PDO::FETCH_ASSOC))
		{	
			if ($first_row)
			{	
			print "<tr>";
			foreach ($match as $col_name => $column)
			{
				print "<th>".$col_name."</th>";
 			}
			$first_row = false;
			print "</tr>\n";
			}
			foreach ($match as $col_name => $column)
			{
				print "<td>".$column."</td>";
 			}
			print "</tr>\n";
		}
	print "</table>\n";	
	
	
$sql = "select f.entity_id, d.tid, d.vid, d.name from ".$table_name." as f left join taxonomy_term_data as d on f.field_".$value."_tid = d.tid left join node as n on f.entity_id = n.nid where n.nid is null or d.tid is null;";

print "<p>".$sql."</p>\n";
$statement = $pdo->query($sql);
print "<table>\n";
			$first_row = true;
		while ($match = $statement->fetch(PDO::FETCH_ASSOC))
		{	
			if ($first_row)
			{	
			print "<tr>";
			foreach ($match as $col_name => $column)
			{
				print "<th>".$col_name."</th>";
 			}
			$first_row = false;
			print "</tr>\n";
			}
			foreach ($match as $col_name => $column)
			{
				print "<td>".$column."</td>";
 			}
			print "</tr>\n";
		}
	print "</table>\n";		
	
}

?>
