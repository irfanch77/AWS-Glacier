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
	
	
	$path= '../../../downloadBkp/';
	$sat = $aws->listJobs(array(
		'vaultName'=>$_GET['v'],		
		
	));
	$jid = $_GET['aId'];
	
	$jobs = $sat->get('JobList');
    $success ='No'; 
	foreach($jobs as $job){
		if($job['JobId'] = $jid && $job['StatusCode'] =='Succeeded'){
			 $success = 'Yes';	
		
		}	
	}
	if ($success!='Yes'){
		echo "Amazon Glacier download is not ready";	
	}else{
		$sat = $aws->getJobOutput(array(
		'vaultName'=>$_GET['v'],
		'saveAs'=> $path.'downloaded.zip',
		'jobId'=>$jid 
		));			
		echo "Success";
		
	}
	
?>