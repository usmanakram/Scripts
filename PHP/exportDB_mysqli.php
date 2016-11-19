<?php

// backup_tables('DB_HOST', 'DB_USERNAME', 'DB_PASSWORD', 'DB_NAME');
backup_tables('localhost', 'root', '', 'butterfly');

/* backup the db OR just a table */
function backup_tables($host, $user, $pass, $name, $tables = '*')
{
	$link = mysqli_connect($host, $user, $pass, $name) or die('Error ' . mysqli_error($link));
	
	//get all of the tables
	if($tables == '*')
	{
		$tables = array();
		$result = $link->query('SHOW TABLES') or die('Error in the consult..' . mysqli_error($link));
		while($row = $result->fetch_assoc())
		{
			$tables[] = $row[key($row)];
		}
	}
	else
	{
		$tables = is_array($tables) ? $tables : explode(',', $tables);
	}
	
	$return = '';
	//cycle through
	foreach($tables as $table)
	{
		$fields = array();
		$fields_result = $link->query('SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`="' . $name . '" AND `TABLE_NAME`="' . $table . '";');
		while($row = $fields_result->fetch_assoc())
		{
			$fields[] = $row[key($row)];
		}

		//$return.= 'DROP TABLE '.$table.';';
		$row2 = $link->query('SHOW CREATE TABLE ' . $table)->fetch_assoc();
		
		$return .= "\n\n" . $row2['Create Table'] . ";\n\n";
		
		$result = $link->query('SELECT * FROM ' . $table);
		while($row = $result->fetch_assoc())
		{
			$return .= 'INSERT INTO ' . $table . ' VALUES(';
			foreach ($fields as $field) 
			{
				if($row[$field] === null) {
					$return .= 'NULL';
				} else {
					$row[$field] = addslashes($row[$field]);
					$row[$field] = ereg_replace("\n", "\\n", $row[$field]);
					if (isset($row[$field])) { $return .= '"' . $row[$field] . '"' ; } else { $return .= '""'; }
				}
				$return .= ',';
			}
			$return = rtrim($return, ',');
			$return .= ");\n";
		}
		$return .= "\n\n\n";
	}
	
	//save file
	$handle = fopen('db-backup-' . time() . '-' . (md5(implode(',', $tables))) . '.sql', 'w+');
	fwrite($handle, $return);
	fclose($handle);
}

?>