<?php

include "inc/config.php";
include "inc/auth.php";
include "inc/functions.php";

$title = "NetSUS Dashboard";

include "inc/header.php";

if ($conf->getSetting("shelluser") != "shelluser") {
	$conf->changedPass("shellaccount");
}

function formatSize($size, $precision = 1) {
	$base = log($size, 1024);
	$suffixes = array('B', 'kB', 'MB', 'GB', 'TB');
	if ($size == 0) {
		return "0 B";
	} else {
		return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
	}
}
?>
			<div style="padding: 0px 20px;">
				<div class="panel panel-default panel-main <?php echo ($conf->getSetting("showsharing") == "false" ? "hidden" : ""); ?>">
					<div class="panel-heading">
						<strong>File Sharing</strong>
					</div>
<?php
function shareExec($cmd) {
	return shell_exec("sudo /bin/sh scripts/shareHelper.sh ".escapeshellcmd($cmd)." 2>&1");
}

$smb_running = (trim(shareExec("getsmbstatus")) === "true");
$afp_running = (trim(shareExec("getafpstatus")) === "true");

$smb_conns = trim(shareExec("smbconns"));
$afp_conns = trim(shareExec("afpconns"));

$shares = array();
$smb_str = trim(shareExec("getSMBshares"));
if ($smb_str != "") {
	foreach(explode("\n", $smb_str) as $value) {
		$share = explode(":", $value);
		if ($share[0] != "NetBoot") {
			array_push($shares, $share[1]);
		}
	}
}
$afp_str = trim(shareExec("getAFPshares"));
if ($afp_str != "") {
	foreach(explode("\n", $afp_str) as $value) {
		$share = explode(":", $value);
		if ($share[0] != "NetBoot" && $share[0] != "NetBootClients" && !in_array($share[1], $shares)) {
			array_push($shares, $share[1]);
		}
	}
}
$shareusage = 4;
foreach ($shares as $share) {
	$shareusage += trim(suExec("getDirSize ".$share));
}
$shareusage = (formatSize($shareusage*1024, 0));

if ($conf->getSetting("sharing") == "") {
	$conf->setSetting("sharing", "disabled");
}
?>
					<div class="panel-body">
						<div class="row">
<?php if ($conf->getSetting("sharing") == "enabled") { ?>
							<!-- Column -->
							<div class="col-xs-4 col-md-2 dashboard-item">
								<a href="sharing.php">
									<p><img src="images/settings/Category.png" alt="File Sharing"></p>
								</a>
							</div>
							<!-- /Column -->

							<!-- Column -->
							<div class="col-xs-4 col-md-2">
								<div class="bs-callout bs-callout-default">
									<h5><strong>Number of Shares</strong></h5>
									<span class="text-muted"><?php echo sizeof($shares); ?></span>
								</div>
							</div>
							<!-- /Column -->

							<!-- Column -->
							<div class="col-xs-4 col-md-2">
								<div class="bs-callout bs-callout-default">
									<h5><strong>Disk Usage</strong></h5>
									<span class="text-muted"><?php echo $shareusage; ?></span>
								</div>
							</div>
							<!-- /Column -->

							<div class="clearfix visible-xs-block visible-sm-block"></div>

							<!-- Column -->
							<div class="col-xs-4 col-md-2 visible-xs-block visible-sm-block"></div>
							<!-- /Column -->

							<!-- Column -->
							<div class="col-xs-4 col-md-2">
								<div class="bs-callout bs-callout-default">
									<h5><strong>SMB Status</strong></h5>
									<span class="text-muted"><?php echo ($smb_running ? $smb_conns." Connection".($smb_conns != "1" ? "s" : "") : "Not Running"); ?></span>
								</div>
							</div>
							<!-- /Column -->

							<!-- Column -->
							<div class="col-xs-4 col-md-2">
								<div class="bs-callout bs-callout-default">
									<h5><strong>AFP Status</strong></h5>
									<span class="text-muted"><?php echo ($afp_running ? $afp_conns." Connection".($afp_conns != "1" ? "s" : "") : "Not Running"); ?></span>
								</div>
							</div>
							<!-- /Column -->
<?php } else { ?>
							<!-- Column -->
							<div class="col-xs-4 col-md-2 dashboard-item">
								<a href="sharingSettings.php">
									<p><img src="images/settings/Category.png" alt="File Sharing"></p>
								</a>
							</div>
							<!-- /Column -->

							<!-- Column -->
							<div class="col-xs-8 col-md-10">
								<div class="bs-callout bs-callout-default">
									<h5><strong>Configure File Sharing</strong> <small>to share files and folders with clients.</small></h5>
									<button type="button" class="btn btn-default btn-sm" onClick="document.location.href='sharingSettings.php'">File Sharing Settings</button>
								</div>
							</div>
							<!-- /Column -->
<?php } ?>
						</div>
						<!-- /Row -->
					</div>
				</div>

				<div class="panel panel-default panel-main <?php echo ($conf->getSetting("showsus") == "false" ? "hidden" : ""); ?>">
					<div class="panel-heading">
						<strong>Software Update Server</strong>
					</div>
