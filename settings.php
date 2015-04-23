<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *  JW Player local settings.
 *
 * @package    local_jwplayer
 * @copyright  2014 Ruslan Kabalin, Lancaster University, 2015 Johannes Burk <me@jojoob.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot.'/local/jwplayer/player.php');
    require_once($CFG->dirroot.'/local/jwplayer/adminlib.php');

    $settings = new admin_settingpage('jwplayer', get_string('pluginname', 'local_jwplayer'));
    $ADMIN->add('localplugins', $settings);

    $jwplayer = new local_jwplayer_media_player();

    // Hosting method.
    $hostingmethodchoice = array(
        'cloud' => get_string('hostingmethodcloud', 'local_jwplayer'),
        'self' => get_string('hostingmethodself', 'local_jwplayer'),
    );
    $settings->add(new local_jwplayer_hostingmethod_setting('local_jwplayer/hostingmethod',
            get_string('hostingmethod', 'local_jwplayer'),
            get_string('hostingmethoddesc', 'local_jwplayer'),
            'cloud', $hostingmethodchoice));

    // Use HTTPS for cloud hosting.
    $settings->add(new admin_setting_configcheckbox('local_jwplayer/securehosting',
            get_string('securehosting', 'local_jwplayer'),
            get_string('securehostingdesc', 'local_jwplayer'),
            0));

    // Account token.
    $settings->add(new local_jwplayer_accounttoken_setting('local_jwplayer/accounttoken',
            get_string('accounttoken', 'local_jwplayer'),
            get_string('accounttokendesc', 'local_jwplayer'),
            ''));

    // License key.
    $settings->add(new admin_setting_configtext('local_jwplayer/licensekey',
            get_string('licensekey', 'local_jwplayer'),
            get_string('licensekeydesc', 'local_jwplayer'),
            ''));

    // Enabled extensions.
    $supportedextensions = $jwplayer->list_supported_extensions();
    $enabledextensionsmenu = array_combine($supportedextensions, $supportedextensions);
    $settings->add(new admin_setting_configmultiselect('local_jwplayer/enabledextensions',
            get_string('enabledextensions', 'local_jwplayer'),
            get_string('enabledextensionsdesc', 'local_jwplayer'),
            $supportedextensions, $enabledextensionsmenu));

    // Download button.
    $settings->add(new admin_setting_configcheckbox('local_jwplayer/downloadbutton',
            get_string('downloadbutton', 'local_jwplayer'),
            get_string('downloadbuttondesc', 'local_jwplayer'),
            0));
}