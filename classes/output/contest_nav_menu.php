<?php
// This file is part of Moodle - http://moodle.org/.
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
// You should have received a copy of the GNU General Public License.
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Description
 *
 * @package    mod_bacs
 * @copyright  SybonTeam, sybon.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bacs\output;

use dml_exception;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Class contest_nav_menu
 * @package mod_bacs
 */
class contest_nav_menu implements renderable, templatable {
    /**
     * @var mixed
     */
    public $contestname = "";
    /**
     * @var mixed
     */
    public $coursemoduleidbacs = 0;
    /**
     * @var mixed
     */
    public $usercapabilitiesbacs;

    /**
     * @var mixed
     */
    public $minutesfromstartbacs = 0;
    /**
     * @var mixed
     */
    public $minutestotalbacs = 0;
    /**
     * @var mixed
     */
    public $conteststatusbacs = "";

    /**
     * @var mixed
     */
    public $isolateparticipantsbacs = false;
    /**
     * @var mixed
     */
    public $isolatedparticipantmodeisforcedbacs = false;
    /**
     * @var mixed
     */
    public $isvirtualparticipationdisabledbacs = true;

    /**
     * @var mixed
     */
    public $activetabviewbacs;
    /**
     * @var mixed
     */
    public $activetabtasksbacs;
    /**
     * @var mixed
     */
    public $activetabstatusbacs;
    /**
     * @var mixed
     */
    public $activetabresultsbacs;
    /**
     * @var mixed
     */
    public $activetabanothersresultsbacs;
    /**
     * @var mixed
     */
    public $activetabactionsbacs;
    /**
     * @var mixed
     */
    public $activetabuserdynamicsbacs;
    /**
     * @var mixed
     */
    public $activetabtaskdynamicsbacs;
    /**
     * @var mixed
     */
    public $activetabresultsgraphbacs;
    /**
     * @var mixed
     */
    public $activetabvirtualcontestbacs;
    /**
     * @var mixed
     */
    public $activetabincidentsbacs;

    /**
     * @var mixed
     */
    public $targetuseridbacs;
    /**
     * @var mixed
     */
    public $targetuserlastnamebacs;
    /**
     * @var mixed
     */
    public $targetuserfirstnamebacs;

    /**
     * @var mixed
     */
    public $menushownbacs;

    /**
     * @var mixed
     */
    public $aceeditorshownbacs;
    /**
     * @var mixed
     */
    public $aceeditorthemebacs;
    /**
     * @var mixed
     */
    public $aceeditorredirecturlbacs;

    /**
     * @var mixed
     */
    public $activetabvirtualparticipantsbacs;

    /**
     * @var mixed
     */
    public $activetabstatementsbacs;

