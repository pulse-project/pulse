<?php

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
function formatFileSize($size) {
    $size = intval($size);
    if (floor($size / pow(1024, 3)) > 0)
        return sprintf("%2.2f " . _T('GB', 'backuppc'), $size / pow(1024, 3));
    else if (floor($size / pow(1024, 2)) > 0)
        return sprintf("%2.2f " . _T('MB', 'backuppc'), $size / pow(1024, 2));
    else if (floor($size / 1024) > 0)
        return sprintf("%2.2f " . _T('KB', 'backuppc'), $size / 1024);
    else
        return sprintf("%d " . _T('Bytes', 'backuppc'), $size);
}

require_once("modules/backuppc/includes/xmlrpc.php");
require_once("modules/msc/includes/utilities.php");

global $conf;
$maxperpage = $conf["global"]["maxperpage"];


if (isset($_GET['filter'])) {
    $prm = explode('|mDvPulse|', $_GET['filter']);
    if (count($prm) == 2)
        list($_GET['folder'], $_GET['location']) = $prm;
    else
        list($_GET['folder'], $_GET['location']) = array('/', $_GET['filter']);
}

if (!isset($_GET['location']))
    $_GET['location'] = '';

if (isset($_GET["start"])) {
    $start = $_GET["start"];
} else {
    $start = 0;
}

/*
 * Sometimes, url can be formatted like this:
 *   &sharename=%2Fcygdrive%2Fc%2FDocuments%26Acirc%3B%2520and%26Acirc%3B%2520Settings%2F
 *
 * And this "%26Acirc%3B%25" gives a bad array with some keys who starts with Acirc;_.
 * These bad keys are part of previous good key value, so this function try to fix it.
 *
 * Bad $_GET example:
 * Array
 *   (
 *       [module] => backuppc
 *       [submod] => backuppc
 *       [action] => ajaxBrowseFiles
 *       [filter] =>
 *       [host] => UUID3
 *       [sharename] => /cygdrive/c/Documents
 *       [Acirc;_and] =>
 *       [Acirc;_Settings/] =>
 *       [backupnum] => 0
 *       [location] =>
 *       [maxperpage] => 20
 *       [folder] => /
 *   )
 */

function fixBadGet($get) {
    $previous_key = '';
    foreach ($get as $key => $value) {
        if(startsWith($key, 'Acirc;_')) {
            $get[$previous_key] = $get[$previous_key] . str_replace('Acirc;_', ' ', $key);
        }
        else {
            $previous_key = $key;
        }
    }
    return $get;
}

