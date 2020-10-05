#!/usr/bin/python
# -*- coding: utf-8; -*-
#
# (c) 2007 Mandriva, http://www.mandriva.com/
#
# $Id$
#
# This file is part of Pulse 2.
#
# Pulse 2 is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Pulse 2 is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Pulse 2; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

from mmc.support.mmctools import shLaunchDeferred

def sysPing(ip, port = 22):
    """
    This function all to ping machines. Default port is 22

    :param ip: IP of the machine to ping
    :param port: Port to ping ( default 22 )

    """
    def cb(shprocess):
        return shprocess.exitCode == 0

    if ip == "":
         return False;

    d = shLaunchDeferred("ping -c 1 '%s'" % ip)
    d.addCallback(cb)
    return d
