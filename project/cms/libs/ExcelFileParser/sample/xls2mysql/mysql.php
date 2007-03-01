<html>
<head>
<STYLE>
<!--
body, table, tr, td {font-size: 12px; font-family: Verdana, MS sans serif, Arial, Helvetica, sans-serif}
td.index {font-size: 10px; color: #000000; font-weight: bold}
td.empty {font-size: 10px; color: #000000; font-weight: bold}
td.dt_string {font-size: 10px; color: #000090; font-weight: bold}
td.dt_int {font-size: 10px; color: #909000; font-weight: bold}
td.dt_float {font-size: 10px; color: #007000; font-weight: bold}
td.dt_unknown {font-size: 10px; background-color: #f0d0d0; font-weight: bold}
td.empty {font-size: 10px; background-color: #f0f0f0; font-weight: bold}
-->
</STYLE>
</head>
<body bgcolor="#ffffff" text="#000000" topmargin="0" leftmargin="10" marginwidth="0" marginheight="0" link="#000000" vlink="#000000" alink="#000000">

<table width="100%" bgcolor="#006699">
<tr>
	<td>&nbsp;</td>
	<td><font size="+3" color="#FFFFFF">ABC Excel Parser Pro</font></td>
	<td>&nbsp;</td>
</tr>
</table>

<p>&nbsp;</p>

<?php

	require "../../excelparser.php";

	function print_error( $msg )
	{
		print <<<END
		<tr>
			<td colspan=5><font color=red><b>Error: </b></font>$msg</td>
			<td><font color=red><b>Rejected</b></font></td>
		</tr>

END;
	}

	function uc2html($str) {
		$ret = '';
		for( $i=0; $i<strlen($str)/2; $i++ ) {
			$charcode = ord($str[$i*2])+256*ord($str[$i*2+1]);
			$ret .= '&#'.$charcode;
		}
		return $ret;
	}

	function get( $exc, $data )
	{
		switch( $data['type'] )
		{
			// string
		case 0:
			$ind = $data['data'];
			if( $exc->sst[unicode][$ind] )
				return uc2html($exc->sst['data'][$ind]);
			else
				return $exc->sst['data'][$ind];

			// integer
		case 1:
			return (integer) $data['data'];

			// float
		case 2:
			return (float) $data['data'];
                case 3:
			return gmdate("m-d-Y",$exc->xls2tstamp($data[data]));

		default:
			return '';
		}
	}

	function fatal($msg = '') {
		echo '[Fatal error]';
		if( strlen($msg) > 0 )
			echo ": $msg";
		echo "<br>\nScript terminated<br>\n";
		if( $f_opened) @fclose($fh);
		exit();
	};

	$err_corr = "Unsupported format or file corrupted";

	$excel_file_size;
	$excel_file = $_FILES['excel_file'];
	if( $excel_file )
		$excel_file = $_FILES['excel_file']['tmp_name'];

	if( $excel_file == '' ) fatal("No file uploaded");

	$fh = @fopen ($excel_file,'rb');
	if( !$fh ) fatal("No file uploaded");
	if( filesize($excel_file)==0 ) fatal("No file uploaded");

	$fc = fread( $fh, filesize($excel_file) );
	@fclose($fh);
	if( strlen($fc) < filesize($excel_file) )
		fatal("Cannot read file");

	$exc = new ExcelFileParser;
	//if( $exc->ParseFromFile($excel_file)>0 ) fatal($err_corr);
	$res = $exc->ParseFromString($fc);
	switch ($res) {
		case 0: break;
		case 1: fatal("Can't open file");
		case 2: fatal("File too small to be an Excel file");
		case 3: fatal("Error reading file header");
		case 4: fatal("Error reading file");
		case 5: fatal("This is not an Excel file or file stored in Excel < 5.0");
		case 6: fatal("File corrupted");
		case 7: fatal("No Excel data found in file");
		case 8: fatal("Unsupported file version");

		default:
			fatal("Unknown error");
	}

	if( count($exc->worksheet['name']) < 1 ) fatal("No worksheets in Excel file.");

	//
	// Process only first worksheet

	print "<b>Worksheet: \"";
		if( $exc->worksheet['unicode'][0] )
		{
			print uc2html($exc->worksheet['name'][0]);
		}
		else
			print $exc->worksheet['name'][0];
	print "\"</b><br><br>";

	//
	// Obtain worksheet data

	$ws = $exc->worksheet['data'][0];

	/****** DEBUG STUFF*
		print '<pre>';
		print_r($exc->worksheet);
		print '</pre>';
	*/

	//
	// Process

	if( is_array($ws) &&
	    isset($ws['max_row']) &&
	    isset($ws['max_col']) )
	{

		//
		// Validate number of rows and cols

		if( $ws['max_col'] < 3 ) fatal("Invalid format.<br>Number of columns is less then 3.");
		if( $ws['max_row'] == 0 ) fatal("Invalid format.<br>No rows defined in document.");

		//
		// Iterate rows

		$data = $ws['cell'];

		$items = array();

		print "<br><b>Receiving data:</b><br>";
		print "<table border=1>\n";

		foreach( $data as $i => $row )
		{
			////////////////////////////////////////////////////////////////////////////
			// $i now contains row index.
			// $row - row data
			//
			// Note: You should use foreach or language construction
			// 		like this to iterate rows, because if excel file contains
			// 		only 2 rows with indexes 0 and 100, then $data will be equal to
			// 		array( 0 => data1, 100 => data2 ).
			////////////////////////////////////////////////////////////////////////////

			/****** DEBUG STUFF
				print '<pre>';
				print_r($row);
				print '</pre>';
			*/

			// this counter is for information only.
			// so adjust it to be 1 - based.
			$i++;

			//
			// Check the row has valid format

			if( !is_array( $row ) )
			{
				print_error("Row $i is of invalid format.");
				continue;
			}

			if( count( $row ) < 3 )
			{
				print_error("Row $i has less then 3 columns.");
				continue;
			}

			$valid = true;

			for( $col = 0; $col < 3; $col++ )
				if( !is_array( $row[$col] ) )
				{
					print_error("Column $col in row $i is of invalid format.");
					$valid = false;
					break;
				}
			if( !$valid ) continue;

			//
			// Fetch data

			$name = get( $exc, $row[0] );
			$spec = get( $exc, $row[1] );
			$price = get( $exc, $row[2] );
                        $date  = get( $exc, $row[3] );

			//
			// Validate data

			if( !is_numeric( $price ) )
			{
				print_error("Row $i is of invalid format.");
				continue;
			}

			print <<<END
			<tr>
				<td>$name</td>
				<td>$spec</td>
				<td>$price</td>
				<td>$retail_price</td>
				<td>$date</td>
				<td><font color=blue><b>Accepted</b></font></td>
			</tr>

END;

			//
			// Store data

			$cur = count( $items );
			$items[ $cur ]['name'] = $name;
			$items[ $cur ]['spec'] = $spec;
			$items[ $cur ]['price'] = $price;
		}

		print "</table>\n";
	}

	//
	// Write $items array to the MySQL table.

	print "<p><b>Insert data into the database.... </b>";

	if( count( $items ) == 0 )
		fatal('No data to import into the MySQL table.');
	else
	{
		//
		// Connect to the database

		if( !( $link = mysql_connect() ) )
			fatal("Could not connect to local MySQL server.");

		if( !mysql_select_db( "ParserSample", $link ) )
		{
			mysql_close( $link );
			fatal("Could not select database <b>ParserSample</b>.");
		}

		//
		// Prepare query

		$comma = '';

		$query = "INSERT INTO SampleData ( name, specification, price ) VALUES ";
		foreach( $items as $item )
		{
			$name = addslashes( $item['name'] );
			$spec = addslashes( $item['spec'] );
			$price = (float) $item['price'];

			$value = sprintf(" %s(\"%s\",\"%s\",%f)",
				$comma, $name, $spec, $price );

			if( $comma == '' )
				$comma = ',';

			$query .= $value;
		}

		$query .= ';';

		//
		// Execute SQL query

		$result = mysql_query( $query, $link );
		$num = mysql_affected_rows( $link );

		print "<b>$num rows successfully inserted.</b><br>";

		//
		// Close connection

		mysql_close( $link );
	}
?>

<p>&nbsp;</p>
<p align="right">
<a href="http://www.zakkis.ca" style="font-size: 9px; text-decoration: none; font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;">ZAKKIS Tech. 2002  All Rights Reserved.</a>&nbsp;&nbsp;
</p>

</body>
</html>
