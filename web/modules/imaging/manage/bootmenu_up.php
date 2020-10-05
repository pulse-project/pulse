<?php
/*
 * (c) 2004-2007 Linbox / Free&ALter Soft, http://linbox.com
 * (c) 2007-2009 Mandriva, http://www.mandriva.com
 * (c) 2017 Siveo, http://http://www.siveo.net
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

require_once('modules/imaging/includes/includes.php');
require_once('modules/imaging/includes/xmlrpc.inc.php');
require_once("modules/xmppmaster/includes/xmlrpc.php");
$params = getParams();
$location = getCurrentLocation();
$item_uuid = $_GET['itemid'];
$label = urldecode($_GET['itemlabel']);

$ret = xmlrpc_moveItemUpInMenu4Location($location, $item_uuid);

if ($ret) {
    $str = sprintf(_T("Success to move <strong>%s</strong> in the default boot menu", "imaging"), $label);
    xmlrpc_setfromxmppmasterlogxmpp($str,
                                    "IMG",
                                    '',
                                    0,
                                    $label ,
                                    'Manuel',
                                    '',
                                    '',
                                    '',
                                    "session user ".$_SESSION["login"],
                                    'Imaging | Master | Menu | server | Manual');
} else {
    $str = sprintf(_T("Failed to move <strong>%s</strong> in the default boot menu", "imaging"), $label);
    xmlrpc_setfromxmppmasterlogxmpp($str,
                                    "IMG",
                                    '',
                                    0,
                                    $label ,
                                    'Manuel',
                                    '',
                                    '',
                                    '',
                                    "session user ".$_SESSION["login"],
                                    'Imaging | Master | Menu | server | Manual');
    new NotifyWidgetFailure($str);
}
header("Location: " . urlStrRedirect("imaging/manage/bootmenu", $params));
exit;

?>
