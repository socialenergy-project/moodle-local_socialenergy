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
 * Local plugin "SocialEnergy"
 *
 * @package   local_socialenergy
 * @copyright 2017 Atanas Georgiev, Sofia University <atanas@fmi.uni-sofia.bg>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_socialenergy;

defined('MOODLE_INTERNAL') || die();

class request {
    public $error = '';
    private $request = null;
    private $requestheaders = '';
    public $response = null;
    private $lastrawresponse = '';
    private $url = null;
    private $method = null;
    private $info = array();

    public function __construct($url, $method = 'GET', $params = null, $header = null) {
        $this->url = $url;
        $this->method = strtoupper($method);
        if (is_array($params)) {
            $this->request = http_build_query($params);
        } else {
            $this->request = $params;
        }
        if (!empty($header)) {
            $this->requestheaders = $header;
        }
    }

    public function send() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        if (!empty($this->requestheaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->requestheaders);
        }
        if ($this->method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->request);
        } else if ($this->method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
            if (!is_null($this->request)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->request);
            }
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);

        $this->lastrawresponse = curl_exec($ch);
        $this->info = curl_getinfo($ch);

        if ($this->lastrawresponse === false) {
            $this->error = curl_error($ch);
            curl_close($ch);
            return false;
        }

        switch ($this->info['http_code']) {
            case 200:
                break;
            default:
                $this->error = $this->lastrawresponse;
                return false;
        }

        $this->response = $this->lastrawresponse;

        return true;
    }

}