<?php
function susExec($cmd) {
	return shell_exec("sudo /bin/sh scripts/susHelper.sh ".escapeshellcmd($cmd)." 2>&1");
}

$sync_status = trim(susExec("getSyncStatus")) == "true" ? true : false;
$sus_branches = trim(susExec("numBranches"));

$last_sync = $conf->getSetting("lastsussync");
if (empty($last_sync)) {
	$last_sync = trim(susExec("getLastSync"));
}
if (empty($last_sync)) {
	$last_sync = "Never";
} else {
	$last_sync = date("Y-m-d H:i:s", $last_sync);
}

$sus_usage = trim(suExec("getDirSize /srv/SUS"));
$sus_usage = (formatSize($sus_usage*1024, 0));

if ($conf->getSetting("sus") == "") {
	if ($last_sync == "Never") {
		$conf->setSetting("sus", "disabled");
	} else {
		$conf->setSetting("sus", "enabled");
	}
}
?>
				<div class="panel-body">
					<div class="row">
<?php if ($conf->getSetting("sus") == "enabled") { ?>
							<!-- Column -->
							<div class="col-xs-4 col-md-2 dashboard-item">
								<a href="SUS.php">
									<p><img src="images/settings/SoftwareUpdateServer.png" alt="Software Update"></p>
								</a>
							</div>
							<!-- /Column -->

							<!-- Column -->
							<div class="col-xs-4 col-md-2">
								<div class="bs-callout bs-callout-default">
									<h5><strong>Last Sync</strong></h5>
									<span class="text-muted"><?php echo $last_sync; ?></span>
								</div>
							</div>
							<!-- /Column -->

							<!-- Column -->
							<div class="col-xs-4 col-md-2">
								<div class="bs-callout bs-callout-default">
									<h5><strong>Sync Status</strong></h5>
									<span class="text-muted"><?php echo ($sync_status ? "Running" : "Not Running"); ?></span>
								</div>
							</div>
							<!-- /Column -->

							<div class="clearfix visible-xs-block visible-sm-block"></div>

							<!-- Column -->
							<div class="col-xs-4 col-md-2 visible-xs-block visible-sm-block"></div>
							<!-- /Column -->

							<!-- Column -->
							<div class="col-xs-4 col-md-2">
								<div class="bs-callout bs-callout-default">
									<h5><strong>Disk Usage</strong></h5>
									<span class="text-muted"><?php echo $sus_usage; ?></span>
								</div>
							</div>
							<!-- /Column -->

							<!-- Column -->
							<div class="col-xs-4 col-md-2">
								<div class="bs-callout bs-callout-default">
									<h5><strong>Number of Branches</strong></h5>
									<span class="text-muted"><?php echo $sus_branches; ?></span>
								</div>
							</div>
							<!-- /Column -->
<?php } else { ?>
							<!-- Column -->
							<div class="col-xs-4 col-md-2 dashboard-item">
								<a href="susSettings.php">
									<p><img src="images/settings/SoftwareUpdateServer.png" alt="Software Update"></p>
								</a>
							</div>
							<!-- /Column -->

							<!-- Column -->
							<div class="col-xs-8 col-md-10">
								<div class="bs-callout bs-callout-default">
									<h5><strong>Configure the Software Update Server</strong> <small>to manage and provide Apple Software Updates for macOS clients.</small></h5>
									<button type="button" class="btn btn-default btn-sm" onClick="document.location.href='susSettings.php'">Software Update Server Settings</button>
								</div>
							</div>
							<!-- /Column -->
<?php } ?>
						</div>
						<!-- /Row -->
					</div>
				</div>

				<div class="panel panel-default panel-main <?php echo ($conf->getSetting("shownetboot") == "false" ? "hidden" : ""); ?>">
					<div class="panel-heading">
						<strong>NetBoot Server</strong>
					</div>
<?php
function netbootExec($cmd) {
	return shell_exec("sudo /bin/sh scripts/netbootHelper.sh ".escapeshellcmd($cmd)." 2>&1");
}

$dhcp_running = (trim(netbootExec("getdhcpstatus")) === "true");
$bsdp_running = (trim(netbootExec("getbsdpstatus")) === "true");

$netbootusage = trim(suExec("getDirSize /srv/NetBoot/NetBootSP0"));
$netbootusage = (formatSize($netbootusage*1024, 0));

$shadowusage = trim(suExec("getDirSize /srv/NetBootClients"));
$shadowusage = (formatSize($shadowusage*1024, 0));

if ($conf->getSetting("netboot") == "") {
	if ($dhcp_running || $bsdp_running) {
		$conf->setSetting("netboot", "enabled");
	} else {
		$conf->setSetting("netboot", "disabled");
	}
}

$nbengine = $conf->getSetting("netbootengine");
if (empty($nbengine)) {
	if ($dhcp_running) {
		$nbengine = "dhcpd";
	} else {
		$nbengine = "pybsdp";
	}
	$conf->setSetting("netbootengine", $nbengine);
}
?>
					<div class="panel-body">
						<div class="row">
