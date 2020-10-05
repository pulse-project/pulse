<?php
/*
 * (c) 2004-2007 Linbox / Free&ALter Soft, http://linbox.com
 * (c) 2007-2009 Mandriva, http://www.mandriva.com
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

include('modules/imaging/includes/includes.php');
include('modules/imaging/includes/xmlrpc.inc.php');
require_once("modules/xmppmaster/includes/xmlrpc.php");

$id = $_GET['itemid'];
$label = urldecode($_GET['itemlabel']);
$params = getParams();

if (isset($_GET['gid']))
    $type = 'group';
else
    $type = '';

if ($_POST) {
    $label = $_POST['label'];
    $title = trim($_POST['title']);
    $size = $_POST['media'];
    $image_uuid = $_POST['itemid'];

    if (empty($title)) {
        $msg = _T("Please specify a title for the ISO image file.", "imaging");
        new NotifyWidgetFailure($msg);
        header("Location: " . urlStrRedirect("base/computers/imgtabs/" . $type . "tabimages", $params));
        exit;
    }

    $ret = xmlrpc_imagingServerISOCreate($image_uuid, $size, $title);
    // goto images list
    if ($ret[0] and !isXMLRPCError()) {
        $str = "<h2>" . _T("Create ISO image from master", "imaging") . "</h2>";
        $str .= "<p>";
        $str .= sprintf(_T("The ISO image generation of master <strong>%s</strong> has been launched in background.", "imaging"), $label);
        $str .= "</p><p>";
        $str .= _T("The ISO image file will be stored into the imaging server at the end of the backup.", "imaging");
        $str .= "</p><p>";
        /*
          FIXME: We don't have this status page
          $str .= _T("Please go to the status page to check the iso creation status.", "imaging");
          $str .= "</p><p>";
         */
        $str .= _T("This operation will last according to the master size.", "imaging");
        $str .= "</p>";

        new NotifyWidgetSuccess($str);
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
                                    'Imaging | Image | delete | server | Manual');
        header("Location: " . urlStrRedirect("base/computers/imgtabs/" . $type . "tabimages", $params));
        exit;
    } elseif ($ret[0]) {
        header("Location: " . urlStrRedirect("base/computers/imgtabs/" . $type . "tabimages", $params));
        exit;
    } else {
        new NotifyWidgetFailure($ret[1]);
    }
}
?>
<h2><?php echo sprintf(_T("Generate ISO for <strong>%s</strong>", "imaging"), $label) ?></h2>
<form action="<?php echo urlStr("base/computers/images_iso", $params) ?>" method="post">
    <table>
        <tr><td><?php echo _T('Title', 'imaging'); ?></td><td> <input name="title" type="text" value="" /></td></tr>
        <tr><td colspan="2">
                <p><?php echo _T("Please select media size. If your data exceeds the volume size, several files of your media size will be created.", "imaging"); ?></p>

            </td></tr>
        <tr><td><?php echo _T("Media size", "imaging"); ?></td><td>
                <select name="media">
                    <option value="681574400">CD (650 MB)</option>
                    <option value="734003200">CD (700 MB)</option>
                    <option value="4617089843">DVD (4.3 GB)</option>
                    <option value="8375186227">DVD-DL (7.8 GB)</option>
                    <option value="24696061952">Blu-ray (25 GB)</option>
                    <option value="49392123904">Blu-ray (50 GB)</option>
                </select>
            </td></tr></table>
    <br/><br/>
    <input name="label" type="hidden" value="<?php echo $label; ?>" />
    <input name="itemid" type="hidden" value="<?php echo $id; ?>" />
    <input name="bgo" type="submit" class="btnPrimary" value="<?php echo _T("Start ISO generation", "imaging"); ?>" />
    <input name="bback" type="submit" class="btnSecondary" value="<?php echo _("Cancel"); ?>" onclick="closePopup();
            return false;" />
</form>
