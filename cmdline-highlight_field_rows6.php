<?php

include 'cmdline-settings.inc';
/*
$pdo = new PDO('mysql:host=localhost;dbname=DBNAME', 'USERNAME', 'PASSWORD');
$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
*/

$begin_row = 1;
$end_row = 0;
$gen_repair_batch = false;

if (count($argv) < 4)
{print "<h2>Usage: php $argv[0] begin_row  end_row gen_repair_batch<br />Using defaults: 1 0 false (print no rows, do not generate repair sql batch file)</h2>\n";}
else
{
	$begin_row = $argv[1];
	$end_row = $argv[2];
	$grb = mb_convert_case($argv[3],MB_CASE_LOWER);
	switch ($grb)
	{
		case "true":
		case "yes":
		case "t":
		case "y": $gen_repair_batch = true;
		break;
	}
}

print_filter( '<p style="font-style:italic; font-size:120%">Rows in the field tables for the vocabularies in which either the nodes or the terms referenced do not exist, or both exist but the term is not in the relevant vocabulary.</p>
<p>For each table, an SQL query is echoed then executed to select candidates for deletion or a move to a different field table, with the text of the query output followed by
the number of rows to be moved or deleted.</p>
<p>If the number of rows output is in the range selected by the command line parameters, next will appear the rows that are candidates to be moved in yellow, each followed by a query to find whether a matching row(s) already exists in the destination, followed by a dark green table of matches if there are any.  If any of these rows are "irregular", they will be colored red with bold yellow text.</p>
<p>Next are the light green rows that are to be moved, each followed by the query that will move them.  None of these rows are ever "irregular".</p>
<p>Finally, hot pink rows to be deleted.  If any are "irregular", they will be colored red with white bold text.</p>
<p>In the following cases, the row should be deleted:</p>
<ul>
<li>the vocabulary id is NULL -- irregular</li>
<li>The entity_type is not "node"  -- irregular (taxonomy terms are only applied to nodes.  Other tables handle hierarchies in the taxonomy terms.)</li>
<li>Node referenced by the entity_id does not exist in the node table</li>
<li>term referenced does not exist in the taxonomy_term_data table</li>
<li>The term is not in the relevant vocabulary (has wrong vid, or vocabulary id, in the taxonomy_vocabulary table).
</li>
</ul>
<p>In the first two cases, the rows will be colored red with white text.  These rows are to be removed, but are also irregular and may merit further investigation.</p> 
<p>In cases 3-4, the rows will be colored pink. These rows are to be removed.</p> 
<p>In case 5, the rows will be colored yellow, to indicate that they are candidates
to be moved to a different field table, because it is not yet known whether they already exist in the other field table pertaining to their vocabulary</p>
<p>In case 5, if the node - term is found in the other field table, a new table is output with dark-green background and white text to show the matching row(s), following the query used to find the matches.  Only if no such matches are found will SQL be generated and output to insert this row into the other field table; in all cases, SQL will be generated to delete the row from the wrong field table for its vid.
</p>'."\n");


$ftl = array (6=>'authors',3=>'lead_item',2=>'content_type',7=>'media_type',5=>'campaign',4=>'categories',1=>'tags');
$deltas = array();

$rows_output = 0;

foreach ($ftl as $key => $value)
{
	process_table('data',$key,$value);
	process_table('revision',$key,$value);
}

function get_field_table_name($vid,$d_or_r)
{
	global $ftl;

	$suffix = $ftl[$vid];
	$table_name = 'field_'.$d_or_r.'_field_'.$suffix;
	return $table_name;
}
function get_tid_field_name ($vid)
{
	global $ftl;

	$tfn = $ftl[$vid];
	return $tfn;
}
function find_row_in_other_field_table($row,$d_or_r)
{
	$table_name = get_field_table_name($row['vocab_id'],$d_or_r);
	$tid_field_name = get_tid_field_name($row['vocab_id']);
	$sql = "select * from ".$table_name." where entity_id = ".$row['entity_id']." and 
	entity_type = \"".$row['entity_type']."\" and 
	field_".$tid_field_name."_tid = ".$row['term_id'];
	
	print_filter ( "\n\n<br />".$sql."<br />\n\n","matches");
	
	global $pdo;
	
	$statement = $pdo->query($sql);
	$matches = array();
		while ($match = $statement->fetch(PDO::FETCH_ASSOC))
		{	
			$matches[] = $match;
		}
	print_filter( count($matches)." matches found.<br />\n\n","matches");
	return $matches;
}

function get_max_delta_plus_one($table_name,$entity_id)
{
	global $deltas;
	#save the results of previous queries in the $deltas array.  If the query has run before, 
	#get the result from the $deltas array, not the DB, because this script does not increment
	#the value in the DB.
	#We therefore need to increment the delta value for each new SQL command that this script
	#generates for the same table and entity_id.  The value obtained from 
	#the DB is only our starting point for each table and entity_id.
	if (isset($deltas[$table_name][$entity_id]))
	{
		$deltas[$table_name][$entity_id]++;
		return $deltas[$table_name][$entity_id];
	}
	$sql = "select max(delta) as m from ".$table_name." where entity_id = ".$entity_id;
	global $pdo;
	$statement = $pdo->query($sql);
	$max_delta = 0;
	$row = $statement->fetch(PDO::FETCH_ASSOC);
	$max_delta = $row['m'];
    if (is_null($max_delta)) 
	{
		$deltas[$table_name][$entity_id] = 0;
		return 0;
	}  /* if entity_id not found, return 0 */
	$max_delta++;
	$deltas[$table_name][$entity_id] = $max_delta;
	return $max_delta;
}

function print_filter ($str,$msg_type = "")
{
	/* msg types include "repair_sql","table","match table", "" */
	global $begin_row,$end_row,$rows_output,$gen_repair_batch;
	
	if ($gen_repair_batch === false)
	{
		if ($msg_type == "") /* used for low-volume usage info and overall stats. */
		{
			print $str;
		}
		/* print high-volume info only if the row is in the selected range  */
		else if ($rows_output >= $begin_row and $rows_output <= $end_row)
		{
			print $str;
		}
	}
	/* $gen_repair_batch !== false
		generate a MySQL batch file to repair the tables.  
		Exclude all output that is not MySQL commands designed to do that */
	else if ($msg_type == "repair_sql")
	{
		/* remove HTML tags */
		$str = str_replace(array('<p>','</p>'),array('',''),$str);
		print $str;
	}
}

function process_table ($d_or_r,$key,$value)

{
$table_name = 'field_'.$d_or_r.'_field_'.$value;

print_filter( "<h2>".$table_name."</h2>\n");

$sql = "select f.entity_type, f.bundle, f.entity_id, f.revision_id, f.deleted, f.language, f.field_".$value."_tid as term_id, d.name as term_name, v.vid as vocab_id,v.name as vocab_name,n.title as node_title, n.nid from ".$table_name." as f left join taxonomy_term_data as d on f.field_".$value."_tid = d.tid left join taxonomy_vocabulary as v on d.vid = v.vid left join node as n on f.entity_id = n.nid where n.nid is null or v.vid is null or d.tid is null or v.vid != $key";

print_filter( "<p>$sql;</p>\n");
/* $col_width = array('entity_type' => '6%', 'entity_id' => '6%', 'revision_id' => '6%','deleted' => '4%', 'field_'.$value.'_tid' => '6%', 'vocab_name' => '9%', 'term_name' => '13%','title' => '50%');  */

global $pdo,$rows_output, $begin_row, $end_row;;

$statement = $pdo->query($sql);
$rows_to_delete = array();
$rows_to_move = array();
$rows_candidates_to_move = array();

$first_row = true;
$count = 0;

//if ($statement !== false)
//{
while ($row = $statement->fetch(PDO::FETCH_ASSOC))
{
$count++;

if ($row['entity_type'] != 'node' or is_null($row['vocab_id']) or is_null($row['nid'])  or is_null($row['term_id']))  /* delete the row */
{
	$rows_to_delete[] = $row;
}
else if ($row['vocab_id'] != $key) 
{
	$rows_candidates_to_move[] = $row;
}
}
$delete_count = count($rows_to_delete);
$candidate_count = count($rows_candidates_to_move);
$unaccounted_count = $count - ($delete_count + $candidate_count);
print_filter( "<p>First row: ".$rows_output."<br />\n");
print_filter( "A: Rows candidates to be moved: ".$candidate_count."<br />\n");
print_filter( "B: Rows to be deleted so far: ".$delete_count."<br />\n");
print_filter( "C: Rows in results of query: $count</br>\n");
print_filter( "C - (A + B): ".$unaccounted_count."</p>\n");
/* done with first pass of table.  Release the cursor to allow more
queries */
$statement->closeCursor();


foreach ($rows_candidates_to_move as $crow)
{
	$rows_output++;
	if ($crow['entity_id'] != $crow['revision_id'] or $crow['deleted'] != 0)
	/* special cases, color deep red with yellow text */
	{$row_highlight = ' style="background-color:red; color:yellow; font-weight:bold"';}
	else /* candidate to move, color yellow */
	{$row_highlight = ' style="background-color:yellow"';}
	print_filter( "<table>\n<tr>","table");
	foreach ($crow as $col_name => $column)
	{print_filter( '<th '.$row_highlight.'>'.$col_name.'</th>',"table");}
	print_filter( '</tr>'."\n".'<tr>',"table");
	foreach ($crow as $col_name => $column)
	{print_filter( '<td '.$row_highlight.'>'.$column.'</td>',"table");}
	print_filter( "</tr>\n</table>\n","table");
	$matches = find_row_in_other_field_table($crow,$d_or_r);
	if (! empty($matches)) /* delete the row */
	{
		$rows_to_delete[] = $crow;
		foreach ($matches as $match)
		{
			print_filter('<table style="background-color:#006600; color:white">',"match table");
			$first_row = true;
			if ($first_row)
			{
				print_filter( '<tr>',"match table");
				foreach ($match as $col_name => $column)
				{print_filter( '<th>'.$col_name.'</th>',"match table");}
				$first_row = false;
				print_filter( '</tr>',"match table");
			}
			print_filter( '<tr>');
			foreach ($match as $col_name => $column)
			{
				if (is_null($column)) {$column = "NULL";}
				print_filter( '<td>'.$column.'</td>',"match table");
			}
			print_filter( '</table>'."\n","match table");
		}
	}
	else
	{
		$rows_to_move[] = $crow;
	}
}
$delete_count = count($rows_to_delete);
$move_count = count($rows_to_move);
$unaccounted_count = $count - ($delete_count + $move_count);
print_filter( "<p>finished processing candidates for ".$table_name."<br />\n");
print_filter( "First row: ".$rows_output."<br />\n");
print_filter( "A: Rows to be moved: ".$move_count."<br />\n");
print_filter( "B: Rows to be deleted: ".$delete_count."<br />\n");
print_filter( "C: Rows in results of query: $count</br>\n");
print_filter( "C - (A + B): ".$unaccounted_count."</p>\n");


foreach ($rows_to_move as $mrow)
{
	$rows_output++;
	if ($mrow['entity_id'] != $mrow['revision_id'] or $mrow['deleted'] != 0 )
	/* special cases, color deep red with yellow text */
	{$row_highlight = ' style="background-color:red; color:yellow; font-weight:bold"';}
	else /* move, color light green */
	{$row_highlight = ' style="background-color:#99FF99"';}
	print_filter( "<table>\n<tr>","table");
	foreach ($mrow as $col_name => $column)
	{print_filter( '<th '.$row_highlight.'>'.$col_name.'</th>',"table");}
	print_filter( '</tr>'."\n".'<tr>',"table");
	foreach ($mrow as $col_name => $column)
	{print_filter( '<td '.$row_highlight.'>'.$column.'</td>',"table");}
	print_filter( "</tr>\n</table>\n","table");
	print_filter( "\n<p>delete from ".$table_name." where entity_type = \"".$mrow['entity_type'].
	"\" and entity_id = ".$mrow['entity_id'].
	" and field_".$value."_tid = ".$mrow['term_id'].";</p>\n","repair_sql");
	
	$new_table_name = get_field_table_name($mrow['vocab_id'],$d_or_r);
	$new_value = get_tid_field_name ($mrow['vocab_id']);
	$delta = get_max_delta_plus_one($new_table_name,$mrow['entity_id']);
	
	print_filter( "<p>insert into ".$new_table_name." (bundle,deleted,delta,entity_id,entity_type,field_".$new_value."_tid,language,revision_id) values (\"".$mrow['bundle']."\",". $mrow['deleted'].",".$delta.",".$mrow['entity_id'].",\"".$mrow['entity_type']."\",".$mrow['term_id'].",\"".$mrow['language']."\",".$mrow['revision_id'].");</p>","repair_sql");
} 

foreach ($rows_to_delete as $drow)
{
	$rows_output++;
	if ($drow['entity_id'] != $drow['revision_id'] or $drow['deleted'] != 0 or is_null($drow['vocab_id']))
	/* special cases, color deep red with white text */
	{$row_highlight = ' style="background-color:red; color:white; font-weight:bold"';}
	else /* delete, color pink */
	{$row_highlight = ' style="background-color:#FF9999"';}
	print_filter( "<table>\n<tr>","table");
	foreach ($drow as $col_name => $column)
	{print_filter( '<th '.$row_highlight.'>'.$col_name.'</th>',"table");}
	print_filter( '</tr>'."\n".'<tr>',"table");
	foreach ($drow as $col_name => $column)
	{print_filter( '<td '.$row_highlight.'>'.$column.'</td>',"table");}
	print_filter( "</tr>\n</table>\n","table");
	print_filter( "<p>delete from ".$table_name." where entity_type = \"".$drow['entity_type'].
	"\" and entity_id = ".$drow['entity_id'].
	" and field_".$value."_tid = ".$drow['term_id'].";</p>\n","repair_sql");
} 
}
print_filter ("<p><strong>Rows output:</strong> ".$rows_output."</p>\n","");
?>