<?php if ($conf->getSetting("netboot") == "enabled") { ?>
							<!-- Column -->
							<div class="col-xs-4 col-md-2 dashboard-item">
								<a href="netBoot.php">
									<p><img src="images/settings/NetbootServer.png" alt="NetBoot"></p>
								</a>
							</div>
							<!-- /Column -->

							<!-- Column -->
							<div class="col-xs-4 col-md-2">
								<div class="bs-callout bs-callout-default <?php echo ($nbengine == "pybsdp" ? "" : "hidden"); ?>">
									<h5><strong>BSDP Status</strong></h5>
									<span class="text-muted"><?php echo ($bsdp_running ? "Running" : "Not Running"); ?></span>
								</div>
								<div class="bs-callout bs-callout-default <?php echo ($nbengine == "dhcpd" ? "" : "hidden"); ?>">
									<h5><strong>DHCP Status</strong></h5>
									<span class="text-muted"><?php echo ($dhcp_running ? "Running" : "Not Running"); ?></span>
								</div>
							</div>
							<!-- /Column -->

							<!-- Column -->
							<div class="col-xs-4 col-md-2">
								<div class="bs-callout bs-callout-default">
									<h5><strong>NetBoot Image Size</strong></h5>
									<span class="text-muted"><?php echo $netbootusage; ?></span>
								</div>
							</div>
							<!-- /Column -->

							<div class="clearfix visible-xs-block visible-sm-block"></div>

							<!-- Column -->
							<div class="col-xs-4 col-md-2 visible-xs-block visible-sm-block"></div>
							<!-- /Column -->

							<!-- Column -->
							<div class="col-xs-4 col-md-2">
								<div class="bs-callout bs-callout-default">
									<h5><strong>Shadow File Usage</strong></h5>
									<span class="text-muted"><?php echo $shadowusage;?></span>
								</div>
							</div>
							<!-- /Column -->
<?php } else { ?>
							<!-- Column -->
							<div class="col-xs-4 col-md-2 dashboard-item">
								<a href="netbootSettings.php">
									<p><img src="images/settings/NetbootServer.png" alt="NetBoot"></p>
								</a>
							</div>
							<!-- /Column -->

							<!-- Column -->
							<div class="col-xs-8 col-md-10">
								<div class="bs-callout bs-callout-default">
									<h5><strong>Configure the NetBoot Server</strong> <small>to allow you to host NetBoot images.</small></h5>
									<button type="button" class="btn btn-default btn-sm" onClick="document.location.href='netbootSettings.php'">NetBoot Server Settings</button>
								</div>
							</div>
							<!-- /Column -->
<?php } ?>
						</div>
						<!-- /Row -->
					</div>
				</div>

				<div class="panel panel-default panel-main <?php echo ($conf->getSetting("showproxy") == "false" ? "hidden" : ""); ?>">
					<div class="panel-heading">
						<strong>LDAP Proxy</strong>
					</div>
<?php
function ldapExec($cmd) {
	return shell_exec("sudo /bin/sh scripts/ldapHelper.sh ".escapeshellcmd($cmd)." 2>&1");
}

$ldap_running = (trim(ldapExec("getldapproxystatus")) === "true");

if ($conf->getSetting("ldapproxy") == "") {
	if ($ldap_running) {
		$conf->setSetting("ldapproxy", "enabled");
	} else {
		$conf->setSetting("ldapproxy", "disabled");
	}
}
?>
					<div class="panel-body">
						<div class="row">
<?php if ($conf->getSetting("ldapproxy") == "enabled") { ?>
							<!-- Column -->
							<div class="col-xs-4 col-md-2 dashboard-item">
								<a href="LDAPProxy.php">
									<p><img src="images/settings/LDAPServer.png" alt="LDAP Proxy"></p>
								</a>
							</div>
							<!-- /Column -->

							<!-- Column -->
							<div class="col-xs-4 col-md-2">
								<div class="bs-callout bs-callout-default">
									<h5><strong>LDAP Status</strong></h5>
									<span class="text-muted"><?php echo ($ldap_running ? "Running" : "Not Running"); ?></span>
								</div>
							</div>
							<!-- /Column -->
<?php } else { ?>
							<!-- Column -->
							<div class="col-xs-4 col-md-2 dashboard-item">
								<a href="proxySettings.php">
									<p><img src="images/settings/LDAPServer.png" alt="LDAP Proxy"></p>
								</a>
							</div>
							<!-- /Column -->

							<!-- Column -->
							<div class="col-xs-8 col-md-10">
								<div class="bs-callout bs-callout-default">
									<h5><strong>Configure the LDAP Proxy</strong> <small>as a lightweight proxy that acts as a middleware layer between LDAP clients and LDAP directory servers.</small></h5>
									<button type="button" class="btn btn-default btn-sm" onClick="document.location.href='proxySettings.php'">LDAP Proxy Settings</button>
								</div>
							</div>
							<!-- /Column -->
<?php } ?>
						</div>
						<!-- /Row -->
					</div>
				</div>

			</div>
<?php include "inc/footer.php";?>