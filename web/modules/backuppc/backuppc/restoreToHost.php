<?php
/**
 * (c) 2004-2007 Linbox / Free&ALter Soft, http://linbox.com
 * (c) 2007-2008 Mandriva, http://www.mandriva.com
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

require_once("includes/xmlrpc.inc.php");
require_once('modules/backuppc/includes/xmlrpc.php');

$host = $_POST['host'];
$backupnum = $_POST['backupnum'];
$sharename = $_POST['sharename'];
$restoredir = $_POST['restoredir'];
$dir = $_POST['dir'];
$hostdest = $_POST['hostdest'];
$sharedest = $_POST['sharedest'];

$_GET = array_merge($_GET,$_POST);

// unset no-files vars
unset($_POST['host']);
unset($_POST['backupnum']);
unset($_POST['sharename']);
unset($_POST['restoredir']);
unset($_POST['dir']);
unset($_POST['hostdest']);
unset($_POST['sharedest']);

$files = array_values($_POST);

if (count($files) == 0)
{
    new NotifyWidgetFailure(_T('No file selected.','backuppc'));
    die('');
}

$response = restore_files_to_host($host,$backupnum,$sharename,$files,$hostdest,$sharedest,$dir.$restoredir); //

if (!$response['err'])
    new NotifyWidgetSuccess(_T('The selected files are going to be restored to','backuppc').' '.$restoredir);
else
    new NotifyWidgetFailure(nl2br($response['errtext']));

?>
