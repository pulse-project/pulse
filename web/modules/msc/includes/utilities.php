<?php

/*
 * (c) 2004-2007 Linbox / Free&ALter Soft, http://linbox.com
 * (c) 2007 Mandriva, http://www.mandriva.com
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

function onlyValues($n) {
    return array($n['uuid'], $n['hostname']);
}

function prettyOctetDisplay($num, $base = 1024, $unit = '') {
    if ($unit == '')
        $unit = _T('Bytes', 'msc');
    foreach (array('', 'k', 'M', 'G', 'T', 'P') as $i) {
        if ($num < $base)
            return sprintf(_T('%3.1f %s%s', 'msc'), $num, $i, $unit);
        $num /= $base;
    }
}

?>
