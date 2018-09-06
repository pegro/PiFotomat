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
 *
 */
namespace PiFotomat\view;

use FeM\sPof\Application;
use FeM\sPof\Router;
use FeM\sPof\Session;
use FeM\sPof\template\HtmlTemplate;

abstract class AbstractHtmlView extends \FeM\sPof\view\AbstractHtmlView
{

    /**
     * {@inheritdoc}
     *
     * @return \PiFotomat\view\PortalView
     */
    public static function handleNoViewFound()
    {
        http_response_code(self::HTTP_CODE_NOT_FOUND);
        return new PortalView();
    } // function

    /**
     * Setup Html View, prepare menus
     */
    public function initializeViewtype()
    {
        $this->addStylesheet('main');
        $this->addStylesheet(Application::$FILE_ROOT.'components/font-awesome/css/font-awesome.css');

        // template default values
        $this->assign('thisuser', Session::getUser());

        $this->initialize();

        HtmlTemplate::getInstance()->addPluginsDir([ Application::$FILE_ROOT.'lib/template/smarty_plugins/' ]);

        $nav_tabs = [];

        $nav_tabs[] = [
            'name' => 'Bilderverzeichnis',
            'url' => Router::reverse('browser_index')
        ];

        $nav_tabs[] = [
            'name' => 'Log',
            'url' => Router::reverse('log')
        ];
        
/*        $nav_tabs[] = array(
            'name' => 'Recent Productions',
            'url' => Router::reverse('production_index')
        );

        // Navtab: Livestream
        $streams = array(
            ['name' => 'iSTUFF Livestream', 'route' => 'header'],

            ['name' => 'Watch live', 'route' => Router::reverse('mediathek_watch', ['asset_sid' => '80wfHYDC8krnael0mupk', 'title' => 'iSTUFF Live'])],
        );

        if(Authorization::getInstance()->hasPermission('FeMCI.CTCC.Playout.Log')) {
            $streams[] = ['name' => 'Playout log', 'route' => Router::reverse('ctcc_playoutlog')];
        }

        if(Session::isLoggedIn()) {
            $streams = array_merge($streams, [
                ['name' => 'Other streams', 'route' => 'header'],
                ['name' => 'All live streams', 'route' => Router::reverse('streams_index')]
            ]);
        }

        $nav_tabs[] = array(
            'name' => '<span class="label label-danger" style="font-size: small; vertical-align: middle">LIVE</span>',
            'items' => $streams
        );

        // Navtab: Mediathek
        $nav_tabs[] = array(
            'name' => 'Mediathek',
            'url' => Router::reverse('mediathek_index')
        );

        if(Authorization::getInstance()->hasPermission('FeMCI.Mountpoint.Files.List')) {
            $cdn = [];

            if(Authorization::getInstance()->hasPermission('FeMCI.Asset.Add')) {
                $cdn[] = ['route' => 'header', 'name' => 'Assets'];
                $cdn[] = ['route' => Router::reverse('asset_add'), 'name' => '<i class="fa fa-plus"></i> Create asset'];
                $cdn[] = ['route' => Router::reverse('asset_list_recent'), 'name' => 'Recently created assets'];
                $cdn[] = ['route' => Router::reverse('asset_list_unassigned'), 'name' => 'Unassigned assets'];
            }

            $cdn[] = ['route' => 'header', 'name' => 'File browser'];
            foreach (Mountpoint::getAll() as $mountpoint) {
                $cdn[] = ['name' => $mountpoint['name'], 'route' => Router::reverse('mountpoint_files', ['mountpoint_id' => $mountpoint['id']])];
            }

            if (Authorization::getInstance()->hasPermission('FeMCI.Publishing.Ticket')) {
                $cdn[] = ['route' => 'header', 'name' => 'Publishing'];
                $cdn[] = ['route' => Router::reverse('publishing_ticket_list'), 'name' => 'Publishing tickets'];
            }
            if (Authorization::getInstance()->hasPermission('FeMCI.Publishing.Profile')) {
                $cdn[] = ['route' => Router::reverse('publishing_profile_list'), 'name' => 'Publishing profiles'];
            }

            if(Authorization::getInstance()->hasPermission('FeMCI.Mountpoint.Crawler.Log')) {
                $cdn[] = ['route' => 'header', 'name' => 'Info'];
                $cdn[] = ['route' => Router::reverse('crawler_log'), 'name' => 'Crawler logs'];
            }

            $nav_tabs[] = array(
                'name' => 'Media content',
                'items' => $cdn
            );
        } */

        $this->assign('nav_tabs',$nav_tabs);

        $nav_tabs_right = [];

        /*
        if(Session::isLoggedIn()) {
            // Queue
            if(Authorization::getInstance()->hasPermission('FeMCI.Collection')) {
                $queue = Collection::getUserQueue();
                $nav_tabs_right[] = [
                    'name' => '<i class="fa fa-shopping-basket"></i>' . (!empty($queue) ? ' <span class="badge">' . $queue['element_count'] . '</span>' : ''),
                    'url' => Router::reverse('collection_file_listing', ['collection_id' => $queue['id']])
                ];
            }

            // Processing
            if(Authorization::getInstance()->hasPermission('FeMCI.Processing')) {
                $pending = AssetFilePending::getCount();
                $nav_tabs_right[] = [
                    'name' => '<i class="fa fa-spinner"></i>' . (!empty($pending) ? ' <span class="badge">' . $pending . '</span>' : ''),
                    'url' => Router::reverse('processing_index')
                ];
            }

            // Administration
            $tab = [];
            $tab[] = ['route' => 'header', 'name' => 'FeMCI'];
            $tab[] = ['route' => Router::reverse('admin_user_index'), 'name' => '<i class="fa fa-user"></i> User ' .
                (Authorization::getInstance()->hasPermission('FeMCI.User') ? 'manager' : 'list')];

            if(Authorization::getInstance()->hasPermission('FeMCI.Task')) {
                $tab[] = ['route' => Router::reverse('task_listing'), 'name' => '<i class="fa fa-clock-o"></i> Task manager'];
            }
            if(Authorization::getInstance()->hasPermission('FeMCI.Server')) {
                $tab[] = ['route' => Router::reverse('server_index'), 'name' => '<i class="fa fa-cloud"></i> Servers'];
            }
            if(Authorization::getInstance()->hasPermission('FeMCI.Mountpoint')) {
                $tab[] = ['route' => Router::reverse('mountpoint_index'), 'name' => '<i class="fa fa-folder-o"></i> Mountpoints'];
            }

            if(Authorization::getInstance()->hasPermission('FeMCI.Overlay.List')) {
                $tab[] = ['route' => 'header', 'name' => 'Publishing'];
                $tab[] = ['route' => Router::reverse('overlay_index'), 'name' => '<i class="fa fa-star-half-o"></i> Overlays'];
            }

            if(Authorization::getInstance()->hasPermission('FeMCI.Sender')) {
                $tab[] = ['route' => 'header', 'name' => 'Sender'];
                $tab[] = ['route' => Router::reverse('sender_program'), 'name' => '<i class="fa fa-podcast"></i> Program switcher'];
                if(Authorization::getInstance()->hasPermission('FeMCI.CTCC')) {
                    $tab[] = ['route' => Router::reverse('ctcc_overlay'), 'name' => '<i class="fa fa-star-half-o"></i> Program overlay'];
                }
                $tab[] = ['route' => Router::reverse('ctcc_playoutlog'), 'name' => '<i class="fa fa-calendar-check-o"></i> Playout log'];
            }
            if(Authorization::getInstance()->hasPermission('FeMCI.Studio')) {
                $tab[] = ['route' => 'header', 'name' => 'Studio'];
                $tab[] = ['route' => Router::reverse('ctcc_video_matrix'), 'name' => '<i class="fa fa-random"></i> Video routing'];
            }
            if(Authorization::getInstance()->hasPermission('FeMCI.CTCC')) {
                $tab[] = ['route' => 'header', 'name' => 'Remote control'];
                $tab[] = ['route' => Router::reverse('ctcc_proxy', ['app' => 'power_switch']), 'name' => '<i class="fa fa-plug"></i> IP Power Switch', 'target' => '_blank'];
                $tab[] = ['route' => Router::reverse('ctcc_proxy', ['app' => 'fa9500']), 'name' => '<i class="fa fa-recycle"></i> Signal processor', 'target' => '_blank'];
                $tab[] = ['route' => Router::reverse('ctcc_proxy', ['app' => 'asi2ip_ml2']), 'name' => '<i class="fa fa-sign-out"></i> ASI -> IP', 'target' => '_blank'];
                $tab[] = ['route' => Router::reverse('ctcc_proxy', ['app' => 'asi2ip_vogelherd']), 'name' => '<i class="fa fa-sign-in"></i> IP -> ASI', 'target' => '_blank'];
            }
            if(Authorization::getInstance()->hasPermission('FeMCI.Filesharing')) {
                $tab[] = ['route' => 'header', 'name' => 'File sharing'];
                $tab[] = ['route' => Router::reverse('filesharing_ftp_user_listing'), 'name' => '<i class="fa fa-exchange"></i> FTP Logins'];
            }
            $nav_tabs_right[] = array(
                'name' => '<i class="fa fa-gear"></i>',
                'items' => $tab
            );
        }*/

        $this->assign('nav_tabs_right', $nav_tabs_right);

    } // function

}// class