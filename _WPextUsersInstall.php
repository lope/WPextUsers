<?php
if (!file_exists(__DIR__.'/wp-config.php')) exit("You need to have a wp-config.php file before running this install.<br>Just run the standard installer and configure wp-config with the UNIQUE wordpress database for this site. (NOT the External USERS database).");

$filePermissionsTips = "
<h3>Tips on setting file permissions</h3>
<p>While you're doing the installation, you can set convenient permissions like this: (where /var/www is the root of your WP installation)<br>
<p style='font-family:monospace'>sudo find /var/www -type d -exec chmod 777 {} +<br>
sudo find /var/www -type f -exec chmod 666 {} +</p>
After you've finished the installation, more secure permissions might be something like:<br>
<p style='font-family:monospace'>sudo find /home/web -type d -exec chmod 755 {} +<br>
sudo find /home/web -type f -exec chmod 644 {} +</p>
</p>";

//confirm the script has write permission
$test_file=__DIR__.'/confirm-have-write-permission'.rand(0,999999); file_put_contents($test_file,'temp'); if (file_exists($test_file)) unlink($test_file);
if (error_get_last()) exit("Please ensure the webserver has appropriate permissions on this dir and all of it's files and subdirectories while you run the installer.<br>".$filePermissionsTips);

//create error handler
set_error_handler('errorHandler');
register_shutdown_function("checkIfFatalError");
function errorHandler($code, $message, $file, $line) {
	exit("<span style='font-weight:bold;color:red'>An error occurred: $message, Line: $line</span>");
}
function checkIfFatalError() {
	$error = error_get_last(); if ($error) exit("An error occurred while running the installer. Please ensure the webserver has appropriate permissions on this dir and it's files and subdirectories while you run the installer.<br><br>Error: {$error['message']}<br>Line: {$error['line']}");
}

//check if WP has been modified already
$wpHasBeenModified_file=__DIR__.'/thisWPhasBeenModifiedForExternalUsers.txt';
if (file_exists($wpHasBeenModified_file)) echo "<p style='color:orange'>It looks like this wordpress installation has already been modified to use an external user's database.</p>";

require(__DIR__.'/wp-includes/version.php'); //get wordpress version

//prepare stuff
$supportedVersions = ['4.0'];//change this when you support more versions of WP, eg: ['4.0','4.1'] etc
$haveModified=false;
$currentAction='';
//finished preparing stuff

