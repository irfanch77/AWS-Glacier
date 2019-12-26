<?php
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
	$archId = $aws->uploadArchive(array(
            'vaultName'          => $_GET['v'],
            'archiveDescription' => 'Backup at '. date('d M Y H:i:s'),
            'body'               => fopen($_GET['file'], 'r'),
        ))->get('archiveId');
	echo 	$archId;
?>