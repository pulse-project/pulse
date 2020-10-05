# -*- coding: utf-8; -*-
#
# (c) 2004-2007 Linbox / Free&ALter Soft, http://linbox.com
# (c) 2007-2014 Mandriva, http://www.mandriva.com
#
# This file is part of Mandriva Management Console (MMC).
#
# MMC is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# MMC is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MMC.  If not, see <http://www.gnu.org/licenses/>.


import os
import logging
from random import randrange

from mmc.support.config import PluginConfig


class SupportConfig(PluginConfig):
    def __init__(self, name="support", conffile=None):
        self.logger = logging.getLogger()
        if not hasattr(self, 'initdone'):
            PluginConfig.__init__(self, name, conffile)
            self.initdone = True

    def setDefault(self):

        PluginConfig.setDefault(self)

        self.pid_path = "/var/run/pulse2/ssh_support"
        self.ssh_path = "/usr/bin/ssh"
        self.support_url = ""
        self.support_user = "support"
        self.identify_file = "/etc/mmc/plugins/support/id_rsa"
        self.check_pid_delay = 2
        self.session_timeout = 7200

        self.license_server_url = ""
        self.install_id_path = "/etc/pulse-licensing/installation_id"
        self.install_uuid = None

        self.cron_search_for_updates = "0 6 * * *"
        self.license_tmp_file = "/var/lib/mmc/pulse_license_info"
        self.country = "FR"

        self.collector_script_path = "/usr/sbin/pulse2-collect-info"
        self.collector_archive_path = "/tmp/pulse2-collect-info.7z"



    def readConf(self):
        PluginConfig.readConf(self)

        self.pid_path = self.safe_get("main",
                                       "pid_path",
                                       self.pid_path)
        self.ssh_path = self.safe_get("main",
                                       "ssh_path",
                                       self.ssh_path)
        self.support_url = self.safe_get("main",
                                         "support_url",
                                          self.support_url)
        self.support_user = self.safe_get("main",
                                          "support_user",
                                           self.support_user)
        self.identify_file = self.safe_get("main",
                                           "identify_file",
                                           self.identify_file)

        if not os.path.exists(self.identify_file):
            logging.getLogger().warn("File %s don't exists!" % self.identify_file)

        self.url = "%s@%s" % (self.support_user, self.support_url)

        self.check_pid_delay = int(self.safe_get("main",
                                                 "check_pid_delay",
                                                  self.check_pid_delay))
        self.session_timeout = int(self.safe_get("main",
                                                 "session_timeout",
                                                  self.session_timeout))

        if not os.path.exists(self.install_id_path):
            logging.getLogger().warn("File %s don't exists!" % self.install_id_path)
        else:
            with open(self.install_id_path, "r") as f:
                content = f.readlines()
                #if content:
                if len(content) > 0:
                    self.install_uuid = content[0].strip()


        self.license_server_url = self.safe_get("main",
                                                "license_server_url",
                                                self.license_server_url)

        self.cron_search_for_updates = self.safe_get("main",
                                                     "cron_search_for_updates",
                                                     self.cron_search_for_updates)
        self._cron_randomize()

        self.license_tmp_file = self.safe_get("main",
                                              "license_tmp_file",
                                              self.license_tmp_file)

        self.country = self.safe_get("main",
                                     "country",
                                      self.country)

        self.collector_script_path = self.safe_get("main",
                                                   "collector_script_path",
                                                   self.collector_script_path)
        self.collector_archive_path = self.safe_get("main",
                                                    "collector_archive_path",
                                                     self.collector_archive_path)



    def _cron_randomize(self):
        """
        Updates the minutes section of cron expression with random value.
        This step avoids the saturation of license server on the same time.
        """
        minute = randrange(0,60)

        first_number_end = self.cron_search_for_updates.index(" ")
        expression = "%d %s" % (minute, self.cron_search_for_updates[first_number_end+1:])
        self.logger.debug("License info cron updated to %s" % expression)

        self.cron_search_for_updates = expression
