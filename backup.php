<?php
function backup_database_tables($host,$user,$pass,$name,$tables){
	$link=mysqli_connect($host,$user,$pass);
	mysqli_select_db($link,$name);
	mysqli_query($link,'SET NAMES utf8');
	
	//get all of the tables
	if($tables=='*'){
		$tables=array();
		$result=mysqli_query($link,'SHOW TABLES');
		while($row=mysqli_fetch_row($result)){
			$tables[]=$row[0];
		}
	}else{
		$tables=is_array($tables)?$tables:explode(',',$tables);
	}
	
	//cycle through each table and format the data
	foreach($tables as $table){
			$result=mysqli_query($link,'SELECT * FROM '.$table);
			$num_rows=mysqli_num_rows($result);
			
			$row2=mysqli_fetch_row(mysqli_query($link,'SHOW CREATE TABLE '.$table));
			$row2=str_ireplace(
				'Create Table',
				'CREATE TABLE IF NOT EXISTS',
				$row2);
				
			$return.="\n\n".$row2[1].";";
			$index=0;
			while($row=mysqli_fetch_row($result)){
				if(++$index==1 or $index%10==1){
					$return.='INSERT INTO `'.$table."` \n\t";
				};
				
				$return.='VALUES(';
				for($j=0;$j<count($row);$j++){
					$row[$j]=addslashes($row[$j]);
					$row[$j]=str_replace("\n","\\n",$row[$j]);
					if(isset($row[$j])){
						$return.='"'.$row[$j].'"';
					}else{
						$return.='""';
					};
					
					if($j<(count($row)-1)){
						$return.= ',';
					};
				};
				
				if($index==$num_rows or $index%10==0){
					$return.=');'."\n";
				}else{
					$return.='),'."\n\t";
				};
			};
				
			$return.="\n\n\n";
	}
	
	//save the file	
	$domain=isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:null;
	if(is_null($domain)) $domain=isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:null;
	$file_name=$domain.' -- db-backup - '.date('d-m-Y H-i-s').'.sql.gz';
	file_put_contents(rtrim($_SERVER['DOCUMENT_ROOT'],'/ ').'/'.$file_name,gzencode($return));
}