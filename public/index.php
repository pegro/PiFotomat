<?php
/**
 * This file is part of FeMCI.
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
 * @copyright 2016 Forschungsgemeinschaft elektronische Medien e.V. (http://fem.tu-ilmenau.de)
 * @lincense  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link      http://femci.fem-net.de
 */

require_once dirname(__DIR__) . "/vendor/autoload.php";

// Run the application!
$app = new FeM\sPof\Application('\\PiFotomat\\');
//$app::$CACHE_ROOT = $app::$FILE_ROOT.'../cache/';
$app::$LOGFILE = $app::$FILE_ROOT.'log/web.log';
$app->setDefaultModule('Portal');
$app->dispatch();