    /**
     *
     */
    const HTML_ICON_LIGHTNING =
        '<svg xmlns="http://www.w3.org/2000/svg"
 width="16"
  height="16"
   fill="currentColor"
    class="bi bi-lightning-charge"
     viewBox="0 0 16 16">
            <path d="M11.251.068a.5.5 0 0 1
             .227.58L9.677 6.5H13a.5.5 0 0 1
              .364.843l-8 8.5a.5.5 0 0
               1-.842-.49L6.323 9.5H3a.5.5 0 0
                1-.364-.843l8-8.5a.5.5 0 0 1
                 .615-.09zM4.157 8.5H7a.5.5 0
                  0 1 .478.647L6.11 13.59l5.732-6.09H9a.5.5 0
                   0 1-.478-.647L9.89 2.41 4.157 8.5z"/>
        </svg>';
    /**
     *
     */
    const HTML_ICON_STORM =
        '<svg xmlns="http://www.w3.org/2000/svg"
 width="16"
  height="16"
   fill="currentColor"
    class="bi bi-tropical-storm"
     viewBox="0 0 16 16">
            <path d="M8 9.5a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>
            <path d="M9.5 2c-.9 0-1.75.216-2.501.6A5
             5 0 0 1 13 7.5a6.5 6.5 0 1 1-13
              0 .5.5 0 0 1 1 0 5.5 5.5 0 0 0
               8.001 4.9A5 5 0 0 1
                3 7.5a6.5 6.5 0 0
                 1 13 0 .5.5 0
                  0 1-1 0A5.5 5.5 0
                   0 0 9.5 2zM8 3.5a4 4
                    0 1 0 0 8 4 4 0 0 0 0-8z"/>
        </svg>';
    /**
     *
     */
    const HTML_ICON_STARS =
        '<svg xmlns="http://www.w3.org/2000/svg"
 width="16"
  height="16"
   fill="currentColor"
    class="bi bi-stars"
     viewBox="0 0 16 16">
            <path d="M7.657 6.247c.11-.33.576-.33.686 0l.645
             1.937a2.89 2.89 0 0 0 1.829
              1.828l1.936.645c.33.11.33.576 0
               .686l-1.937.645a2.89 2.89 0 0 0-1.828
                1.829l-.645 1.936a.361.361 0
                 0 1-.686 0l-.645-1.937a2.89 2.89 0 0
                  0-1.828-1.828l-1.937-.645a.361.361 0 0
                   1 0-.686l1.937-.645a2.89 2.89 0 0
                    0 1.828-1.828l.645-1.937zM3.794 1.148a.217.217 0 0 1
                     .412 0l.387
                      1.162c.173.518.579.924
                       1.097 1.097l1.162.387a.217.217
                        0 0 1 0 .412l-1.162.387A1.734
                         1.734 0 0 0 4.593
                          5.69l-.387 1.162a.217.217
                           0 0 1-.412 0L3.407 5.69A1.734
                            1.734 0 0 0 2.31
                             4.593l-1.162-.387a.217.217 0
                              0 1 0-.412l1.162-.387A1.734
                               1.734 0 0 0 3.407 2.31l.387-1.162zM10.863.099a.145.145
                                0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145
                                 0 0 1 0 .274l-.774.258a1.156 1.156 0 0
                                  0-.732.732l-.258.774a.145.145 0 0
                                   1-.274 0l-.258-.774a1.156
                                    1.156 0 0 0-.732-.732L9.1
                                     2.137a.145.145 0 0 1
                                      0-.274l.774-.258c.346-.115.617-.386.732-.732L10.863.1z"/>
        </svg>';
    /**
     *
     */
    const HTML_ICON_SHIELD =
        '<svg xmlns="http://www.w3.org/2000/svg"
        width="16"
        height="16"
        fill="currentColor"
        class="bi bi-shield-exclamation"
        viewBox="0 0 16 16">
            <path d="M5.338
             1.59a61.44 61.44
              0 0 0-2.837.856.481.481
               0 0 0-.328.39c-.554
                4.157.726 7.19
                 2.253 9.188a10.725
                  10.725 0 0
                   0 2.287 2.233c.346.244.652.42.893.533.12.057.218.095.293.118a.55.55
                    0 0 0 .101.025.615.615 0 0
                     0 .1-.025c.076-.023.174-.061.294-.118.24-.113.547-.29.893-.533a10.726 10.726
                      0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48
                       0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552
                        1.29 8.531 1.067 8 1.067c-.53
                         0-1.552.223-2.662.524zM5.072.56C6.157.265
                          7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54
                           1.54 0 0 1 1.044 1.262c.596
                            4.477-.787 7.795-2.465 9.99a11.775 11.775
                             0 0 1-2.517 2.453 7.159 7.159
                              0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158
                               7.158 0 0 1-1.048-.625 11.777
                                11.777 0 0 1-2.517-2.453C1.928
                                 10.487.545 7.169
                                  1.141 2.692A1.54
                                   1.54 0 0
                                    1 2.185 1.43 62.456 62.456 0 0 1 5.072.56z"/>
            <path d="M7.001 11a1 1 0 1
             1 2 0 1 1 0
              0 1-2 0zM7.1 4.995a.905.905 0
               1 1 1.8 0l-.35 3.507a.553.553 0 0
                1-1.1 0L7.1 4.995z"/>
        </svg>';
    /**
     *
     */
    const HTML_ICON_FLAG =
        '<svg class="bi bi-flag-fill"
width="16"
height="16"
viewBox="0 0 16 16"
fill="currentColor"
xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd"
            d="M3.5 1a.5.5 0 0 1 .5.5v13a.5.5 0 0 1-1 0v-13a.5.5 0 0 1 .5-.5z"/>
            <path fill-rule="evenodd"
            d="M3.762 2.558C4.735 1.909 5.348 1.5 6.5 1.5c.653
             0 1.139.325
              1.495.562l.032.022c.391.26.646.416.973.416.168 0
               .356-.042.587-.126a8.89
                8.89 0 0 0
                 .593-.25c.058-.027.117-.053.18-.08.57-.255 1.278-.544 2.14-.544a.5.5 0
                  0 1 .5.5v6a.5.5 0 0
                   1-.5.5c-.638 0-1.18.21-1.734.457l-.159.07c-.22.1-.453.205-.678.287A2.719 2.719 0
                    0 1 9 9.5c-.653 0-1.139-.325-1.495-.562l-.032-.022c-.391-.26-.646-.416-.973-.416-.833
                     0-1.218.246-2.223.916A.5.5 0 0
                      1 3.5 9V3a.5.5 0 0 1 .223-.416l.04-.026z"/>
        </svg>';
    /**
     *
     */
    const HTML_ICON_GEAR =
        '<svg xmlns="http://www.w3.org/2000/svg"
 width="16"
  height="16"
   fill="currentColor"
    class="bi bi-gear-fill"
     viewBox="0 0 16 16">
            <path d="M9.405
             1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464
              1.464 0 0
               1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987
                1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397
                 0 2.81l.34.1a1.464 1.464
                  0 0 1 .872 2.105l-.17.31c-.698 1.283.705
                   2.686 1.987 1.987l.311-.169a1.464 1.464
                    0 0 1 2.105.872l.1.34c.413
                     1.4 2.397
                      1.4 2.81 0l.1-.34a1.464
                       1.464 0 0
                        1 2.105-.872l.31.17c1.283.698
                         2.686-.705 1.987-1.987l-.169-.311a1.464 1.464
                          0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397
                           0-2.81l-.34-.1a1.464 1.464
                            0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464
                             1.464 0 0 1-2.105-.872l-.1-.34zM8
                              10.93a2.929 2.929 0 1
                               1 0-5.86 2.929 2.929
                                0 0 1 0 5.858z"/>
        </svg>';
    /**
     *
     */
    const HTML_ICON_ARROW_RIGHT =
        '<svg xmlns="http://www.w3.org/2000/svg"
 width="16"
  height="16"
   fill="currentColor"
    class="bi bi-arrow-right-circle-fill"
     viewBox="0 0 16 16">
            <path d="M8 0a8 8 0 1
             1 0 16A8 8 0 0 1
              8 0zM4.5 7.5a.5.5 0 0
               0 0 1h5.793l-2.147 2.146a.5.5 0
                0 0 .708.708l3-3a.5.5 0 0
                 0 0-.708l-3-3a.5.5 0 1
                  0-.708.708L10.293 7.5H4.5z"/>
        </svg>';
    /**
     *
     */
    const HTML_ICON_HOURGLASS =
        '<svg xmlns="http://www.w3.org/2000/svg"
 width="16"
  height="16"
   fill="currentColor"
    class="bi bi-hourglass"
     viewBox="0 0 16 16">
            <path d="M2 1.5a.5.5 0
             0 1 .5-.5h11a.5.5 0 0 1 0 1h-1v1a4.5 4.5 0 0 1-2.557
              4.06c-.29.139-.443.377-.443.59v.7c0
               .213.154.451.443.59A4.5 4.5 0 0 1
                12.5 13v1h1a.5.5 0 0 1
                 0 1h-11a.5.5 0 1 1 0-1h1v-1a4.5 4.5 0
                  0 1 2.557-4.06c.29-.139.443-.377.443-.59v-.7c0-.213-.154-.451-.443-.59A4.5
                   4.5 0 0 1 3.5 3V2h-1a.5.5 0
                    0 1-.5-.5zm2.5.5v1a3.5 3.5 0 0
                     0 1.989 3.158c.533.256
                      1.011.791 1.011 1.491v.702c0
                       .7-.478 1.235-1.011 1.491A3.5 3.5 0
                        0 0 4.5 13v1h7v-1a3.5
                         3.5 0 0 0-1.989-3.158C8.978
                          9.586 8.5 9.052 8.5
                           8.351v-.702c0-.7.478-1.235
                            1.011-1.491A3.5 3.5
                             0 0 0 11.5 3V2h-7z"/>
        </svg>';
    /**
     *
     */
    const HTML_ICON_RISING_GRAPH =
        '<svg xmlns="http://www.w3.org/2000/svg"
 width="16"
  height="16"
   fill="currentColor"
    class="bi bi-graph-up"
     viewBox="0 0 16 16">
            <path fill-rule="evenodd"
             d="M0 0h1v15h15v1H0V0Zm14.817 3.113a.5.5
              0 0 1 .07.704l-4.5
               5.5a.5.5 0 0
                1-.74.037L7.06
                 6.767l-3.656
                  5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0
                   1 .758-.06l2.609
                    2.61 4.15-5.073a.5.5
                     0 0 1 .704-.07Z"/>
        </svg>';
    /**
     *
     */
    const HTML_ICON_CHART_STEPS =
        '<svg xmlns="http://www.w3.org/2000/svg" width="16"
 height="16"
  fill="currentColor"
   class="bi bi-bar-chart-steps"
    viewBox="0 0 16 16">
            <path d="M.5 0a.5.5 0 0 1
             .5.5v15a.5.5 0 0
              1-1 0V.5A.5.5 0 0
               1 .5 0zM2 1.5a.5.5 0
                0 1 .5-.5h4a.5.5 0
                 0 1 .5.5v1a.5.5 0
                  0 1-.5.5h-4a.5.5 0
                   0 1-.5-.5v-1zm2 4a.5.5 0
                    0 1 .5-.5h7a.5.5 0 0
                     1 .5.5v1a.5.5 0 0 1-.5.5h-7a.5.5 0
                      0 1-.5-.5v-1zm2 4a.5.5 0
                       0 1 .5-.5h6a.5.5 0
                        0 1 .5.5v1a.5.5 0
                         0 1-.5.5h-6a.5.5 0 0 1-.5-.5v-1zm2
                          4a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1
                           .5.5v1a.5.5 0 0 1-.5.5h-7a.5.5 0
                            0 1-.5-.5v-1z"/>
        </svg>';
    /**
     *
     */
    const HTML_ICON_SWAP =
        '<svg xmlns="http://www.w3.org/2000/svg"
 width="16"
  height="16"
   fill="currentColor"
    class="bi bi-shuffle"
     viewBox="0 0 16 16">
            <path fill-rule="evenodd"
             d="M0 3.5A.5.5 0 0
              1 .5 3H1c2.202 0 3.827
               1.24 4.874 2.418.49.552.865
                1.102 1.126
                 1.532.26-.43.636-.98
                  1.126-1.532C9.173 4.24
                   10.798 3 13
                    3v1c-1.798 0-3.173
                     1.01-4.126 2.082A9.624 9.624
                      0 0 0 7.556 8a9.624
                       9.624 0 0 0
                        1.317 1.918C9.828
                         10.99 11.204 12 13 12v1c-2.202 0-3.827-1.24-4.874-2.418A10.595
                          10.595 0 0 1 7 9.05c-.26.43-.636.98-1.126 1.532C4.827 11.76
                           3.202 13 1 13H.5a.5.5 0 0
                            1 0-1H1c1.798 0 3.173-1.01
                             4.126-2.082A9.624 9.624
                              0 0 0 6.444 8a9.624
                               9.624 0 0
                                0-1.317-1.918C4.172 5.01 2.796 4
                                 1 4H.5a.5.5 0 0 1-.5-.5z"/>
            <path d="M13 5.466V1.534a.25.25
             0 0 1 .41-.192l2.36 1.966c.12.1.12.284
              0 .384l-2.36 1.966a.25.25 0 0 1-.41-.192zm0
               9v-3.932a.25.25 0 0 1 .41-.192l2.36
                1.966c.12.1.12.284 0 .384l-2.36
                 1.966a.25.25 0 0 1-.41-.192z"/>
        </svg>';
    /**
     *
     */
    const HTML_ICON_ENVELOPE =
        '<svg class="bi bi-envelope-fill"
 width="16"
  height="16"
   viewBox="0 0 16 16"
    fill="currentColor"
     xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" d="M.05 3.555A2 2 0
             0 1 2 2h12a2 2 0 0 1
              1.95 1.555L8 8.414.05
               3.555zM0 4.697v7.104l5.803-3.558L0
                4.697zM6.761 8.83l-6.57 4.027A2 2 0
                 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8
                  9.586l-1.239-.757zm3.436-.586L16
                   11.801V4.697l-5.803 3.546z"/>
        </svg>';
    /**
     *
     */
    const HTML_ICON_ACTIVITY =
        '<svg xmlns="http://www.w3.org/2000/svg" width="16"
 height="16"
  fill="currentColor"
   class="bi bi-activity"
    viewBox="0 0 16 16">
            <path fill-rule="evenodd"
             d="M6 2a.5.5 0 0 1 .47.33L10
              12.036l1.53-4.208A.5.5 0
               0 1 12 7.5h3.5a.5.5 0 0 1
                0 1h-3.15l-1.88 5.17a.5.5 0
                 0 1-.94 0L6 3.964 4.47
                  8.171A.5.5 0 0
                   1 4 8.5H.5a.5.5 0 0
                    1 0-1h3.15l1.88-5.17A.5.5
                     0 0 1 6 2Z"/>
        </svg>';
    /**
     *
     */
    const HTML_ICON_TASKLIST =
        '<svg xmlns="http://www.w3.org/2000/svg"
 width="16"
  height="16"
   fill="currentColor"
    class="bi bi-list-task"
     viewBox="0 0 16 16">
            <path fill-rule="evenodd"
             d="M2 2.5a.5.5 0 0
              0-.5.5v1a.5.5 0 0
               0 .5.5h1a.5.5 0 0 0 .5-.5V3a.5.5 0
                0 0-.5-.5H2zM3 3H2v1h1V3z"/>
            <path d="M5 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0
             1h-9a.5.5 0 0 1-.5-.5zM5.5 7a.5.5
              0 0 0 0 1h9a.5.5 0
               0 0 0-1h-9zm0 4a.5.5
                0 0 0 0 1h9a.5.5 0
                 0 0 0-1h-9z"/>
            <path fill-rule="evenodd"
             d="M1.5 7a.5.5 0 0 1
              .5-.5h1a.5.5 0 0 1
               .5.5v1a.5.5 0 0
                1-.5.5H2a.5.5 0
                 0 1-.5-.5V7zM2 7h1v1H2V7zm0
                  3.5a.5.5 0 0 0-.5.5v1a.5.5 0 0
                   0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5H2zm1 .5H2v1h1v-1z"/>
        </svg>';
    /**
     *
     */
    const HTML_ICON_EYE =
        '<svg xmlns="http://www.w3.org/2000/svg"
 width="16"
  height="16"
   fill="currentColor"
    class="bi bi-eye-fill"
     viewBox="0 0 16 16">
            <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
            <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8
             0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
        </svg>';
    /**
     *
     */
    const HTML_ICON_VERTICAL_DOTS =
        '<svg xmlns="http://www.w3.org/2000/svg"
 width="16"
  height="16"
   fill="currentColor"
    class="bi bi-three-dots-vertical"
     viewBox="0 0 16 16">
            <path d="M9.5 13a1.5 1.5 0 1 1-3
             0 1.5 1.5 0 0 1 3
              0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0
               0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
        </svg>';

