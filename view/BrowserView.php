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


use FeM\sPof\Config;
use FeM\sPof\exception\NotFoundException;
use FeM\sPof\Logger;
use FeM\sPof\Request;
use PiFotomat\lib\Camera;
use PiFotomat\lib\Filesystem;

class BrowserView extends AbstractHtmlView
{
    public function index()
    {
        $date = Request::getStrParam('date');
        $archive = Request::getBoolParam('archive');
        $hour = Request::getStrParam('hour');

        $times = array_map(function($n) { return sprintf('%02d', $n); }, range(0, 23) );

        $path_sdcard = Camera::getBasePath('timelapse');
        $path_archive = Config::getDetail('path', 'root_external') . '/timelapse';
        if(empty($date)) {
            $listing = Filesystem::listPath($path_sdcard);
            $dates = [
                'sdcard' => [],
                'archive' => []
            ];
            foreach ($listing['items'] as $item) {
                if($item['type'] != 'dir') {
                    continue;
                }

                $dates['sdcard'][] = $item['name'];
            }

            // list archive
            $listing_archive = Filesystem::listPath($path_archive);
            foreach ($listing_archive['items'] as $item) {
                if($item['type'] != 'dir') {
                    continue;
                }

                $dates['archive'][] = $item['name'];
            }

            $this->assign('dates', $dates);
        } elseif(empty($hour)) {
            $this->assign('date', $date);
            $this->assign('archive', $archive);

            $this->assign('times', $times);
        } else {
            $this->assign('date', $date);
            $this->assign('hour', $hour);
            $this->assign('archive', $archive);

            $datetime = \DateTime::createFromFormat('Y-m-d H', $date . ' ' . $hour);
            if(!$datetime) {
                throw new \InvalidArgumentException('Ungültiges Datumsformat!');
            }
            $datetime_unix = (int)$datetime->format('U');

            $path = $archive ? $path_archive : $path_sdcard;
            $path .= '/' . $date . '/images';

            $listing = Filesystem::listPath($path, [
                'mtime_min' => $datetime_unix,
                'mtime_max' => $datetime_unix + 3600
            ]);

            $items = [];
            foreach ($listing['items'] as $item) {
                if($item['type'] == 'dir') {
                    continue;
                }

                $item_hour = strftime('%H', $item['file_mtime']);
                if($item_hour == $hour) {
                    $items[] = $item;
                }
            }
            if(empty($items)) {
                Logger::getInstance()->warning('Keine Bilder für diese Uhrzeit.');
                $this->assign('times', $times);
            } else {
                $this->assign('items', $items);
            }
        }
    }

    public function image()
    {
        $date = Request::getStrParam('date');
        $filename = Request::getStrParam('image');
        $archive = Request::getBoolParam('archive');

        if(empty($date) || empty($filename)) {
            throw new NotFoundException("Kein Bild angegeben.");
        }

        $path_sdcard = Camera::getBasePath('timelapse');
        $path_archive = Config::getDetail('path', 'root_external') . '/timelapse';

        $path = ($archive ? $path_archive : $path_sdcard) . '/' . $date . '/images';
        $fullpath = $path . '/' . $filename;

        $subpath = ($archive ? 'archive' : 'sdcard') . '/timelapse/' . $date . '/images';

        if(!file_exists($fullpath)) {
            throw new NotFoundException("Bild nicht gefunden.");
        }

        $image = [
            'name' => $filename,
            'path' => $subpath,
            'file_mtime' => filemtime($fullpath)
        ];

        $filename_prefix = Config::getDetail('path', 'filename_prefix', 'image');
        $filepattern =  $filename_prefix . '(\d{5}).jpg';
        if(preg_match('/'.$filepattern.'/', $filename, $matches)) {
            $frame_number = (int)ltrim($matches[1], '0');
            if(!empty($frame_number)) {
                for($offset = 1; $offset < 20; $offset++) {
                    $filename_prev = sprintf('%s%05d.jpg', $filename_prefix, $frame_number - $offset);
                    if (file_exists($path . '/' . $filename_prev)) {
                        $image['prev'] = $filename_prev;
                        break;
                    }
                }

                for($offset = 1; $offset < 20; $offset++) {
                    $filename_next = sprintf('%s%05d.jpg', $filename_prefix, $frame_number + $offset);
                    if (file_exists($path . '/' . $filename_next)) {
                        $image['next'] = $filename_next;
                        break;
                    }
                }
            }
        }

        $image_mtime = filemtime($fullpath);
        $this->assign('date', strftime('%Y-%m-%d', $image_mtime));
        $this->assign('hour', strftime('%H', $image_mtime));
        $this->assign('archive', $archive);
        $this->assign('image', $image);
    }
}