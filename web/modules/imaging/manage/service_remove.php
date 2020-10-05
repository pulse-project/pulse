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
require_once('modules/imaging/includes/web_def.inc.php');
require_once("modules/xmppmaster/includes/xmlrpc.php");

$location = getCurrentLocation();

if (isset($_POST["bconfirm"])) {
    $params = getParams();

    $item_uuid = $_POST['itemid'];
    $label = urldecode($_POST['itemlabel']);

    $ret = xmlrpc_removeService($item_uuid, $location, $params);

    // goto images list
    if ($ret[0] and !isXMLRPCError()) {
        $str = sprintf(_T("Service <strong>%s</strong> successfully removed", "imaging"), $label);
        xmlrpc_setfromxmppmasterlogxmpp("Error : ".$str,
                                            "IMG",
                                            '',
                                            0,
                                            $label ,
                                            'Manuel',
                                            '',
                                            '',
                                            '',
                                            "session user ".$_SESSION["login"],
                                            'Imaging | Postinstall | Menu | Configuration | Manual');

        new NotifyWidgetSuccess($str);

        // Synchronize boot menu
        $ret = xmlrpc_synchroLocation($location);
        if (isXMLRPCError()) {
            $str = sprintf(_T("Boot menu generation failed for package server: %s", "imaging"), implode(', ', $ret[1]));
            new NotifyWidgetFailure($str);
        }
        header("Location: " . urlStrRedirect("imaging/manage/service", $params));
        exit;
    } elseif ($ret[0]) {
        header("Location: " . urlStrRedirect("imaging/manage/service", $params));
        exit;
    } else {
        new NotifyWidgetFailure($ret[1]);
    }
}

$params = getParams();
$item_uuid = $_GET['itemid'];
$label = urldecode($_GET['itemlabel']);

$f = new PopupForm(sprintf(_T("Are you sure you want to delete <b>%s</b> Boot Service ?", "imaging"), $label));

$f->push(new Table());

// form preseeding
$f->add(new HiddenTpl("location"),                      array("value" => $location,                      "hide" => True));
$f->add(new HiddenTpl("itemlabel"),                     array("value" => $label,                         "hide" => True));
$f->add(new HiddenTpl("itemid"),                        array("value" => $item_uuid,                     "hide" => True));
$f->add(new HiddenTpl("default_mi_label"),              array("value" => $label,                         "hide" => True));

$f->addValidateButton("bconfirm");
$f->addCancelButton("bback");
$f->display();


?>