    /**
     *
     */
    const HTML_ICON_EXCLAMATION_TRIANGE =
        '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
        <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.1.1 0 0 1-.066.017H1.146a.1.1 0 0 1-.066-.017.2.2 0 0 1-.054-.06.18.18 0 0 1 .002-.183L7.884 2.073a.15.15 0 0 1 .054-.057m1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767z"/>
        <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
        </svg>';
        
    /**
     *
     */
    public function __construct() {
    }

    /**
     * This function
     * @param string $activetab
     * @return void
     */
    public function set_active_tab($activetab) {
        $this->activetabviewbacs                = ($activetab == 'view');
        $this->activetabtasksbacs               = ($activetab == 'tasks');
        $this->activetabstatusbacs              = ($activetab == 'status');
        $this->activetabresultsbacs             = ($activetab == 'results');
        $this->activetabanothersresultsbacs     = ($activetab == 'anothers_results');
        $this->activetabactionsbacs             = ($activetab == 'actions');
        $this->activetabuserdynamicsbacs        = ($activetab == 'user_dynamics');
        $this->activetabtaskdynamicsbacs        = ($activetab == 'task_dynamics');
        $this->activetabresultsgraphbacs        = ($activetab == 'results_graph');
        $this->activetabvirtualcontestbacs      = ($activetab == 'virtual_contest');
        $this->activetabvirtualparticipantsbacs = ($activetab == 'virtual_participants');
        $this->activetabincidentsbacs           = ($activetab == 'incidents');
        $this->activetabstatementsbacs          = ($activetab == 'statements');
    }

