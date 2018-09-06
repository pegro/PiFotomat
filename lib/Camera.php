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

use Cvuorinen\Raspicam\Raspistill;
use FeM\sPof\Config;
use FeM\sPof\Logger;

class Camera
{

    /**
     * @var Raspistill
     */
    private $raspistill;

    private $settings = [];

    private $default_settings = [
        'exposure' => Raspistill::EXPOSURE_AUTO,
        'quality' => 75,
    ];

    private $current_day;

    private $current_daytime;

    private $daytime_history = [];

    private $timelapse_start = 0;

    private $latest_picture;

    public function __construct()
    {
        // some default start settings
        $this->current_day = date('Y-m-d');
        $this->current_daytime = 'day';

        $this->resetSettings();
    }

    /**
     * @param int $framestart
     */
    public function startTimelapse($framestart = 0)
    {
        if($this->getLatestPicture()) {
            $framestart = $this->latest_picture['frame_number'] + 1;
        }
        $this->settings['framestart'] = $framestart;

        // prepare path
        $path = $this->getPath('timelapse', true) . '/images';
        if(!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $filename = Config::getDetail('path', 'filename_prefix', 'image') . '%05d.jpg';
        $interval = Config::getDetail('timelapse_interval', $this->current_daytime, 10);

        Logger::getInstance()->info("starting timelapse!");
        Logger::getInstance()->dump([
            "path" => $path,
            "filename" => $filename,
            "interval" => $interval] + $this->settings);

        $this->raspistill = new Raspistill($this->settings);

        // start a timelapse
        $this->raspistill->startTimelapse($path . '/' . $filename, $interval, 60 * 60 * 24 /* 24h timeout */);

        // reset latest
        unset($this->latest_picture);

        // clear daytime history
        $this->daytime_history = [];

        $this->timelapse_start = time();
    }

    /**
     * @return bool
     * @throws \Exception if raspistill couldn't get killed
     */
    public function stopTimelapse()
    {
        $this->timelapse_start = 0;

        return ($this->raspistill) ? $this->raspistill->stop() : false;
    }

    /**
     * Try to determine latest image taken for the timelapse.
     */
    public function getLatestPicture()
    {
        $search_path = $this->getPath('timelapse', true) . '/images';
        $filepattern = Config::getDetail('path', 'filename_prefix', 'image') . '(\d{5}).jpg';
        $interval = Config::getDetail('timelapse_interval', $this->current_daytime);

        // latest picture has date-independent path
        $filename_latest = $this->getPath('timelapse') . '/latest.jpg';

        // find hardlinked image file, to determine latest frame number
        $command = 'find ' . $search_path . ' -samefile ' . $filename_latest . ' 2>&1';
        exec($command, $linkfiles, $ret);
        if(empty($linkfiles) || $ret) {
            Logger::getInstance()->warning("No latest file: " . var_export($linkfiles, true));
            return false;
        }

        // consider all hardlinks, highest frame number wins
        $file = [];
        foreach($linkfiles as $filename) {
            if (preg_match('/'.$filepattern.'/', $filename, $matches)) {
                $file['filepath'] = $filename;
                $file['timestamp'] = filemtime($filename);
                $file['size'] = filesize($filename);

                $frame_number = (int)ltrim($matches[1], '0');
                if(empty($frame_number)) {
                    $frame_number = 0;
                }
                if(empty($file['frame_number']) || $frame_number > $file['frame_number']) {
                    $file['frame_number'] = $frame_number;
                }
            }
        }

        if(empty($file)) {
            Logger::getInstance()->warning("No matching file found for " . $filename_latest);
            return false;
        }

        Logger::getInstance()->debug(' latest: frame='.$file['frame_number']. ' time='.$file['timestamp'] . ' disk_free='.self::formatFilesize(disk_free_space("/")));

        // check, whether the found picture is new (only after the first iteration)
        if(!empty($this->latest_picture) && !empty($this->daytime_history) && $this->timelapse_start) {
            // check whether that's the same file as last time?!
            if($this->latest_picture['filepath'] == $file['filepath'] || $this->latest_picture['frame_number'] == $file['frame_number']) {
                // calculate time since last picture / timelapse start, in case of no picture was taken yet
                $seconds_since_last_picture = time() - max($this->latest_picture['timestamp'] , $this->timelapse_start);
                Logger::getInstance()->debug("No new picture since " . $seconds_since_last_picture . " seconds.");

                if($seconds_since_last_picture > 3 * $interval) {
                    Logger::getInstance()->error("No new picture found for 3 intervals now. I guess the camera hangs. Reboot?!");
                    //exec('sudo sync && sudo reboot');
                }

                return false;
            }
        }

        $this->latest_picture = $file;
        return true;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function checkDaytime()
    {
        if(!$this->raspistill->isRunning()) {
            Logger::getInstance()->error("raspistill is not running! Trying to restart it...");

            $this->restartTimelapse();

            sleep(1);

            if(!$this->raspistill->isRunning()) {
                Logger::getInstance()->emergency('Cannot start raspistill!');
                exit(2);
            }
        }

        if(!$this->getLatestPicture()) {
            return false;
        }

        $latest_daytime = $this->getDaytimeFromImage($this->latest_picture['filepath']);
        $day_change = $this->checkDayChange();

        // restart timelapse at midnight, or on daytime changes
        if ($day_change || $this->hasDaytimeChanged($latest_daytime)) {
            $this->restartTimelapse($day_change);
        }
    }

    public function waitForPicture()
    {
        $interval = Config::getDetail('camera', 'interval', 10);

        // check if there is a latest picture, and if whether we found a latest picture from an older timelapse
        if (empty($this->latest_picture) || $this->latest_picture['timestamp'] < $this->timelapse_start) {
            // no latest for this timelapse -> sleep for half an interval
            $sleep = ceil($interval / 2);

            Logger::getInstance()->warning("No latest picture found for this timelapse. Waiting for " . $sleep . " seconds...");
        } else {
            // minimal sleep duration depends on current daytime
            $sleep = Config::getDetail('min_sleep', $this->current_daytime, 2);

            // only extend sleep duration, if latest picture is recent enough. otherwise just sleep for the moment
            $seconds_until_next = $this->latest_picture['timestamp'] + $interval - time();
            if ($seconds_until_next > 0) {
                $sleep += $seconds_until_next;
            }
        }

        Logger::getInstance()->debug('Sleeping for ' . $sleep . ' seconds');
        sleep($sleep);
    }

    private function checkDayChange()
    {
        $date = date('Y-m-d');

        // day change?
        if($this->current_day != $date) {
            Logger::getInstance()->info('First timelapse today! Reset frame number! New date: ' . $date);
            $this->current_day = $date;

            return true;
        }

        return false;
    }

    private function resetSettings()
    {
        $this->settings = $this->default_settings;

        // new settings
        /*
         * day:
         *   camera.exposure_mode = 'auto'
         *   camera.awb_mode = 'auto'
         * twilight:
         *   camera.exposure_mode = 'night' # or 'verylong'?
         *   camera.iso = nightMaxISO
         *   camera.shutter_speed
         * */

        /* NOTE: disabled switching to night mode, since the noise makes the pictures 3 times as large as during the day
        switch($this->current_daytime) {
            case "twilight":
                $this->settings['exposure'] = Raspistill::EXPOSURE_NIGHT;
                break;
            case "dark":
            case "black":
                $this->settings['exposure'] = Raspistill::EXPOSURE_VERYLONG;
        }
        */

        $this->settings['linkLatest'] = $this->getPath('timelapse') . '/latest.jpg';
    }

    /**
     * Calculate the average pixel values for the specified image
     * used for determining day/night or twilight conditions
     *
     * @param $image_filepath
     * @return float|int
     * @throws \Exception
     */
    private function getPixelAverage($image_filepath)
    {
        if(!file_exists($image_filepath)) {
            throw new \Exception('image doesn\'t exist');
        }

        $info = getimagesize($image_filepath);
        $mime = $info['mime'];
        switch ($mime) {
            case 'image/jpeg':
                $image_create_func = 'imagecreatefromjpeg';
                break;
            case 'image/png':
                $image_create_func = 'imagecreatefrompng';
                break;
            case 'image/gif':
                $image_create_func = 'imagecreatefromgif';
                break;
            default:
                throw new \Exception('invalid image mime type');
        }
        $avg = $image_create_func($image_filepath);

        list($width, $height) = getimagesize($image_filepath);

        // resample to a picture of size 1x1
        $tmp = imagecreatetruecolor(1, 1);
        imagecopyresampled($tmp, $avg, 0, 0, 0, 0, 1, 1, $width, $height);

        // get color
        $rgb = imagecolorat($tmp, 0, 0);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        // average over all colors
        $picture_average = floor(($r + $g + $b) / 3);

        return $picture_average;
    }

    /**
     * Determine daytime of when a picture was taken based on a global pixel average.
     *
     * @param $image_filepath string path to image to analyze
     * @return bool|string
     * @throws \Exception
     */
    private function getDaytimeFromImage($image_filepath)
    {
        $pixel_average = $this->getPixelAverage($image_filepath);

        $daytime_thresholds = Config::get('daytime_thresholds');

        $latest_daytime = 'black';
        foreach ($daytime_thresholds as $daytime => $threshold) {
            if ($pixel_average >= $threshold) {
                $latest_daytime = $daytime;
                break;
            }
        }
        Logger::getInstance()->info('average: ' . $pixel_average . ' => daytime: ' . $latest_daytime);

        return $latest_daytime;
    }

    private function hasDaytimeChanged($latest_daytime)
    {
        // add new daytime value to history
        $this->daytime_history[] = $latest_daytime;

        // shorten history, to keep fixed window size
        $daytime_check_window = Config::getDetail('camera', 'daytime_check_window');
        if(count($this->daytime_history) > $daytime_check_window) {
            $this->daytime_history = array_slice($this->daytime_history, -1 * $daytime_check_window);
        }

        // determine majority daytime value
        $values_count = array_count_values($this->daytime_history);
        arsort($values_count);
        reset($values_count);

        $majority_daytime = key($values_count);

        // has daytime changed?
        if($majority_daytime != $this->current_daytime) {
            Logger::getInstance()->info("daytime has changed: ".$this->current_daytime." -> ".$majority_daytime);
            $this->current_daytime = $latest_daytime;
            return true;
        }

        return false;
    }

    private function getPath($type = 'timelapse', $append_date = false)
    {
        $path = self::getBasePath($type);

        if($append_date) {
            $path .= '/' . $this->current_day;
        }

        return $path;
    }

    private function restartTimelapse($reset_frame_number = false)
    {
        // stop raspistill
        $this->stopTimelapse();

        // set settings according to current day time
        $this->resetSettings();

        // restart timelapse after adjusting settings
        $this->startTimelapse((!empty($this->latest_picture) && !$reset_frame_number) ? $this->latest_picture['frame_number'] + 1 : 0);
    }

    public static function formatFilesize($bytes, $decimals = 2) {
        $sz = 'BKMGTP';
        $factor = (int)floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }

    public static function getBasePath($type = 'timelapse') {
        return Config::getDetail('path', 'root') . '/' . Config::getDetail('path', $type, '');
    }
}