<?php


/* 

	Plugin Name: AWS Glacier
	Plugin URI: http://www.complextechweb.com/custom-wp-plugins/
	Description: This is a simple plugin to backup and store your WP Site and Database to Amazon Web Services Glacier which is meant for long term storage. Must have an active AWS Glacier Account. .

	Author: Complex Technologies 

	Version: 1.0

	Author URI: http://www.complextech.com/

*/



global $aws_db_version;
$aws_db_version = "1.0";

add_action( 'admin_menu', 'aws_plugin_menu' );
register_activation_hook( __FILE__, 'aws_install');

function aws_plugin_menu() {
	add_options_page( 'AWS Glacier Options', 'AWS Glacier', 'manage_options', 'aws-glacier-options', 'aws_glacier_options' );
	add_management_page( 'AWS Glacier Backup', 'AWS Glacier Archive', 'manage_options', 'aws-glacier-backup', 'aws_glacier_backup' );
	add_management_page( 'AWS Glacier Downloads', 'AWS Glacier Downlods', 'manage_options', 'aws-glacier-download', 'download_job_status' );
}
function delete_archive(){
	global $wpdb;
	$jId =$_GET['aid'] ;
	$aws_access_key = get_option('aws_access_key');
	$aws_sec_key = get_option('aws_sec_key');
	$aws_region = get_option('aws_region');
	$aws_gl_vault = get_option('aws_gl_vault');
	 $url = plugins_url().'/aws-glacier/deletearchive.php?ac='.urlencode($aws_access_key)."&sec=".urlencode($aws_sec_key).'&reg='.$aws_region.'&v='.$aws_gl_vault.'&aId='.$jId; 
	$result = curlRequest($url);
	$sql = "Delete from ".$wpdb->prefix . "archives where archId ='$jId' ";
	$wpdb->query($sql);
	
}
function aws_glacier_backup(){
		set_time_limit(0);
		if ($_REQUEST['ac']=='init_download'){
			init_download_job();
			echo "<p>Download Job has been initialed. Please wait for response from Amazon Glacier.</p>";
			exit;
		}elseif ($_REQUEST['ac']=='del_download'){
			delete_archive();
			echo "<p>Archive has been deleted successfully.</p>";
			exit;	 
		}else{
		if(isset($_POST['Submit'])){
			db_backup();
			$zip = new ZipArchive();
			$dir = get_home_path();
			chdir('../../');
			$base =  getcwd();
			$filename = $dir."archieve/backup".date('Ymd_His').".zip";
			$zip->open($filename, ZipArchive::CREATE);
			addDirectoryToZip($zip,$dir ,$base);
			$zip->close();
			uploadtoGlacier($filename);			
			
		}
		echo get_archieves();
		echo '<div class="wrap">';
		echo '<form id="form1" name="form1" method="post" action="">
	
	<p><input type="Submit" name="Submit" value="Start Archive"/></p>
	</form>
	';
		echo '</div>
			<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<table cellpadding="0" cellspacing="0" align="center" width="70%">
	<tr>
	<td align="center">
	<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="4ZD7ACVUBR4CN">
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
	</td>
	</tr>
	<tr>
	<td align="center">
	 If you find this plugin useful or would like to help continue its development. Please consider a donation. Thanks in Advance
	</td>
	</tr>
	</table>
		';
		}
}
function get_archieves(){
	global $wpdb;
	$table_name = $wpdb->prefix . "archives";
	$rows = $wpdb->get_results("SELECT * FROM  $table_name order by id desc ",OBJECT);
	$table = ' You have the follow combo archives (WP and associated DB) stored with Amazon Glacier
		<br><br>
		<strong>Note:</strong> Initiating a download takes a few hours for Amazon to prepare. Please return at a later time to check the status if your file is ready for download in the AWS Glacier Downloads section
	<table width="300" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="center"><strong>Date</strong></td>
    <!--td align="center"><strong>Archive ID</strong></td-->
    <td align="center"><strong>Vault</strong></td>
	<td align="center"><strong>Action</strong></td>
  </tr>
  ';
  
	$aws_access_key = get_option('aws_access_key');
	$aws_sec_key = get_option('aws_sec_key');
	$aws_region = get_option('aws_region');
	$aws_gl_vault = get_option('aws_gl_vault');
	
	foreach($rows as $row){
		
		$table .= '<tr> <td>'.date('m-d-Y',strtotime($row->archDate)).'</td>  <!--td>'.$row->archId.'</td-->  <td>'.$row->vault.'</td>  <td><a href="?page=aws-glacier-backup&ac=init_download&aid='.$row->archId.'">Download</a> | <a href="?page=aws-glacier-backup&ac=del_download&aid='.$row->archId.'">Delete</a></td> </tr>
';
	}
	$table .='</table>

	';
			
	return $table;
}
function start_download(){
	$jId =$_GET['jobId'] ;
	$aws_access_key = get_option('aws_access_key');
	$aws_sec_key = get_option('aws_sec_key');
	$aws_region = get_option('aws_region');
	$aws_gl_vault = get_option('aws_gl_vault');
	 $url = plugins_url().'/aws-glacier/downloadstart.php?ac='.urlencode($aws_access_key)."&sec=".urlencode($aws_sec_key).'&reg='.$aws_region.'&v='.$aws_gl_vault
	.'&aId='.$jId; 
	 $result = curlRequest($url);
	 if (strpos($result,'Success') !==false){
		
		$downloadURl = site_url().'/downloadBkp/downloaded.zip';
		echo "Your Backup is available for download<br>
				Please click here to download <a href='$downloadURl' target='_blank'>Click Here to Download</a> <br>
				<br><br>
			";
		
	 }else{
		echo $result;	 
	 }
	}
