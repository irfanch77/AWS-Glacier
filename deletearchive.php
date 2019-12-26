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
	
	
	$sat = $aws->deleteArchive(array(
		'vaultName'=>$_GET['v'],
		'archiveId'				
	));
	
	$jobs = $sat->get('JobList');
	print_r($jobs);
	$JobIds = array();
	$JobStatus = array();
	foreach($jobs as $job){
		
			 $JobIds[] = $job['JobId'];
			 $JobStatus[] = $job['StatusCode'];			
		
	}
	echo implode('<<:::>>',$JobIds);
	echo "<=====>";
	echo implode('<<:::>>',$JobStatus);
	
?>