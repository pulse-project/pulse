# -*- coding: utf-8; -*-
#
# (c) 2013 Mandriva, http://www.mandriva.com/
#
# This file is part of Pulse 2, http://pulse2.mandriva.org
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
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
# MA 02110-1301, USA.

def push_phases():
    from pulse2.scheduler.phases.remote import WOLPhase, UploadPhase, ExecutionPhase
    from pulse2.scheduler.phases.remote import DeletePhase, InventoryPhase
    from pulse2.scheduler.phases.remote import RebootPhase, HaltPhase, DonePhase
    from pulse2.scheduler.phases.remote import WUParsePhase
    from pulse2.scheduler.phases.imaging import PreImagingMenuPhase
    from pulse2.scheduler.phases.imaging import PostImagingMenuPhase

    return [PreImagingMenuPhase,
            WOLPhase,
            PostImagingMenuPhase,
            UploadPhase,
            ExecutionPhase,
            WUParsePhase,
            DeletePhase,
            InventoryPhase,
            RebootPhase,
            HaltPhase,
            DonePhase,
           ]
def pull_phases():
    from pulse2.scheduler.phases.pull import WOLPhase, UploadPhase, ExecutionPhase
    from pulse2.scheduler.phases.pull import DeletePhase, InventoryPhase
    from pulse2.scheduler.phases.pull import RebootPhase, HaltPhase
    from pulse2.scheduler.phases.remote import DonePhase
    from pulse2.scheduler.phases.remote import WUParsePhase

    return [WOLPhase,
            UploadPhase,
            ExecutionPhase,
            WUParsePhase,
            DeletePhase,
            InventoryPhase,
            RebootPhase,
            HaltPhase,
            DonePhase,
           ]

installed_phases = {"push": push_phases(),
                    "pull": pull_phases(),
                   }



__all__ = ["installed_phases"]
