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

use FeM\sPof\Logger;
use FeM\sPof\Request;
use FeM\sPof\view\AbstractJsonView;

class SettingsView extends AbstractJsonView
{
    protected function settime()
    {
        $time = Request::getIntParam('time');
        if(!is_numeric($time)) {
            self::sendBadRequest();
        }

        $timestr = strftime('%F %T',$time);
        Logger::getInstance()->info('Request to set system time to ' . $timestr);
        Logger::getInstance()->debug('date before: '.strftime('%c'));
        $cmd = 'sudo timedatectl set-time "' . $timestr . '" 2>&1';
        exec($cmd, $out, $ret);
        if($ret) {
            Logger::getInstance()->error('could not set system time: '. implode("\n",$out));
        }
        Logger::getInstance()->debug('cmd='.$cmd.' ret='.$ret.' out='.implode("\n",$out));
        Logger::getInstance()->debug('date after: '.strftime('%c'));

        $this->resultSet = ['success' => ($ret == 0), 'date' => $timestr];
    }
}