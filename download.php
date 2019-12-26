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
	$result = $aws->initiateJob(array(
		'vaultName'=>$_GET['v'],
		'Type'=>'archive-retrieval',
		'ArchiveId'=>$_GET['aId']
	));	
	echo $result->get('jobId')."<<<::::>>>".$result->get('location');
	
	
?>