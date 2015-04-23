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
 *  JW Player media_player.
 *
 * @package    local_jwplayer
 * @copyright  2014 Ruslan Kabalin, Lancaster University, Johannes Burk <me@jojoob.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/medialib.php');

if (!defined('LOCAL_JWPLAYER_VIDEO_WIDTH')) {
    // Default video width if no width is specified.
    // May be defined in config.php if required.
    define('LOCAL_JWPLAYER_VIDEO_WIDTH', 400);
}
if (!defined('LOCAL_JWPLAYER_AUDIO_WIDTH')) {
    // Default audio width if no width is specified.
    // May be defined in config.php if required.
    define('LOCAL_JWPLAYER_AUDIO_WIDTH', 400);
}
if (!defined('LOCAL_JWPLAYER_AUDIO_HEIGTH')) {
    // Default audio heigth if no heigth is specified.
    // May be defined in config.php if required.
    define('LOCAL_JWPLAYER_AUDIO_HEIGTH', 30);
}

class local_jwplayer_media_player extends core_media_player {

    /**
     * Generates code required to embed the player.
     *
     * @param array $urls URLs of media files
     * @param string $name Display name; '' to use default
     * @param int $width Optional width; 0 to use default
     * @param int $height Optional height; 0 to use default
     * @param array $options Options array
     * use 'subtitles' key with an array of subtitle track files
     * in vtt or srt format indexed by label name.
     * Example: $options['subtitles']['English'] = http://example.com/english.vtt
     * @return string HTML code for embed
     */
    public function embed($urls, $name, $width, $height, $options) {
        global $PAGE, $CFG;

        $output = '';

        // setup sources for playlist item
        $sources = array();
        foreach ($urls as $url) {
            // Add the details for this source.
            $source = array(
                'file' => urldecode($url),
            );
            if (strtolower(pathinfo($url, PATHINFO_EXTENSION)) === 'mov') {
                $source['type'] = 'mp4';
            }
            $sources[] = $source;
        }

        // setup playlistitem
        if (count($sources) > 0) {
            $playerid = 'local_jwplayer_media_player_' . html_writer::random_id();

            $playlistitem = array('sources' => $sources);

            // setup subtitle tracks
            $tracks = null;
            if (isset($options['subtitles'])) {
                $tracks = array();
                foreach ($options['subtitles'] as $label => $subtitlefileurl) {
                    $tracks[] = array(
                        'file' => $subtitlefileurl->out(),
                        'label' => $label);
                }
                $playlistitem['tracks'] = $tracks;
            }

            $playlist = array($playlistitem);

            $setupdata = array(
                'title' => $this->get_name('', $urls),
                'playlist' => $playlist
            );
            
            // If width is not provided, use default.
            if ($width == 0) {
                $width = LOCAL_JWPLAYER_VIDEO_WIDTH;
            }
            $setupdata['width'] = $width;
            // Let player choose the height unless it is provided.
            if ($height != null) {
                $setupdata['height'] = $height;
            }

            // If we are dealing with audio, show just the control bar.
            if (mimeinfo('string', $sources[0]['file']) === 'audio') {
                $setupdata['width'] = LOCAL_JWPLAYER_AUDIO_WIDTH;
                $setupdata['height'] = LOCAL_JWPLAYER_AUDIO_HEIGTH;
            }

            // Set up the player.
            $jsmodule = array(
                'name' => $playerid,
                'fullpath' => '/local/jwplayer/module.js',
            );
            $initparams = array(
                'playerid' => $playerid,
                'setupdata' => $setupdata,
            );

            if (get_config('local_jwplayer', 'downloadbutton')) {
                $initparams['downloadbtn'] = array(
                    'img' => $CFG->wwwroot.'/local/jwplayer/img/download.png',
                    'tttext' => get_string('videodownloadbtntttext', 'local_jwplayer')
                );
            }

            // setup jwplayer library
            $hostingmethod = get_config('local_jwplayer', 'hostingmethod');
            if ($hostingmethod === 'cloud') {
                $proto = (get_config('local_jwplayer', 'securehosting')) ? 'https' : 'http';
                // For cloud-hosted player account token is required.
                if ($accounttoken = get_config('local_jwplayer', 'accounttoken')) {
                    $jwplayer = new moodle_url( $proto . '://jwpsrv.com/library/' . $accounttoken . '.js');
                    $PAGE->requires->js($jwplayer, false);
                }
            } else if ($hostingmethod === 'self') {
                $jwplayer = new moodle_url('/lib/jwplayer/jwplayer.js');
                $PAGE->requires->js($jwplayer, false);

                if ($licensekey = get_config('local_jwplayer', 'licensekey')) {
                    $PAGE->requires->js_init_code("jwplayer.key='" . $licensekey . "'");
                }
            }

            // setup jwplayer instance
            $PAGE->requires->js_init_call('M.local_jwplayer.init', array($initparams), true, $jsmodule);
            
            $playerdiv = html_writer::tag('div', $this->get_name('', $urls), array('id' => $playerid));
            $output .= html_writer::tag('div', $playerdiv, array('class' => 'local_jwplayer_media_player'));
        }

        return $output;
    }

    /**
     * Gets the list of file extensions supported by this media player.
     *
     * @return array Array of strings (extension not including dot e.g. 'mp3')
     */
    public function get_supported_extensions() {
        return explode(',', get_config('local_jwplayer', 'enabledextensions'));
    }

    /**
     * Generates the list of file extensions supported by this media player.
     *
     * @return array Array of strings (extension not including dot e.g. 'mp3')
     */
    public function list_supported_extensions() {
        $video = array('mp4', 'm4v', 'f4v', 'mov', 'flv', 'webm', 'ogv');
        $audio = array('aac', 'm4a', 'f4a', 'mp3', 'ogg', 'oga');
        $streaming = array('m3u8');
        return array_merge($video, $audio, $streaming);
    }

    /**
     * Gets the ranking of this player.
     *
     * See parent class function for more details.
     *
     * @return int Rank
     */
    public function get_rank() {
        return 1;
    }

    /**
     * @return bool True if player is enabled
     */
    public function is_enabled() {
        global $CFG;
        $hostingmethod = get_config('local_jwplayer', 'hostingmethod');
        $accounttoken = get_config('local_jwplayer', 'accounttoken');
        if (($hostingmethod === 'cloud') && empty($accounttoken)) {
            // Cloud mode, but no account token is provided.
            return false;
        }
        $hostedjwplayerpath = $CFG->libdir . '/jwplayer/jwplayer.js';
        if (($hostingmethod === 'self') && !is_readable($hostedjwplayerpath)) {
            // Self-hosted mode, but no jwplayer files.
            return false;
        }
        return true;
    }
}
