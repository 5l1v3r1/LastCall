<?php 
	$start = microtime(true);
	//dbconfig
	$linkAsterisk=mysqli_connect('192.168.1.231','cron','1234','asterisk');
	$linkVtiger	 =mysqli_connect('localhost','vtiger','vtiger2016','vtiger');
	$linkCdr	 =mysqli_connect('192.168.1.231','cdr','cdr2016','cdrdb');

	//get retentions
	$retentionResults=mysqli_query($linkAsterisk," SELECT user,full_name as user FROM vicidial_users where custom_one='retention' AND active='Y' ");
	$retentions=mysqli_fetch_all($retentionResults);

	$data=array();
	$outputJson = fopen("reports/report".date("dmY").".json", "w");

	//loop retentions
	foreach ($retentions as $retention) {
		$retentionId=$retention[0];
		$retentionName=$retention[1];
		if ($retentionId=='cc354') {
			$retentionId='cristian.krugher';
			//continue;//skip
		}

		//get retention vtiger id
		$retentionResults=mysqli_query($linkVtiger,"SELECT id from vtiger_users where user_name='".$retentionId."'");
		$retentionVtiger=mysqli_fetch_all($retentionResults);
		$retentionVtiger=$retentionVtiger[0][0];

		$data[$retentionName]=array();

		// get vtiger leads for each retention
		$getLeads=mysqli_query($linkVtiger,"SELECT crmid FROM vtiger_crmentity WHERE setype='Leads' AND smownerid ='".$retentionVtiger."' ");
		$leads=mysqli_fetch_all($getLeads);

		//get phone for each lead
		foreach ($leads as $lead) {
			$lead=$lead[0];
			$getLeadData=mysqli_query($linkVtiger,"SELECT vtiger_leadaddress.phone,CONCAT_WS(' ',vtiger_leaddetails.firstname,vtiger_leaddetails.lastname) FROM vtiger_leadaddress INNER JOIN vtiger_leaddetails ON vtiger_leaddetails.leadid=vtiger_leadaddress.leadaddressid  WHERE leadaddressid='".$lead."' ");
			$leadData=mysqli_fetch_all($getLeadData);
			$phoneId=$leadData[0][0];
			$leadName=$leadData[0][1];

			//phone ext
			if ($retentionId=='cristian.krugher') {
				$retentionId='cc354';
			}
			$phoneExt=explode('cc',$retentionId);
			$phoneExt=$phoneExt[1];

			//get last calldate
			$sqlCdr="SELECT calldate FROM cdr WHERE dst like '%".$phoneId."' and src='".$phoneExt."' order by calldate desc limit 1 ";
			$cdrData=mysqli_query($linkCdr,$sqlCdr);
			$cdr=mysqli_fetch_all($cdrData);
			$lastCallDate=$cdr[0][0];

			//if calldate is 4 day old
			if (!is_null($lastCallDate)) {
				if (time() - strtotime($lastCallDate) >= 4 * 86400) {
					array_push($data[$retentionName],array($phoneId,$leadName,$lastCallDate));
				}	
			} else{
				array_push($data[$retentionName],array($phoneId,$leadName,'No Data!'));
			}
		}
	}
	fwrite($outputJson,"[".json_encode($data)."]");
	$duration = microtime(true) - $start;
	echo $duration;
?>