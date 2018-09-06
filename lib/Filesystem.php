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

namespace PiFotomat\lib;


class Filesystem
{
    public static function listPath($path, $filter = [])
    {
        if (!file_exists($path)) {
            return ['items' => [], 'item_count' => 0];
        }

        $rdi = new \DirectoryIterator($path);
        $iter = new \IteratorIterator($rdi);

         // iterate over _all_ files and folders
        $list = ['dir' => [], 'file' => []];
        foreach ($iter as $file) {
            $name = $file->getFilename();
            // Skip hidden files and directories.
            if ($name[0] === '.') {
                continue;
            }
            // filter files by modification time
            if(!empty($filter['mtime_min']) && $file->getMTime() < $filter['mtime_min']) {
                continue;
            }
            if(!empty($filter['mtime_max']) && $file->getMTime() > $filter['mtime_max']) {
                continue;
            }

            // handle directories and files separately
            if ($file->isDir()) {
                // skip .deleted folders
                if (in_array($name, ['.deleted'])) {
                    continue;
                }
                $list['dir'][$file->getBasename()] = [
                    'type' => 'dir',
                    'name' => $file->getBasename()
                ];
                continue;
            } else {
                $file_path = dirname(substr($file->getPathname(), strlen($path) + 1));
                $key = $file->getBasename();

                $list['file'][$key] = [
                    'type' => 'file',
                    'name' => $file->getBasename(),
                    'file_path' => $file_path,
                    'file_size' => $file->getSize(),
                    'file_mtime' => $file->getMTime()
                ];
            }
        }

        // sort and merge lists
        ksort($list['dir']);
        ksort($list['file']);

        $items = array_merge($list['dir'], $list['file']);

        // prepare result: select items in range
        // note: this is done here, since to get a list sorted by file or folder name,
        //       first all entries need to be gathered, then sorted and then sliced
        $result = [
            'items' => $items, // array_slice($items, $offset, $limit),
            'item_count' => count($items)
        ];
        return $result;
    }
}