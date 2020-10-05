<?
/**
 * (c) 2004-2007 Linbox / Free&ALter Soft, http://linbox.com
 * (c) 2007-2008 Mandriva, http://www.mandriva.com/
 *
 * $Id$
 *
 * This file is part of Mandriva Management Console (MMC).
 *
 * MMC is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * MMC is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MMC; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

require_once("modules/backuppc/includes/xmlrpc.php");
require_once("modules/xmppmaster/includes/xmlrpc.php");

if (!isset($_GET['location']))
   return;
else
    $location = $_GET['location'];

global $conf;
$maxperpage = $conf["global"]["maxperpage"];

if (isset($_GET["start"])) {
    $start = $_GET["start"];
} else {
    $start = 0;
}

$response = get_global_status($location);

// Check if error occured
if ($response['err']) {
    new NotifyWidgetFailure(nl2br($response['errtext']));
    return;
}

$data = $response['data'];

if (count($data) == 0){
    print _T('No entry found.','backuppc');
    return;
}

$cnames = array();
$params = array();

for ($i = 0 ; $i<count($data['hosts']) ; $i++){

	$cn = $data['hosts'][$i];

    if (preg_match('@uuid([0-9]+)@i', $cn, $matches) == 1)
    {
    	$cn_ = getComputersName(array('uuid' => $matches[1]));
	    if (count($cn_)){
			$cn = $cn_[0];
    	    $cnames_ = sprintf('<a href="main.php?module=backuppc&submod=backuppc&action=hostStatus&cn=%s&objectUUID=UUID%s">%s</a>',$cn_[0],$matches[1],$cn_[0]);
		}
        $param_ = array('cn' => $cn, 'objectUUID' => 'UUID'.$matches[1]);
    }
    else{
        $param_ = array('cn' => $data['hosts'][$i]);
		$cnames_ = $cn;
	}


	if (!empty($_GET['filter']) && stripos($cn, $_GET['filter'])===FALSE)
		continue;


	$cnames[] = $cnames_;
	$params[] = $param_;
}

$_SESSION['backup_hosts'] = array_combine($data['hosts'], $cnames);

$count = count($data['hosts']);

$n = new OptimizedListInfos($cnames, _T("Host name", "backuppc"));
$n->addExtraInfo($data['full'], _T("Full number", "backuppc"));
$n->addExtraInfo($data['full_size'], _T("Full size (GB)", "backuppc"));
$n->addExtraInfo($data['incr'], _T("incr. number", "backuppc"));
$n->addExtraInfo($data['last_backup'], _T("Last backup (days)", "backuppc"));
$n->addExtraInfo($data['state'], _T("Current state", "backuppc"));
$n->addExtraInfo($data['last_attempt'], _T("Last message", "backuppc"));

$n->addActionItem(new ActionPopupItem(_T("Start backup"), "startBackup", "start", "host", "backuppc", "backuppc"));
$n->addActionItem(new ActionPopupItem(_T("Stop backup"), "stopBackup", "stop", "host", "backuppc", "backuppc"));
$n->addActionItem(new ActionPopupItem(_T("View errors"), "viewHostLog", "file", "host", "backuppc", "backuppc"));
$n->addActionItem(new ActionConfirmItem(_T("Unset backup", 'backuppc'), "index", "delete", "uuid", "backuppc", "backuppc", _T('Are you sure you want to unset backup for this computer?', 'backuppc')));
$n->setParamInfo($params);

// Get the presence for each machine
$hosts = $data['hosts'];
$presenceHosts = [];
$cssClasses = [];
foreach($hosts as $host)
{
  $status = xmlrpc_getPresenceuuid($host);
  if($status){
    $presenceHosts[$host] = $status;
    $cssClasses[] = "machineNamepresente";
  }
  else {
    $cssClasses[] = "machineName";
  }
}
$n->setMainActionClasses($cssClasses);
$n->setItemCount($count);
$filter1 = $_GET['location'];
$n->setNavBar(new AjaxNavBar($count, $filter1));
$n->start = isset($_GET['start'])?$_GET['start']:0;
$n->end = (isset($_GET['end'])?$_GET['end']:$maxperpage)-1;

print "<br/><br/>"; // to go below the location bar : FIXME, really ugly as line height dependent

$n->display();

?>
