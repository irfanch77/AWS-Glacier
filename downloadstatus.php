<?
	set_time_limit(0);
	require 'aws.phar';
	use Aws\Glacier\GlacierClient;
	use Aws\Common\Enum\Region;
	use Aws\Glacier\Transfer;
	use Aws\Common\Enum\Size;
	use Guzzle\Http\EntityBody;
	$aws = GlacierClient::factory(array(
		'key'    => $_GET['ac'],
		'secret' => $_GET['sec'],
		'region' => $_GET['reg']
	));
	
	
	$sat = $aws->listJobs(array(
		'vaultName'=>$_GET['v'],				
	));
	
	$jobs = $sat->get('JobList');
	//print "<pre>";
	//print_r($jobs);die;
	$JobIds = array();
	$JobStatus = array();
	foreach($jobs as $job){
		//if($job['StatusCode'] =='Succeeded'){
			 $JobIds[] = $job['JobId'];
			 $JobStatus[] = $job['StatusCode'];			
		//}	
	}
	echo implode('<<:::>>',$JobIds);
	echo "<=====>";
	echo implode('<<:::>>',$JobStatus);
	
?>