function check_download_status(){
	
	$aws_access_key = get_option('aws_access_key');
	$aws_sec_key = get_option('aws_sec_key');
	$aws_region = get_option('aws_region');
	$aws_gl_vault = get_option('aws_gl_vault');
	 $url = plugins_url().'/aws-glacier/downloadstatus.php?ac='.urlencode($aws_access_key)."&sec=".urlencode($aws_sec_key).'&reg='.$aws_region.'&v='.$aws_gl_vault; 
	$result = curlRequest($url);
	return $result;
}
	
function download_job_status(){
	global $wpdb;
	if ($_REQUEST['ac']=='start_download'){
		echo start_download();
	}
	
	$jobs = check_download_status();
	$jobArr = explode('<=====>',$jobs);
	$JobIds = explode('<<:::>>',$jobArr[0]);
	$JobStatus = explode('<<:::>>',$jobArr[1]);
	
	$sql ="Delete from ".$wpdb->prefix."jobstatus where jobId not in ('".implode("','",$JobIds)."')";
	$wpdb->query($sql);
	
	foreach($JobIds as $key => $dArr){
		
		$sql = "update ".$wpdb->prefix."jobstatus set jobStatus = '".$JobStatus[$key]."' where jobId ='".$dArr."'";
		$wpdb->query($sql);
		
		}
	$rows = $wpdb->get_results("SELECT * FROM  ".$wpdb->prefix."jobstatus order by id desc ",OBJECT);
	$table = ' You have following download jobs initiated on Amazon glacier.
	<br><br>
	Note: Downloads in Succeeded status will remain here for download for 24 hours and will be removed if not downloaded within the time period. Another download will need to be initiated once the time passes.
	<br><br>
	
	<table width="90%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="center"><strong>Job Id</strong></td>  
    <td align="center"><strong>Status</strong></td>
	<td align="center"><strong>Action</strong></td>
  </tr>
  ';
 foreach($rows as $row){
	 $table .= '<tr> <td>'.$row->jobId.'</td>   <td>'.$row->jobStatus.'</td>  <td><a href="?page=aws-glacier-download&ac=start_download&jobId='.$row->jobId.'">Download</a> </td> </tr>
';
 }
 echo $table.'</table>
 <br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<table cellpadding="0" cellspacing="0" align="center" width="70%">
	<tr>
	<td align="center">
	<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="4ZD7ACVUBR4CN">
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
	</td>
	</tr>
	<tr>
	<td align="center">
	 If you find this plugin useful or would like to help continue its development. Please consider a donation. Thanks in Advance
	</td>
	</tr>
	</table>
 ';
}
function init_download_job(){
	global $wpdb;
	$aid = $_GET['aid'];
	 
	$aws_access_key = get_option('aws_access_key');
	$aws_sec_key = get_option('aws_sec_key');
	$aws_region = get_option('aws_region');
	$aws_gl_vault = get_option('aws_gl_vault');	
	 $url = plugins_url().'/aws-glacier/download.php?ac='.urlencode($aws_access_key)."&sec=".urlencode($aws_sec_key).'&reg='.$aws_region.'&v='.$aws_gl_vault
	.'&aId='.$aid; 
	 $result = curlRequest($url);
	$res = explode('<<<::::>>>',$result);
	 $insSql = "insert into ".$wpdb->prefix."jobstatus (jobId,locationId,vault,awsStatus,jobStatus) values('{$res[0]}','{$res[1]}','$aws_gl_vault','InProgress','InProgress')";
	$wpdb->query($insSql);
	
}
//add_pages_page('aws download page','AWS Glacier','read','init-download-job');
function aws_install() {
   global $wpdb;
   global $aws_db_version;

   $table_name = $wpdb->prefix . "archives";
      
   $sql = " CREATE TABLE IF NOT EXISTS  $table_name (
		 `id` int(11) NOT NULL AUTO_INCREMENT,
		  `archDate` datetime DEFAULT NULL,
		  `archId` varchar(255) DEFAULT NULL,
		  `vault` varchar(255) DEFAULT NULL,
		  PRIMARY KEY (`id`)
    		);";
			
	$wpdb->query($sql);
   $sql ="CREATE TABLE IF NOT EXISTS `".$wpdb->prefix ."jobstatus` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `jobId` varchar(255) DEFAULT NULL,
		  `locationId` varchar(255) DEFAULT NULL,
		  `vault` varchar(255) DEFAULT NULL,
		  `awsStatus` varchar(50) DEFAULT NULL,
		  `jobStatus` varchar(50) DEFAULT NULL,
		  PRIMARY KEY (`id`)
		)";
	$wpdb->query($sql);
	$path = get_home_path();
	if (!file_exists($path.'/archieve')){
		mkdir($path.'/archieve',0777);	
	}
	if (!file_exists($path.'/dbbkp')){
		mkdir($path.'/dbbkp',0777);	
	}
	if (!file_exists($path.'/downloadBkp')){
		mkdir($path.'/downloadBkp',0777);	
	}
   add_option( "aws_db_version", $aws_db_version );
}
function db_backup(){
	global $wpdb;
	
	$dir = get_home_path();
	$filename = $dir."dbbkp/dbbackup.sql";
      $command = "mysqldump --opt  -u ".($wpdb->dbuser)."  -p".($wpdb->dbpassword)." -h ".($wpdb->dbhost)." ".
           "".($wpdb->dbname)." > $filename ";
     (system($command));

}
function uploadtoGlacier($filename){
	global $wpdb;
	$aws_access_key = get_option('aws_access_key');
	$aws_sec_key = get_option('aws_sec_key');
	$aws_region = get_option('aws_region');
	$aws_gl_vault = get_option('aws_gl_vault');
	 $url = plugins_url().'/aws-glacier/upload.php?ac='.urlencode($aws_access_key)."&sec=".urlencode($aws_sec_key).'&reg='.$aws_region.'&v='.$aws_gl_vault.'&file='.					urlencode($filename); 
	$result =  curlRequest($url);	
	$wpdb->insert($wpdb->prefix."archives", array( 'archDate' => date('Y-m-d H:i:s'), 'archId' => $result, 'vault' => $aws_gl_vault ));
}
function curlRequest($url) {
	$ch=curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($ch, CURLOPT_TIMEOUT, 250);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response = curl_exec($ch);
	return($response);
} 
 function addDirectoryToZip($zip, $dir, $base)
{
    $newFolder = str_replace($base, '', $dir);
	
    $zip->addEmptyDir($newFolder);
    foreach(glob($dir . '/*') as $file)
    {
        
		if(is_dir($file))
        {
           if (strpos($file,'archieve') or strpos($file,'downloadBkp')){
				continue;
			}
			$zip = addDirectoryToZip($zip, $file, $base);
        }
        else
        {
            $newFile = str_replace($base, '', $file);
            $zip->addFile($file, $newFile);
        }
    }
    return $zip;
}
	
