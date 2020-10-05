<?php
/**
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
 * along with MMC.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once("modules/pulse2/includes/utilities.php"); # for quickGet method
require_once("modules/dyngroup/includes/dyngroup.php");

$name = quickGet('groupname');
$gid = quickGet('gid');
$location = quickGet('location');

ob_end_clean();

/* The two following lines make the CSV export works for IE 6.x on HTTPS ! */
header("Pragma: ");
header("Cache-Control: ");
header("Content-type: text/txt");
header('Content-Disposition: attachment; filename="'.$name.'.csv"');

function get_first($val) { return $val[0]; }
function get_second($val) { return _T($val[1], "base"); }
function get_values($h, $values) {
    $ret = array();
    foreach ($h as $k) {
        if (is_array($values[$k])) {
            $ret[] = implode('/', $values[$k]);
        } else {
            $ret[] = $values[$k];
        }
    }
    return $ret;
}

$headers = getComputersListHeaders();
print "\"".implode('","', array_map("get_second", $headers))."\"\n";

$datum = getRestrictedComputersList(0, -1, array('gid'=>$gid, 'location' => $location), False);
foreach ($datum as $machine) {
    print "\"".implode('","', get_values(array_map("get_first", $headers), $machine[1]))."\"\n";
}

exit;

?>