if (isset($_POST['doInstall'])) {
	echo "Starting install of the external database hack<br>";
	install();
} elseif (isset($_POST['doUninstall'])) {
	echo "Starting uninstall of the external database hack<br>";
	uninstall();
} else {//show disclaimer and offer user to install/revert
	?>
	<h3>Disclaimer</h3>
	<p>The author of this software takes absolutely no responsibility for any damage/harm/problems/data loss/etc that might arise from using it. This software comes without any warranty neither expressed, nor implied. You're free to use it or modify it as you choose. Proceed at your own risk. If your wordpress files or your DB has anything important in them, you should <strong>create a backup now</strong>. This is a hack that has only been tested very minimally. You should review the modifications it makes and test thoroughly before deploying it to a live site.</p>
	<h1>WPextUsers</h1>
	<p>This Wordpress hack allows you to have multiple WP installs (each with their own files and DB for content) that all share a single DB of users. The hack modifies Wordpress installations to refer to an external database when accessing the wp_users and wp_usermeta tables.</p>
	<h3>Hack Status</h3>
	<p>This is a proof of concept. It is not production ready yet. I don't have time to work on it any more. I suggest you fork it and improve it.</p>

	<h3>Compatible with WP versions</h3>
	<ul>
	<?php foreach ($supportedVersions as $version) echo "<li>$version</li>";?>
	</ul>

	<h3>Expected capabilities</h3>
	<ul>
	<li>Users can log in at multiple WP installs using the same login details. If they change their password or user information, the changes will be reflected on all WP installs.</li>
	</ul>
	<h3>Limitations</h3>
	<ul>
	<li>When a user logs in at one WP site, they are not automatically logged in on another WP site. That will probably require hacking WP's cookie system as well.</li>
	</ul>

	<h3>What has been tested successfully</h3>
	<ul>
	<li>Log in and log-out on different sites.</li>
	<li>Change password at one site, and see that it has been changed at the other.</li>
	<li>Change usermeta data on one site for a user's profile and see it updated on the other site.</li>
	</ul>

	<h3>Requirements</h3>
	<ul>
	<li>Same table prefix: All DB's in the entire set must use the same wordpress DB table prefix (wp_ by default)</li>
	<li>Write permissions: The installer needs write permission to files and directories within the wordpress directory</li>
	<li>Re-hack on update: After updating wordpress you might have to re-hack it.</li>
	</ul>

	<h3>Suggestions if you want to go into production</h3>
	<ul>
	<li>check/modify /wp-admin/user-new.php (I never got around to it, maybe more files need attention)</li>
	<li>Test creating new users.</li>
	<li>Test modifying other user's details by admin</li>
	<li>Test emails</li>
	<li>Test forgot password</li>
	<li>Test CRUD (create read update delete) of users, posts, pages</li>
	<li>Read the code to see what get's modified, check that everything looks okay.</li>
	</ul>

	<h3>What could go wrong?</h3>
	<ul>
	<li>I'm not very familiar with the Wordpress. I've wildly monkey patched a few files that 'seem' to be 'all about users', replacing all references from the default DB to the Users DB, and have only skim read the WP code. I've not checked every single replacement carefully. Some replacements might be missing, some might be incorrect. Read the resulting code yourself. Or test thoroughly. It's your responsibility to make sure your site works.</li>
	<li>If WP tries to do any table joins between user tables and other tables, that simply wouldn't work and would result in an error. Again I'm a Wordpress novice so I don't know what the risk of this is. I saw JOIN in one or two places, I haven't checked what tables they're joining.</li>
	</ul>

	<h3>The main principles of the hack</h3>
	<ul>
	<li>Wordpress defines a <em>wpdb</em> class and creates a single instance of it called <em>$wpdb</em> to access the default database. I've created another instance of that class called <em>$wpUsersDB</em> and initialized it in the same way.</li>
	<li>Code that was referring to <em>$wpdb</em> to access user data has been modified to refer to <em>$wpUsersDB</em> instead.</li>
	<li>Rename tables that are not used in order to avoid confusion and also cause WP to produce an error if it tries to access the tables on the wrong DB. An error is a much better indication of a missing code-patch than strange un-detected behavior.</li>
	</ul>

	<h3>Future wishlist</h3>
	<ul>
	<li>If WP has permissions to do a self-update, make it run the hack again afterwards, automatically.</li>
	</ul>

	<p>The hack was initially developed with WP version <a href='http://wordpress.org/wordpress-4.0.tar.gz'>4.0</a></p>

	<?php
	echo "<h3>Your Wordpress installation</h3>Your WP version is: $wp_version<br>";
	if (in_array($wp_version,$supportedVersions)) {
		echo "<span style='color:green;'>This hack has been tested (as above) with your version of wordpress.</span> You may proceed at your own risk.<br>";
	} else {
		echo "<span style='color:red;font-weight:bold;'>This hack has not been tested (as above) with your version of wordpress. You may proceed at your own risk.</span><br>";
	}
	?>
	<h3>How to install</h3>
	<ol>
	<li>Read and agree to everything above</li>
	<li>Create a MySQL DB that will hold your <em>wp_users</em> &amp; <em>wp_usermeta</em> tables (lets pretend the DB is called <em>wpusers</em>)</li>
	<li>Create a normal WP installation that uses the <em>wpusers</em> database. Complete all the WP install questions until you see a WP login screen (let's say you call the admin account <em>mainAdmin</em>).</li>
	<li>Now that the <em>wpusers</em> DB is initialized you can delete this wordpress files &amp; dirs, just keep the database.</li>
	<li>Create a new WP installation for the first site, let's pretend it's called <em>site A</em>. Complete all the WP install questions until you see a WP login screen</li>
	<li>After you've completed the installation for <em>site A</em>, make sure the webserver (Apache, Nginx or HHVM) has write permissions to the files and directories inside the installation.</li>
	<li>Put this php file (<em><?php echo basename(__FILE__);?></em>) in the root of <em>site A</em> and open it in your browser.</li>
	<li>Fill in the database details for your central USERS database that will hold your <em>wp_users</em> &amp; <em>wp_usermeta</em> tables for all of your sites. (<em>wpusers</em> is the name of that DB in this example)</li>
	<li>Click install now</li>
	<li>Now you can log in with the username <em>mainAdmin</em> at <em>site A</em></li>
	<li>Repeat steps 5-9 to add more sites</li>
	</ol>

	<?php echo $filePermissionsTips;?>

	<h3>Apply the WP modification below</h3>
	<form action='' method='POST'>
	External Users DB: Name <input type='text' name='dbname'><br>
	External Users DB: User <input type='text' name='dbuser'><br>
	External Users DB: Pass <input type='text' name='dbpass'><br>
	External Users DB: Host <input type='text' name='dbhost' value='localhost'><br>
	<input type='submit' name='doInstall' value='Install Now'>
	<!-- not implemented yet <input type='submit' name='doUninstall' value='Uninstall Now'>-->
	</form>
	<?php
}


function getSafePost($key) {
	$val = trim($_POST[$key]);
	if (preg_match('/[^A-Za-z\d._-]/',$val)!==0) exit("Your $key contains characters that are not allowed: $val<br>Only Alphanumeric and -_. is allowed.");
	return $val;
}

function install() {
	global $wp_version, $currentAction;
	$currentAction='install';
	$dbname = getSafePost('dbname');
	$dbuser = getSafePost('dbuser');
	$dbpass = getSafePost('dbpass');
	$dbhost = getSafePost('dbhost');
	if (getDBnameFromWPconfig()===$dbname) exit("Error, you have entered the same DB name ($dbname) as the main DB used by this wordpress install. Aborting.");
	//rename db tables for safety and to avoid confusion
	rename_db_tables($dbname, $dbuser, $dbpass, $dbhost);
	//modify WP content install's files
	$comment='// https://github.com/lope/WPextUsers';
	$extDBConsts  = $comment."\n";
	$extDBConsts .= "define('USERSDB_NAME', 	'$dbname');\n";
	$extDBConsts .= "define('USERSDB_USER', 	'$dbuser');\n";
	$extDBConsts .= "define('USERSDB_PASSWORD', '$dbpass');\n";
	$extDBConsts .= "define('USERSDB_HOST', 	'$dbhost');\n";
	
	//be very careful of using magic quotes (double quotes) below because the strings contain a lot of dollar signs. Remember the dollar here "$foo" will be replaced by PHP. So either use "\$foo" or '$foo'

	//wp-config.php
	switch ($wp_version) {//
		case '4.0':
		case '4.0.0'://example
			modifyFile('/wp-config.php','afterLine',"/** The name of the database for WordPress */",$extDBConsts,'USERSDB_NAME');
			break;
		default:
			exit("\$wp_version $wp_version is missing a rule for: DB consts");
	}
	//wp-settings.php
	switch ($wp_version) {//
		case '4.0':
			modifyFile('/wp-settings.php','replace','wp_set_wpdb_vars();','/*wp_set_wpdb_vars();*/ wp_set_wpdb_vars($wpdb); wp_set_wpdb_vars($wpUserDB); '.$comment,'wp_set_wpdb_vars($wpUserDB);');
			break;
		default:
			exit("\$wp_version $wp_version is missing a rule for: DB consts");
	}
	//wp-includes/load.php
	switch ($wp_version) {//
		case '4.0':
			modifyFile('/wp-includes/load.php','replace',"function wp_set_wpdb_vars() {\n\tglobal \$wpdb,","function wp_set_wpdb_vars(/*now its a param*/\$wpdb) {\n\tglobal /*\$wpdb,*/",'function wp_set_wpdb_vars(/*');
			modifyFile('/wp-includes/load.php','afterLine','$wpdb = new wpdb','global $wpUserDB; '.$comment."\n".'$wpUserDB = new wpdb( USERSDB_USER, USERSDB_PASSWORD, USERSDB_NAME, USERSDB_HOST );','$wpUserDB = new wpdb');
			break;
		default:
			exit("\$wp_version $wp_version is missing a rule for: DB consts");
	}
	//wp-includes/user.php
	switch ($wp_version) {//
		case '4.0':
			modifyFile('/wp-includes/user.php','replace','$wpdb','$wpUserDB','$wpUserDB');
			break;
		default:
			exit("\$wp_version $wp_version is missing a rule for: DB consts");
	}
	//wp-includes/capabilities.php
	switch ($wp_version) {//
		case '4.0':
			modifyFile('/wp-includes/capabilities.php','replace','$wpdb','$wpUserDB','$wpUserDB');
			break;
		default:
			exit("\$wp_version $wp_version is missing a rule for: DB consts");
	}
	//wp-admin/user-edit.php
	switch ($wp_version) {//
		case '4.0':
			modifyFile('/wp-admin/user-edit.php','replace','$wpdb','$wpUserDB','$wpUserDB');
			break;
		default:
			exit("\$wp_version $wp_version is missing a rule for: DB consts");
	}
	//wp-includes/pluggable.php
	$plugableSetPasswordFunc = 'function wp_set_password( $password, $user_id ) { 	global $wpUserDB;  	$hash = wp_hash_password( $password ); 	$wpUserDB->update($wpUserDB->users, array("user_pass" => $hash, "user_activation_key" => ""), array("ID" => $user_id) );  	wp_cache_delete($user_id, "users"); }';
	switch ($wp_version) {//
		case '4.0':
			modifyFile('/wp-includes/pluggable.php','beforeLine',"!function_exists('wp_set_password')",$comment."\n".$plugableSetPasswordFunc."\n",'$wpUserDB');
			break;
		default:
			exit("\$wp_version $wp_version is missing a rule for: DB consts");
	}
	//wp-includes/meta.php
	$plugableSetPasswordFunc = 'function wp_set_password( $password, $user_id ) { 	global $wpUserDB;  	$hash = wp_hash_password( $password ); 	$wpUserDB->update($wpUserDB->users, array("user_pass" => $hash, "user_activation_key" => ""), array("ID" => $user_id) );  	wp_cache_delete($user_id, "users"); }';
	switch ($wp_version) {//
		case '4.0':
			modifyFile('/wp-includes/meta.php','replace','$wpdb','$wpdbSelected','$wpdbSelected');
			modifyFile('/wp-includes/meta.php','replace','global $wpdbSelected;','global $wpdb, $wpUserDB; $wpdbSelected = (isset($meta_type)&&$meta_type===\'user\' || isset($type)&&$type===\'user\') ? $wpUserDB : $wpdb;','$wpUserDB');
			break;
		default:
			exit("\$wp_version $wp_version is missing a rule for: DB consts");
	}
	//finished
	global $wpHasBeenModified_file; file_put_contents($wpHasBeenModified_file, 'This is a hack to allow multiple installs of wordpress to have their own independent DB for most things, but share a single external DB for users.');
	exit("Install finished");
}

function uninstall() {
	global $wp_version, $currentAction;
	$currentAction='uninstall';
	echo "Uninstall not implemented yet. Just restore the backup files. They're named .bak<br>";
}

function modifyFile($fileName,$mode,$find,$insert,$alreadyPatched) {
	global $currentAction, $haveModified;
	if (!file_exists(__DIR__.$fileName)) exit("Could not find file to be modified: $fileName (in ".__DIR__.")");
	$buf = file_get_contents(__DIR__.$fileName);
	if (strpos($buf,$alreadyPatched)!==false) {
		echo "<p style='color:orange'><span style='background-color:#DDD'>$alreadyPatched</span> was found in $fileName, indicating it's already been patched.</p>";
	} else {//not already patched
		$posFind = strpos($buf,$find);
		if ($posFind!==false) {
			$backupFile = $fileName.'.bak';
			if (!file_exists(__DIR__.$backupFile)) {
				echo "Creating backup file: $backupFile<br>";
				copy(__DIR__.$fileName,__DIR__.$backupFile);
			}
			echo "Found the string <span style='background-color:#DDD'>$find</span> in $fileName ";
			switch ($mode) {
				case 'replace':
					$count=0;
					$buf = str_replace($find,$insert,$buf,$count);
					echo "($count replacements made) ";
					break;
				case 'beforeLine':
					while ($buf[$posFind]!=="\n") --$posFind;
					$buf = substr($buf,0,$posFind)."\n".$insert.substr($buf,$posFind);
					break;
				case 'afterLine':
					$posNl = strpos($buf,"\n",$posFind+strlen($find));
					$buf = substr($buf,0,$posNl)."\n".$insert.substr($buf,$posNl);
					break;
				default:
					exit("Unknown mode for modifyFile: $mode");
			}
			file_put_contents(__DIR__.$fileName,$buf);
			echo "<span style='font-weight:bold;color:green'>Modified</span><br><br>";
			$haveModified=true;
		} else {
			echo "<p style='color:red;font-weight:bold;'>Error: Could not find <span style='background-color:#DDD'>$find</span> in $fileName<br>";
			if ($haveModified) echo "Files have already been modified, so the $currentAction is incomplete. Wordpress may not be functional.<br>Perhaps try fix the problem and add a specific change for this WP version.<br>";
			echo "</p>";
		}
	}//not already patched
}

function getDBnameFromWPconfig() {
	$buf = file_get_contents(__DIR__.'/wp-config.php');
	$pos=strpos($buf,"define('DB_NAME'");
	$posComma = strpos($buf,',',$pos);
	$posBracket = strpos($buf,')',$posComma);
	return preg_replace('/[^A-Za-z\d]/','',substr($buf,$posComma+1,$posBracket-$posComma-1));
}
function getValueFromWPconfig($key) {
	$buf = file_get_contents(__DIR__.'/wp-config.php');
	$pos=strpos($buf,$key); if ($pos===false) exit("Could not find key $key in /wp-config.php");
	$pos += strlen($key)+1;
	$posNl = strpos($buf,"\n",$pos);
	return preg_replace('/[^A-Za-z\d_]/','',substr($buf,$pos,$posNl-$pos-1));
}

function rename_db_tables($dbname, $dbuser, $dbpass, $dbhost) {
	$cDBname = getValueFromWPconfig("define('DB_NAME'");
	$cDBuser = getValueFromWPconfig("define('DB_USER'");
	$cDBpass = getValueFromWPconfig("define('DB_PASSWORD'");
	$cDBhost = getValueFromWPconfig("define('DB_HOST'");
	$tblPrefix = getValueFromWPconfig('$table_prefix');
	echo "got details name $dbname user $dbuser pass $dbpass host $dbhost prefix $tblPrefix name $cDBname, $cDBuser,$cDBpass,$cDBhost<br>";
	$cTbls = ['users','usermeta'];//rename these tables on content DBs
	$uTbls = ['commentmeta','comments','links','options','postmeta','posts','term_relationships','term_taxonomy','terms'];//rename these tables on User DB
	echo "<br>Renaming tables in DBs<br>";
	rename_table_mysqli($dbname,$dbuser,$dbpass,$dbhost,$tblPrefix,$uTbls,"$dbname(Users DB)",'_not_used');//===false) rename_table_mysql($dbname,$dbuser,$dbpass,$dbhost,$tblPrefix,$uTbls,"$dbname(Users DB)",'_not_used');
	rename_table_mysqli($cDBname,$cDBuser,$cDBpass,$cDBhost,$tblPrefix,$cTbls,"$cDBname(Content DB)",'_using_external_instead');//===false) rename_table_mysql($cDBname,$cDBuser,$cDBpass,$cDBhost,$tblPrefix,$cTbls,"$cDBname(Content DB)",'_not_used');
}
/*function rename_table_mysql($dbname,$dbuser,$dbpass,$dbhost,$tblPrefix,$tblNames,$dbdesc,$addSuffix) {
	echo "MySQL connecting to DB: $dbdesc with user:$dbuser pass:$dbpass prefix:$tblPrefix at host $dbhost<br>";
	$con = mysql_connect($dbhost,$dbuser,$dbpass);
	if ($con===false) {echo "Could not connect to DB $dbname at host $dbhost with user:$dbuser pass:$dbpass with MySQL: ".mysql_error()."<br>"; return false;}
	if (mysql_select_db($dbname)===false) {echo "Could not select DB $dbname with MySQL<br>"; return false;}
	foreach ($tblNames as $tblName) if (mysql_query("RENAME TABLE `$tblPrefix$tblName` TO `$tblPrefix$tblName$addSuffix`;")===false) {echo "Failed to rename `$tblPrefix$tblName` table to `$tblPrefix$tblName$addSuffix`".mysql_error()."<br>"; mysql_close($con); return false;}
	mysql_close($con);
	return true;
}*/
function rename_table_mysqli($dbname,$dbuser,$dbpass,$dbhost,$tblPrefix,$tblNames,$dbdesc,$addSuffix) {
	echo "MySQLi connecting to DB: $dbdesc with user:$dbuser pass:$dbpass prefix:$tblPrefix at host $dbhost<br>";
	$con = mysqli_connect($dbhost,$dbuser,$dbpass,$dbname);
	if (mysqli_connect_errno()) { echo "Could not connect to DB $dbname at host $dbhost with user:$dbuser pass:$dbpass with MySQLi: ".mysqli_connect_error()."<br>"; return false;}
	foreach ($tblNames as $tblName) {
		echo "Renaming table `$tblPrefix$tblName` to `$tblPrefix$tblName$addSuffix`";
		if (mysqli_query($con,"RENAME TABLE `$tblPrefix$tblName` TO `$tblPrefix$tblName$addSuffix`;")!==false) echo " <span style='font-weight:bold;color:green'>OK</span><br>"; else {echo " <span style='font-weight:bold;color:red'>Fail</span><br>Failed to rename `$tblPrefix$tblName` table to `$tblPrefix$tblName$addSuffix`".mysqli_error($con)."<br><br>"; mysqli_close($con); return false;}
	}
	echo "<br>\n";
	mysqli_close($con);
	return true;
}

?>