if (isset($_GET['host'], $_GET['sharename'], $_GET['backupnum'])) {
    $_GET = fixBadGet($_GET);

    $folder = (isset($_GET['folder']) && trim($_GET['folder']) != '//') ? urldecode($_GET['folder']) : '/';
    $response = list_files($_GET['host'], $_GET['backupnum'], $_GET['sharename'], $folder, $_GET['location']);

    $z = '';
    $z = _T('Current directory :  ', 'backuppc');
    $z .= sprintf('<a href="#" onclick="BrowseDir(\'/\')">%s</a>', $_GET['sharename']);

    $asc_folder = '/';
    $a_folder = explode('/', $folder);
    foreach ($a_folder as $s)
        if ($s) {
            $asc_folder .= $s . '/';
            $z .= sprintf(' &gt; <a href="#" onclick="BrowseDir(\'%s\')">%s</a>', $asc_folder, $s);
        }

    // Check if error occured
    if ($response['err']) {
        new NotifyWidgetFailure(nl2br($response['errtext']));
        return;
    }

    $files = $response['data'];

    $names = $files[0];
    $paths = $files[1];
    $types = $files[3];
    $sizes = $files[5];
    $cssClasses = array();

    $emptyAction = new EmptyActionItem();
    $viewVersionsAction = new ActionPopupItem(_T("View all versions"), "viewFileVersions", "display", "dir", "backuppc", "backuppc");
    $viewVersionsActions = array();


    $params = array();
    for ($i = 0; $i < count($names); $i++) {
        $params[] = array('host' => $_GET['host'], 'backupnum' => $_GET['backupnum'], 'sharename' => $_GET['sharename'], 'dir' => $paths[$i]);
        $sizes[$i] = formatFileSize($sizes[$i]);
        if ($types[$i] == 'dir') {
            $names[$i] = '<a href="#" onclick="BrowseDir(\'' . $paths[$i] . '\')">' . $names[$i] . "</a>";
            $cssClasses[$i] = 'folder';
            $sizes[$i] = '';
            $params[$i]['isdir'] = '1';
            $viewVersionsActions[] = $emptyAction;
        } else {
            $param_str = "host=" . $_GET['host'] . "&backupnum=" . $_GET['backupnum'] . "&sharename=" . urlencode($_GET['sharename']);
            $param_str.= "&dir=" . urlencode($paths[$i]);
            $names[$i] = '<a href="#" onclick="RestoreFile(\'' . $param_str . '\')">' . $names[$i] . "</a>";
            $cssClasses[$i] = 'file';

            $viewVersionsActions[] = $viewVersionsAction;
        }
        $names[$i] = sprintf('<input type="checkbox" name="f%d" value="%s" /> &nbsp;&nbsp;', $i, $paths[$i]) . $names[$i];
    }

    if ($folder != '/') {
        $parentfolderlink = '<a href="#" onclick="BrowseDir(\'' . dirname($folder) . '/\')">.. (Parent dir)</a>';
        $names = array_merge(array($parentfolderlink), $names);
        $cssClasses = array_merge(array('folder'), $cssClasses);
        $sizes = array_merge(array(''), $sizes);
        $params = array_merge(array(''), $params);
        $viewVersionsActions = array_merge(array($emptyAction), $viewVersionsActions);
    }

    $count = count($names);

    $n = new OptimizedListInfos($names, $z);
    $n->disableFirstColumnActionLink();
    $n->addExtraInfo($sizes, _T("Size", "backuppc"));
    $n->setMainActionClasses($cssClasses);
    $n->setItemCount($count);
    $filter = $_GET['folder'] . '|mDvPulse|' . $_GET['location'];
    $n->setNavBar(new AjaxNavBar($count, $filter));
    $n->start = isset($_GET['start']) ? $_GET['start'] : 0;
    $n->end = isset($_GET['end']) ? $_GET['end'] : $maxperpage;
    $n->setParamInfo($params); // Setting url params

    $n->addActionItemArray($viewVersionsActions);

    print '<br/><br/><form id="restorefiles" method="post" action="">';
    printf('<input type="hidden" name="host" value="%s" />', $_GET['host']);
    printf('<input type="hidden" name="backupnum" value="%s" />', $_GET['backupnum']);
    printf('<input type="hidden" name="sharename" value="%s" />', $_GET['sharename']);
    printf('<input type="hidden" name="sharedest" id="sharedest" value="%s" />', $_GET['sharename']);
    printf('<input type="hidden" name="dir" id="dir" value="%s" />', $folder);
    print('<input type="hidden"  name="restoredir" id="restoredir" value=""  />');
    $n->display();
}
?>
<input id="btnRestoreZip" type="button" value="<?php print _T('Download selected (ZIP)', 'backuppc'); ?>" class="btnPrimary" />
<input id="btnRestoreDirect1" type="button" value="<?php print _T('Restore to host (overwrite)', 'backuppc'); ?>" class="btnPrimary" />
<input id="btnRestoreDirect2" type="button" value="<?php print _T('Restore to host (subfolder)', 'backuppc'); ?>" class="btnPrimary" />
<input type="button" value="<?php print _T('Advanced restore', 'backuppc'); ?>" class="btnPrimary" onclick="showPopup(event, 'main.php?module=backuppc&submod=backuppc&action=restorePopup&sharename=<?php echo $_GET['sharename'] ?>');
        return false;" />
</form>

<script type="text/javascript">
    jQuery(function() {
        jQuery('input#btnRestoreZip').click(function() {
            form = jQuery('#restorefiles').serialize();

            // Test if no checkbox is checked
            if (jQuery('input[type=checkbox]:checked').length == 0)
            {
                alert('You must select at least on file.');
                return;
            }

            jQuery.ajax({
                type: "POST",
                url: "<?php echo 'main.php?module=backuppc&submod=backuppc&action=restoreZip'; ?>",
                data: form,
                success: function(data) {
                    jQuery('html').append(data);
                    setTimeout("refresh();", 3000);
                }
            });
            return false;

        });

        jQuery('input#btnRestoreDirect1').click(function(){
            jQuery('#restoredir').val('/');
            form = jQuery('#restorefiles').serialize();

            // Test if no checkbox is checked
            if (jQuery('input[type=checkbox]:checked').length == 0)
                {
                    alert('You must select at least on file.');
                    return;
                }

            jQuery.ajax({
                type: "POST",
                url: "<?php  echo 'main.php?module=backuppc&submod=backuppc&action=restoreToHost'; ?>",
                data: form,

                success: function(data){
                    jQuery('html').append(data);
                    setTimeout("refresh();",3000);
            }
            });
            return false;
        });

        jQuery('input#btnRestoreDirect2').click(function(){
            jQuery('#restoredir').val('/Restore_<?php print(date('Y-m-d')); ?>');
            form = jQuery('#restorefiles').serialize();

            // Test if no checkbox is checked
            if (jQuery('input[type=checkbox]:checked').length == 0)
                {
                    alert('You must select at least on file.');
                    return;
                }

            jQuery.ajax({
                type: "POST",
                url: "<?php  echo 'main.php?module=backuppc&submod=backuppc&action=restoreToHost'; ?>",
                data: form,

                success: function(data){
                    jQuery('html').append(data);
                    setTimeout("refresh();",3000);
            }
            });
            return false;
        });
    });

</script>
