<?php
/**
 * (c) 2004-2007 Linbox / Free&ALter Soft, http://linbox.com
 * (c) 2007 Mandriva, http://www.mandriva.com/
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
require_once('modules/msc/includes/scheduler_xmlrpc.php');
require_once('modules/msc/includes/commands_xmlrpc.inc.php');
require_once("modules/msc/includes/mscoptions_xmlrpc.php");

if (isset($_POST["bconfirm"])) {
    //
    $cmd_id = $_POST['cmd_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    extend_command($cmd_id, $start_date, $end_date);
    return;
}


/* Form displaying */
$f = new PopupForm(_T('Reschedule this command', 'msc'), 'reschedulePopupForm');
$f->add(new HiddenTpl("cmd_id"), array("value" => $_GET['cmd_id'], "hide" => True));

$f->add(new TrFormElement(_T('Start date', 'msc'), new DateTimeTpl('start_date')), array('value' => date("Y-m-d H:i:s")));
$f->add(new TrFormElement(_T('<br/>End date', 'msc'), new DateTimeTpl('end_date')), array('value' => date("Y-m-d H:i:s", time() + web_def_coh_life_time() * 60 * 60)));
$f->addValidateButton("bconfirm");
$f->addCancelButton("bback");
$f->display();
?>
<script type="text/javascript">
    jQuery(function() {
        var $ = jQuery;
        $('form#reschedulePopupForm').submit(function() {
            $.ajax($(this).attr('action'), {
                type: $(this).attr('method'),
                data: $(this).serialize() + '&bconfirm=1'
            }).success(function() {
                window.location.reload();
            });
            return false;
        });
    })
</script>