function aws_glacier_options() {
	
	
	
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	if(isset($_POST['Submit'])){
		$aws_access_key = $_POST['aws_acc_key'];	
		$aws_sec_key = $_POST['aws_sec_key'];	
		$aws_region = $_POST['aws_region'];	
		$aws_gl_vault = $_POST['aws_gl_vault'];
		if (get_option('aws_access_key')){
			update_option('aws_access_key',$aws_access_key);
		}else{
			add_option('aws_access_key',$aws_access_key);
		}
		if (get_option('aws_sec_key')){
			update_option('aws_sec_key',$aws_sec_key);
		}else{
			add_option('aws_sec_key',$aws_sec_key);
		}
		if (get_option('aws_region')){
			update_option('aws_region',$aws_region);
		}else{
			add_option('aws_region',$aws_region);
		}
		if (get_option('aws_gl_vault')){
			update_option('aws_gl_vault',$aws_gl_vault);
		}else{
			add_option('aws_gl_vault',$aws_gl_vault);	
		}
	}
	$aws_access_key = get_option('aws_access_key');
	$aws_sec_key = get_option('aws_sec_key');
	$aws_region = get_option('aws_region');
	$aws_gl_vault = get_option('aws_gl_vault');
	echo '<div class="wrap">';
	echo '<form id="form1" name="form1" method="post" action="">
<p>AWS Access Key:<input name="aws_acc_key" value="'.$aws_access_key.'" type="password" /></p>
<p>AWS secret Key:<input name="aws_sec_key" value="'.$aws_sec_key.'" type="password" /></p>
<p>AWS Region:<select name="aws_region" id="aws_region">
<option value="" '.(($aws_region=='')?'selected="selected"':'').'>Select Region</option>
<option value="us-east-1" '.(($aws_region=='us-east-1')?'selected="selected"':'').'>US East (Northern Virginia) Region</option>
<option value="us-west-2" '.(($aws_region=='us-west-2')?'selected="selected"':'').'>US West (Oregon) Region</option>
<option value="us-west-1" '.(($aws_region=='us-west-1')?'selected="selected"':'').'>US West (Northern California) Region</option>
<option value="eu-west-1" '.(($aws_region=='eu-west-1')?'selected="selected"':'').'>EU (Ireland) Region</option>
<option value="ap-southeast-2" '.(($aws_region=='ap-southeast-2')?'selected="selected"':'').'>Asia Pacific (Sydney) Region</option>
<option value="ap-northeast-1" '.(($aws_region=='ap-northeast-1')?'selected="selected"':'').'>Asia Pacific (Tokyo) Region</option>
</select>
<!--input name="aws_region" value="'.$aws_region.'" type="text" /--></p>
<p>Glacier Vault Name:<input name="aws_gl_vault" value="'.$aws_gl_vault.'" type="text" /></p>
<p><input type="Submit" name="Submit" value="Submit"/></p>
</form>

';
	echo '</div>
	
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<table cellpadding="0" cellspacing="0" align="center" width="70%">
	<tr>
	<td align="center">
	<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="4ZD7ACVUBR4CN">
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
	</td>
	</tr>
	<tr>
	<td align="center">
	 If you find this plugin useful or would like to help continue its development. Please consider a donation. Thanks in Advance
	</td>
	</tr>
	</table>
	';
}
?>