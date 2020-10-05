<?php
/**
 * (c) 2004-2007 Linbox / Free&ALter Soft, http://linbox.com
 * (c) 2007-2008 Mandriva, http://www.mandriva.com
 * (c) 2018 Siveo, http://www.siveo.net/
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
require("localSidebar.php");
require("graph/navbar.inc.php");

require_once("modules/pkgs/includes/xmlrpc.php");
require_once("modules/pkgs/includes/functions.php");
require_once("modules/pkgs/includes/query.php");
require_once("modules/pkgs/includes/class.php");

$p = new PageGenerator(_T("Add package", "pkgs"));
$p->setSideMenu($sidemenu);
$p->display();

// This session variable is used for auto-check upload button
// @see ajaxrefreshPackageTempdir.php
$_SESSION['pkgs-add-reloaded'] = array();


if (isset($_POST['bconfirm'])) {

    $p_api_id = $_POST['p_api'];
    $random_dir = $_SESSION['random_dir'];
    $need_assign = True;
    $mode = $_POST['mode'];
    $level = 0;
    if ($mode == "creation") {
        $level = 1;
    }

    foreach (array('id', 'label', 'version', 'description', 'mode', 'Qvendor', 'Qsoftware',
            'Qversion', 'boolcnd', 'licenses', 'targetos', 'metagenerator') as $post) {
        //$package[$post] = iconv("utf-8","ascii//TRANSLIT",$_POST[$post]);
        $package[$post] = $_POST[$post];
    }
    foreach (array('reboot', 'associateinventory') as $post) {
        $package[$post] = ($_POST[$post] == 'on' ? 1 : 0);
    }
    // Package command
    $package['command'] = array('name' => $_POST['commandname'], 'command' => $_POST['commandcmd']);

    // Simple package: not a bundle
    $package['sub_packages'] = array();

    // Send Package Infos via XMLRPC
    $ret = putPackageDetail($p_api_id, $package, $need_assign);
    $pid = $ret[3]['id'];
    $plabel = $ret[3]['label'];
    $pversion = $ret[3]['version'];

    $package_uuid = "";
    if(isset($_POST['saveList']))
    {
        $saveList = $_POST['saveList'];
        $saveList1 = clean_json($saveList);
        //$saveList1 = iconv("utf-8","ascii//TRANSLIT",$saveList1);

        $package_uuid = $ret[2];
        $result = save_xmpp_json($package_uuid,$saveList1);
    }
    if (!isXMLRPCError() and $ret and $ret != -1) {
        if ($_POST['package-method'] == "upload") {
            $cbx = array($random_dir);
        } else if ($_POST['package-method'] == "package") {
            $cbx = array();

            foreach ($_POST as $post => $v) {
                if (preg_match("/cbx_/", $post) > 0) {
                    $cbx[] = preg_replace("/cbx_/", "", $post);
                }
            }
            if (isset($_POST['rdo_files'])) {
                $cbx[] = $_POST['rdo_files'];
            }
        }
        $ret = associatePackages($p_api_id, $pid, $cbx, $level);
        if (!isXMLRPCError() and is_array($ret)) {
            if ($ret[0]) {
                $explain = '';
                if (count($ret) > 1) {
                    $explain = sprintf(" : <br/>%s", implode("<br/>", $ret[1]));
                }
                //ICI
                $str = sprintf(_T("Files successfully associated with package <b>%s (%s)</b>%s", "pkgs"), $plabel, $pversion, $explain);
                new NotifyWidgetSuccess($str);
                xmlrpc_setfrompkgslogxmpp(  $str,
                                    "IMG",
                                    '',
                                    0,
                                    $explain ,
                                    'Manuel',
                                    '',
                                    '',
                                    '',
                                    "session user ".$_SESSION["login"],
                                    'Packaging | List | Manual');
                header("Location: " . urlStrRedirect("pkgs/pkgs/index", array('location' => base64_encode($p_api_id))));
                exit;
            } else {
                $reason = '';
                if (count($ret) > 1) {
                    $reason = sprintf(" : <br/>%s", $ret[1]);
                }
                $str = sprintf(_T("Failed to associate files%s", "pkgs"), $reason);
                new NotifyWidgetFailure($str);
                xmlrpc_setfrompkgslogxmpp(  $str,
                                    "IMG",
                                    '',
                                    0,
                                    $reason ,
                                    'Manuel',
                                    '',
                                    '',
                                    '',
                                    "session user ".$_SESSION["login"],
                                    'Packaging | List | Manual');
                if($package_uuid != '')
                    remove_xmpp_package($package_uuid);
            }
        } else {
            new NotifyWidgetFailure(_T("Failed to associate files", "pkgs"));
        }
    }
} else {
    // Get number of PackageApi
    $res = getUserPackageApi();

    // set first Package Api found as default Package API
    $p_api_id = $res[0]['uuid'];

    $list_val = $list = array();
    if (!isset($_SESSION['PACKAGEAPI'])) {
        $_SESSION['PACKAGEAPI'] = array();
    }
    foreach ($res as $mirror) {
        $list_val[$mirror['uuid']] = $mirror['uuid'];
        $list[$mirror['uuid']] = $mirror['mountpoint'];
        $_SESSION['PACKAGEAPI'][$mirror['uuid']] = $mirror;
    }

    $span = new SpanElement(_T("Choose package source", "pkgs"), "pkgs-title");

    $selectpapi = new SelectItem('p_api');
    $selectpapi->setElements($list);
    $selectpapi->setElementsVal($list_val);
    $_SESSION['pkgs_selected'] = array_values($list_val)[0];

    $f = new ValidatingForm(array("onchange"=>"getJSON()","onclick"=>"getJSON()"));
    $f->push(new Table());

    // Step title
    $f->add(new TrFormElement("", $span));

    $r = new RadioTpl("package-method");
    $vals = array("package", "upload", "empty");
    $keys = array(_T("Already uploaded on the server", "pkgs"), _T("Upload from this web page", "pkgs"), _T("Make an empty package", "pkgs"));
    $r->setValues($vals);
    $r->setChoices($keys);

    // Package API
    $f->add(
            new TrFormElement("<div id=\"p_api_label\">" . _T("Package API", "pkgs") . "</div>", $selectpapi), array("value" => $p_api_id, "required" => True)
    );

    $f->add(new TrFormElement(_T("Package source", "pkgs"), $r), array());
    $f->add(new TrFormElement("<div id='directory-label'>" . _T("Files directory", "pkgs") . "</div>", new Div(array("id" => "package-temp-directory"))), array());
    $f->add(new HiddenTpl("mode"), array("value" => "creation", "hide" => True));

    $span = new SpanElement(_T("Package Creation", "pkgs"), "pkgs-title");
    $f->add(new TrFormElement("", $span), array());

    // fields

    $fields = array(
        array("label", _T("Name", "pkgs"), array("required" => True, 'placeholder' => _T('<fill_package_name>', 'pkgs'))),
        array("version", _T("Version", "pkgs"), array("required" => True)),
        array('description', _T("Description", "pkgs"), array()),
    );


    if(!isExpertMode())
    {
        $command = _T('Command:', 'pkgs') . '<br /><br />';
        $commandHelper = '<span>' . _T('Pulse will try to figure out how to install the uploaded files.\n\n
        If the detection fails, it doesn\'t mean that the application cannot be installed using Pulse but that you\'ll have to figure out the proper command.\n\n
        Many vendors (Acrobat, Flash, Skype) provide a MSI version of their applications which can be processed automatically by Pulse.\n
        You may also ask Google for the silent installation switches. If you\'re feeling lucky, here is a Google search that may help:\n\n
        <a href="@@GOOGLE_SEARCH_URL@@">Google search</a>', 'pkgs') . '</span>';
        $command = $command . str_replace('\n', '<br />', $commandHelper);
        $cmds = array(
            array('command', _T('Command\'s name : ', 'pkgs'), $command)
        );

        $options = array(
            array('reboot', _T('Need a reboot ?', 'pkgs'))
        );
    }
    $os = array(
        array('win', 'linux', 'mac'),
        array(_T('Windows'), _T('Linux'), _T('Mac OS'))
    );

    foreach ($fields as $p) {
        $f->add(
                new TrFormElement($p[1], new AsciiInputTpl($p[0])), array_merge(array("value" => ''), $p[2])
        );
    }

    foreach ($options as $p) {
        $f->add(
                new TrFormElement($p[1], new CheckboxTpl($p[0])), array("value" => '')
        );
    }

    $oslist = new SelectItem('targetos');
    $oslist->setElements($os[1]);
    $oslist->setElementsVal($os[0]);
    $f->add(
            new TrFormElement(_T('Operating System', 'pkgs'), $oslist), array("value" => '')
    );

    if(isExpertMode())
    {
      $f->add(new HiddenTpl("metagenerator"), array("value" => "expert", "hide" => True));
    }
    else {
      $f->add(new HiddenTpl("metagenerator"), array("value" => "standard", "hide" => True));
    }

    if(isExpertMode())
    {

        $f->add(new HiddenTpl('transferfile'), array("value" => true, "hide" => true));

        $methodtransfer = new SelectItem('methodetransfert');
        $methodtransfer->setElements(['pullcurl','pushrsync']);
        $methodtransfer->setElementsVal(['pullcurl','pushrsync']);
        $f->add(new TrFormElement(_T('Transfer method','pkgs'),$methodtransfer,['trid'=>'trTransfermethod']),['value'=>'']);


        $bpuploaddownload = new IntegerTpl("limit_rate_ko");
        $bpuploaddownload->setAttributCustom('min = 0');
        $f->add(
                new TrFormElement(_T("bandwidth throttling (ko)",'pkgs'), $bpuploaddownload), array_merge(array("value" => ''), array('placeholder' => _T('<in ko>', 'pkgs')))
        );
        //spooling priority
        $rb = new RadioTpl("spooling");
        $rb->setChoices(array(_T('high priority', 'pkgs'), _T('ordinary priority', 'pkgs')));
        $rb->setvalues(array('high', 'ordinary'));
        $rb->setSelected('ordinary');
        $f->add(new TrFormElement(_T('Spooling', 'pkgs'), $rb));

        $packagesInOption = '';
        foreach(xmpp_packages_list() as $package)
        {
            $packagesInOption .= '<option value="'.$package['uuid'].'">'.$package['name'].'</option>';
        }
        $f->add(new TrFormElement("Dependencies",new SpanElement('<div id="grouplist">
    <table style="border: none;" cellspacing="0">
        <tr>
            <td style="border: none;">
                <div>
                    <img src="img/common/icn_arrowup.png" alt="|^" id="moveDependencyToUp" onclick="moveToUp()"/><br/>
                    <img src="img/common/icn_arrowdown.png" alt="|v" id="moveDependencyToDown" onclick="moveToDown()"/></a><br/>
                </div>
            </td>
            <td style="border: none;">
                <h3>Added dependencies</h3>
                <div class="list">
                    <select multiple size="13" class="list" name="Dependency" id="addeddependencies">

                    </select>
                </div>
            </td>
            <td style="border: none;">
                <div>
                    <img src="img/common/icn_arrowright.gif" alt="-->" id="moveDependencyToRight" onclick="moveToRight()"/><br/>
                    <img src="img/common/icn_arrowleft.gif" alt="<--" id="moveDependencyToLeft" onclick="moveToLeft()"/></a><br/>
                </div>
            </td>
            <td style="border: none;">
                <div class="list" style="padding-left: 10px;">
                    <h3>Available dependencies</h3>
                    <select multiple size="13" class="list" name="members[]" id="pooldependencies">
                        '.$packagesInOption.'
                    </select>
                </div>
                <div class="clearer"></div>
            </td>
        </tr>
    </table>
</div>',"pkgs")));
    }

    foreach ($cmds as $p) {
        $f->add(
                new HiddenTpl($p[0] . 'name'), array("value" => '', "hide" => True)
        );
        $f->add(
                new TrFormElement($p[2], new TextareaTplArray(["name"=>$p[0] . 'cmd',"required"=>"required"])), array("value" => '')
        );
    }

    foreach (array('Qvendor', 'Qsoftware', 'Qversion') as $k) {
        if (!isset($package[$k])) {
            $package[$k] = '';
        }
    }

    addQuerySection($f, $package);

    $f->pop();
    if(isExpertMode())
    {
        $f->add(new HiddenTpl('saveList'), array('id'=>'saveList','name'=>'saveList',"value" => '', "hide" => True));
        include('addXMPP.php');
    }

    $f->addValidateButton("bconfirm", _T("Add", "pkgs"));
    $f->display();
}

?>

<script src="modules/pkgs/lib/fileuploader/fileuploader.js"
    type="text/javascript"></script>
<!-- js for file upload -->
<link href="modules/pkgs/lib/fileuploader/fileuploader.css"
    rel="stylesheet" type="text/css">
<!-- css for file upload -->

<script type="text/javascript">
    jQuery(function() { // load this piece of code when page is loaded
        jQuery('.label span a').each(function() {
            jQuery(this).attr('href', 'http://www.google.com/#q=file.exe+silent+install');
            jQuery(this).attr('target', '_blank');
            return false; // break the loop
        });
        /*
         * Auto fill fields of form
         * if tempdir is empty (when changing packageAPI)
         * default tempdir will be chosen in ajaxGetSuggestedCommand
         * php file.
         */
        function fillForm(selectedPapi, tempdir) {
            url = '<?php echo urlStrRedirect("pkgs/pkgs/ajaxGetSuggestedCommand") ?>&papiid=' + selectedPapi;
            if (tempdir != undefined) {
                url += '&tempdir=' + tempdir;
            }

            jQuery.ajax({
                'url': url,
                type: 'get',
                success: function(data) {
                    jQuery('#version').val(data.version);
                    jQuery('#commandcmd').val(data.commandcmd);

                }
            });
        }
        /*
         * Refresh Package API tempdir content
         * When called, display available packages
         * in package API tempdir
         */
        function refreshTempPapi() {
            var packageMethodValue = jQuery('input:checked[type="radio"][name="package-method"]').val();
            var selectedPapi = jQuery("#p_api").val();
            if (packageMethodValue == "package") {
                /*new Ajax.Updater('package-temp-directory', '<?php echo urlStrRedirect("pkgs/pkgs/ajaxRefreshPackageTempDir") ?>&papi=' + selectedPapi, {
                 method: "get",
                 evalScripts: true,
                 onComplete: fillForm(selectedPapi)
                 });*/

                jQuery('#package-temp-directory').load('<?php echo urlStrRedirect("pkgs/pkgs/ajaxRefreshPackageTempDir") ?>&papi=' + selectedPapi, function() {
                    fillForm(selectedPapi);
                });

            }
            else {
                /*new Ajax.Updater('package-temp-directory', '<?php echo urlStrRedirect("pkgs/pkgs/ajaxDisplayUploadForm") ?>&papi=' + selectedPapi, {
                 method: "get",
                 evalScripts: true
                 });*/

                jQuery('#package-temp-directory').load('<?php echo urlStrRedirect("pkgs/pkgs/ajaxDisplayUploadForm") ?>&papi=' + selectedPapi);

                // reset form fields
                jQuery('#version').val("");
                jQuery('#commandcmd').val("");
            }

            return selectedPapi;
        }

        // on page load, display available temp packages
        var selectedPapi = refreshTempPapi();

        // When change Package API, update available temp packages
        jQuery('#p_api').change(function() {
            selectedPapi = refreshTempPapi();
        });

        jQuery('input[name="package-method"]').click(function() {

            // display temp package or upload form
            // according to package-method chosen ("package" or "upload")
            var selectedValue = jQuery('input:checked[type="radio"][name="package-method"]').val();
            if (selectedValue == "package") {
                selectedPapi = refreshTempPapi();
                jQuery('#directory-label').html("<?php echo _T("Files directory", "pkgs") ?>");
                jQuery('#directory-label').parent().parent().fadeIn();
            }
            else if (selectedValue == "empty") {
                var jcArray = new Array('label', 'version', 'description', 'commandcmd');
                for (var dummy in jcArray) {
                    try {
                        jQuery('#' + jcArray[dummy]).css("background", "#FFF");
                        jQuery('#' + jcArray[dummy]).removeAttr('disabled'); // TODO: Check if no error here
                    }
                    catch (err) {
                        // this php file is prototype ajax request with evalscript
                        // enabled.
                    }
                }
                jQuery('#directory-label').parent().parent().fadeOut();
            }
            else if (selectedValue == "upload") {
                jQuery('#package-temp-directory').load('<?php echo urlStrRedirect("pkgs/pkgs/ajaxDisplayUploadForm") ?>&papi=' + selectedPapi);
                // reset form fields
                jQuery('#version').val("");
                jQuery('#commandcmd').val("");
                jQuery('#directory-label').html("<?php echo sprintf(_T("Files upload (<b><u title='%s'>%sM max</u></b>)", "pkgs"), _T("Change post_max_size and upload_max_filesize directives in php.ini file to increase upload size.", "pkgs"), get_php_max_upload_size()) ?>");
                jQuery('#directory-label').parent().parent().fadeIn();
            }
        });
    });
<?php
// if one package API, hide field
if (count($list) < 2) {
    echo <<< EOT
            // Hide package api field
            jQuery('#p_api').parents('tr:first').hide();

EOT;
}
?>
</script>
