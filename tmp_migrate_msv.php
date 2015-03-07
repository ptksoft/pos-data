#!/usr/bin/php -q
<?php

// ============================= SCS 1 ================================== >> 

	require('config.inc.php');

		$sql_msv = "select
								*
							from
								pos.pos_promotion_list
							where
								current_date between startdate and enddate
							";
		$table_msv = pg_query($sql_msv);
			if (false===$table_msv) die('Error!!! query error'.NL.$table_msv);
		$rowcount = pg_num_rows($table_msv);		
	
// ============================= SCS 2 ================================== >>
	
	require('config_migrate.inc.php');
		$insert_row = 0; 
		$sql_clear_data = "delete from pos.msv_promotions";
		$exec_clear = pg_query($sql_clear_data);
		
		while ($row_msv = pg_fetch_array($table_msv)) { // While data scs1 prepare to insert >> 
		
			$id = trim($row_msv["id"]); 
			$event_no = trim($row_msv["event_no"]); 
			$description = trim($row_msv["description"]); 
			$condition_list = trim($row_msv["condition_list"]); 
			$condition_qty = trim($row_msv["condition_qty"]); 
			$result_list = trim($row_msv["result_list"]); 
			$result_qty = trim($row_msv["result_qty"]); 
			$discount_amount = trim($row_msv["discount_amount"]); 
			$group_no = trim($row_msv["group_no"]); 
			$promotion_type = trim($row_msv["promotion_type"]); 
			$startdate = trim($row_msv["startdate"]); 
			$enddate = trim($row_msv["enddate"]); 
			
				$idate_start_year =  substr($startdate,2,2);
				$idate_start_month =  substr($startdate,5,2);
				$idate_start_day =  substr($startdate,8,2);
				
				$idate_stop_year =  substr($enddate,2,2);
				$idate_stop_month =  substr($enddate,5,2);
				$idate_stop_day =  substr($enddate,8,2);
			
				// =============== data fix ==>>
					
					$site_num = 43;
					
					$time =microtime(true);
					$micro_time=sprintf("%06d",($time - floor($time)) * 1000000);	   
					$created = date('Y-m-d H:i:s:'.$micro_time,$time);
					$modified = date('Y-m-d H:i:s');
					
					$i_year = substr($created,2,2);
					$i_month = substr($created,5,2);
					$i_day = substr($created,8,2);
					$i_hour = substr($created,11,2);
					$i_min = substr($created,14,2);
					$i_sec = substr($created,17,2);
					$i_micro = substr($created,20,6);
					
					$idate_start = $idate_start_year.$idate_start_month.$idate_start_day;
					$idate_stop = $idate_stop_year.$idate_stop_month.$idate_stop_day;
					
					$_version_ = $i_year.$i_month.$i_day.$i_hour.$i_min.$i_sec.$i_micro;
					
					$uuid = gen_uuid();
					
				// =============== Insert to scs2 ================ >> 
					
					$sql_insert = "
											insert into 	pos.msv_promotions
											(
												id , 
												site_num , 
												event_num ,
											  	group_num ,
												description ,
												condition_list ,
												condition_qty ,
												result_list ,
												result_qty ,
												discount_amount ,
												discount_type ,
												idate_start ,
												idate_stop ,
												created ,
												modified  ,
												_version_ 
											) 
											values 
											(
												'$uuid' , 
												$site_num , 
												$event_no ,
											  	$group_no ,
												'$description' ,
												'$condition_list' ,
												$condition_qty ,
												'$result_list' ,
												$result_qty ,
												$discount_amount ,
												'$promotion_type' ,
												'$idate_start' ,
												'$idate_stop' ,
												'$modified'  ,
												'$modified'  ,
												'$_version_' 
											)		
										";					
					$exec = pg_query($sql_insert);
					if($exec==""){ die('ERROR!!! Cannot Insert to Database '.NL.$sql_insert.NL);   } else{  $insert_row = $insert_row +1; }
					
					
					
		} // => while ($row_msv = pg_fetch_assoc($table_msv)) {
		
		echo "Finish Insert Row Count => $rowcount / insert_row => $insert_row".NL;
		
		
		// ===================== Function =========================== >> 
		
		 function gen_uuid() {

// The field names refer to RFC 4122 section 4.1.2

			return sprintf('%04x%04x-%04x-%03x4-%04x-%04x%04x%04x',
			mt_rand(0, 65535), mt_rand(0, 65535), // 32 bits for "time_low"
			mt_rand(0, 65535), // 16 bits for "time_mid"
			mt_rand(0, 4095), // 12 bits before the 0100 of (version) 4 for "time_hi_and_version"
			bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '01', 6, 2)),
			// 8 bits, the last two of which (positions 6 and 7) are 01, for "clk_seq_hi_res"
			// (hence, the 2nd hex digit after the 3rd hyphen can only be 1, 5, 9 or d)
			// 8 bits for "clk_seq_low"
			mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535) // 48 bits for "node"
			);
		}


  
?>