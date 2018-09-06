<?php
/**
 * This file is part of PiFotomat.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @copyright 2018 Peter Große
 * @lincense  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link      https://github.com/pegro/pifotomat
 */

namespace PiFotomat\view;

use FeM\sPof\Application;
use PiFotomat\lib\Camera;

class PortalView extends AbstractHtmlView
{
    public function index()
    {
        $filename = Camera::getBasePath('timelapse') . '/latest.jpg';
        $this->assign('latest_mtime', file_exists($filename) ? filemtime($filename) : '<em>Nie</em>');

        $this->assign('pid_raspistill', exec('pidof raspistill'));
        $this->assign('disk_free_sdcard', Camera::formatFilesize(disk_free_space('/')));

        exec('mountpoint /mnt/data',$out, $ret);
        if(!$ret) {
            $this->assign('disk_free_external', Camera::formatFilesize(disk_free_space('/mnt/data')));
        }
    }

    public function log()
    {
        $logfile = Application::$FILE_ROOT . '/log/daemon.log';

        $out = '';
        if(file_exists($logfile)) {
            exec('tail -200 '.$logfile, $out, $ret);
        }
        $this->assign('log', implode("\n", $out));
    }
}