    /**
     * This function
     * @param renderer_base $output
     * @return stdClass
     * @throws dml_exception
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();

        $data->icon_standings            = self::HTML_ICON_FLAG;
        $data->icon_tasklist             = self::HTML_ICON_TASKLIST;
        $data->icon_status               = self::HTML_ICON_ACTIVITY;
        $data->icon_results              = self::HTML_ICON_ENVELOPE;
        $data->icon_anothers_results     = self::HTML_ICON_EYE;
        $data->icon_actions              = self::HTML_ICON_ARROW_RIGHT;
        $data->icon_user_dynamics        = self::HTML_ICON_SWAP;
        $data->icon_task_dynamics        = self::HTML_ICON_CHART_STEPS;
        $data->icon_results_graph        = self::HTML_ICON_RISING_GRAPH;
        $data->icon_virtual_contest      = self::HTML_ICON_HOURGLASS;
        $data->icon_virtual_participants = self::HTML_ICON_HOURGLASS;
        $data->icon_incidents            = self::HTML_ICON_EXCLAMATION_TRIANGE;
        $data->icon_settings             = self::HTML_ICON_GEAR;
        $data->icon_more                 = self::HTML_ICON_VERTICAL_DOTS;
        $data->icon_statements           = '<i class="bi bi-book"></i>';

        // ...other params.
        $data->user_capability_viewany = $this->usercapabilitiesbacs->viewany;
        $data->user_capability_edit    = $this->usercapabilitiesbacs->edit;

        $data->contest_name    = $this->contestname;
        $data->coursemodule_id = $this->coursemoduleidbacs;

        $data->minutes_from_start = $this->minutesfromstartbacs;
        $data->minutes_total      = $this->minutestotalbacs;
        $data->contest_status     = $this->conteststatusbacs;

        $data->isolate_participants = $this->isolateparticipantsbacs;
        $data->isolated_participant_mode_is_forced = $this->isolatedparticipantmodeisforcedbacs;
        $data->is_virtual_participation_disabled = $this->isvirtualparticipationdisabledbacs;

        $data->target_user_id        = $this->targetuseridbacs;
        $data->target_user_lastname  = $this->targetuserlastnamebacs;
        $data->target_user_firstname = $this->targetuserfirstnamebacs;

        $data->ace_editor_shown        = $this->aceeditorshownbacs;
        $data->ace_editor_theme        = $this->aceeditorthemebacs;
        $data->ace_editor_redirect_url = $this->aceeditorredirecturlbacs;

        $data->ace_editor_theme_present = ($this->aceeditorthemebacs != '');

        // ...prepare menu.
        $data->menu_shown = $this->menushownbacs;

        // ...local shortcut variables.
        $sisolatedf    = $this->isolatedparticipantmodeisforcedbacs;
        $snoisolatedf = !$this->isolatedparticipantmodeisforcedbacs;
        $scanviewany   = $this->usercapabilitiesbacs->viewany;
        $scanedit      = $this->usercapabilitiesbacs->edit;
        $snovirtual    = $this->isvirtualparticipationdisabledbacs;
        $sanyvirtual   = !$this->isvirtualparticipationdisabledbacs;

        $data->show_tab_view                 = $snoisolatedf;
        $data->show_tab_tasks                = true;
        $data->show_tab_status               = $snoisolatedf;
        $data->show_tab_results              = true;
        $data->show_tab_anothers_results     = $scanviewany && $this->activetabanothersresultsbacs;
        $data->show_tab_actions              = $scanedit && $this->activetabactionsbacs;
        $data->show_tab_user_dynamics        = $this->activetabuserdynamicsbacs;
        $data->show_tab_task_dynamics        = $this->activetabtaskdynamicsbacs;
        $data->show_tab_results_graph        = $this->activetabresultsgraphbacs;
        $data->show_tab_virtual_contest      = ($sisolatedf && $sanyvirtual) || $this->activetabvirtualcontestbacs;
        $data->show_tab_virtual_participants = $this->activetabvirtualparticipantsbacs;
        $data->show_tab_incidents            = $this->activetabincidentsbacs;
        $data->show_tab_more                 = $snoisolatedf;
        $data->show_tab_statements           = true;

        $data->show_more_item_view                 = true;
        $data->show_more_item_tasks                = true;
        $data->show_more_item_status               = true;
        $data->show_more_item_results              = true;
        $data->show_more_item_user_dynamics        = true;
        $data->show_more_item_task_dynamics        = true;
        $data->show_more_item_results_graph        = true;
        $data->show_more_item_virtual_contest      = $sanyvirtual;
        $data->show_more_item_virtual_participants = $scanedit && $sanyvirtual;
        $data->show_more_item_incidents            = $scanviewany;
        $data->show_more_item_actions              = $scanedit;
        $data->show_more_item_settings             = $scanedit;

        $data->active_tab_view                 = $this->activetabviewbacs;
        $data->active_tab_tasks                = $this->activetabtasksbacs;
        $data->active_tab_status               = $this->activetabstatusbacs;
        $data->active_tab_results              = $this->activetabresultsbacs;
        $data->active_tab_anothers_results     = $this->activetabanothersresultsbacs;
        $data->active_tab_actions              = $this->activetabactionsbacs;
        $data->active_tab_user_dynamics        = $this->activetabuserdynamicsbacs;
        $data->active_tab_task_dynamics        = $this->activetabtaskdynamicsbacs;
        $data->active_tab_results_graph        = $this->activetabresultsgraphbacs;
        $data->active_tab_virtual_contest      = $this->activetabvirtualcontestbacs;
        $data->active_tab_virtual_participants = $this->activetabvirtualparticipantsbacs;
        $data->active_tab_incidents            = $this->activetabincidentsbacs;
        $data->active_tab_statements           = $this->activetabstatementsbacs;

        return $data;
    }
}
