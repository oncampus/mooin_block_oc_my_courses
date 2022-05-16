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

class block_oc_my_courses extends block_base {

    public function init() {
        $this->page;
        $this->title = '<a name="kurse_bei_mooin"></a>' . get_string('pluginname', 'block_oc_my_courses');
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function has_config() {
        return false;
    }

    public function instance_allow_config() {
        return true;
    }

    public function applicable_formats() {
        return array(
                'all' => true
        );
    }

    public function specialization() {
        $this->page;
        $ocpage = $this->page->url->out(false);
        if (strpos($ocpage, '/my/') === false) {
            $this->title = '<a name="kurse_bei_mooin"></a>' . get_string('mooin_courses', 'block_oc_my_courses');
        }
    }

    public function get_content() {
        global $USER, $CFG, $DB;
        $this->page;
        ini_set('memory_limit', '256M');
        if ($this->content !== null) {
            return $this->content;
        }

        $precontent = '';
        $content = '';

        $ocpage = $this->page->url->out(false);
        if (strpos($ocpage, '/my/') !== false) {
            // $courses = enrol_get_my_courses();
            // $courses = enrol_get_my_courses(NULL, 'idnumber DESC');
            $courses = enrol_get_my_courses(null, 'sortorder ASC');
            if (count($courses) == 0) {
                redirect($CFG->wwwroot . '?redirect=0');
            }
        } else {
            // $courses = $DB->get_records_sql('SELECT * FROM {course} c WHERE c.id > 0 AND c.visible = 1 AND c.category = 2 ORDER BY c.idnumber DESC');

            // TODO wichtig für das umstellen auf moodalis!!!!!!!!!!!: Alle categories außer 3 (Administration) Achtung: auch an Kategorie Test denken
            // ALT - $courses = $DB->get_records_sql('SELECT *, 1 notondashboard FROM {course} c WHERE c.id > 0 AND c.visible = 1 AND c.category = 2 ORDER BY c.sortorder ASC');

            $courses =
                    $DB->get_records_sql('SELECT *, 1 notondashboard FROM {course} c WHERE c.id > 0 AND c.visible = 1 AND c.category != 3 ORDER BY c.sortorder ASC');

        }
        $coursecount = 0;
        foreach ($courses as $course) {
            $record = $DB->get_record('course', array('id' => $course->id));
            if (false) {
                $content .= $record->summary;
                if ($record->summary != '') {
                    $coursecount++;
                }
                /* Beispiel für den Inhalt der Kursbeschreibung ////////////////////////////////////////////////////////
                '<a href="kurs_videomooc.html">
                    <div class="mooc2 course_box">
                        <div class="upper">'.$course->fullname.'</div>
                        <div class="info">
                            <div class="inner_info">
                                <div class="inf1 inf_line">30.03.2015</div>
                                <div class="inf2 inf_line">Markus Valley</div>
                                <div class="inf3 inf_line">deutsch</div>
                                <div class="inf4 inf_line">6 Wochen</div>
                                <div class="inf5 inf_line">kostenlos</div>
                                <div class="inf6 inf_line">2000+</div>
                                <div class="infstar inf_line">4,5 von 5</div>
                            </div>
                        </div>
                    </div>
                 </a>';
                */
            } else { // alles was hier passiert sehen nur die Testuser ///////////////////////////////////////////////////////////////////////
                // *
                require_once($CFG->dirroot . '/local/ildcourseinfo/locallib.php');
                $moochubcourse = get_moochub_course($course->idnumber);
                // Der Kurs muss im json-file existieren und in moodalis auf sichtbar eingestellt sein (außer wir befinden uns auf dem dashboard)
                if ($moochubcourse !== false and ($moochubcourse->attributes->visible or !isset($course->notondashboard))) {
                    $style = '';
                    if (strlen(format_string($moochubcourse->attributes->name)) > 26) {
                        $style = 'style="line-height:21px;"';
                    }

                    $duration = '';
                    $access = '';
                    if (isset($moochubcourse->attributes->accessDuration) and
                            ($moochubcourse->attributes->accessDuration != 'P')) {
                        $interval = new DateInterval($moochubcourse->attributes->accessDuration);
                        $access = ' ' . get_string('access', 'local_ildcourseinfo');
                    } else if (isset($moochubcourse->attributes->duration) and ($moochubcourse->attributes->duration != 'P')) {
                        $interval = new DateInterval($moochubcourse->attributes->duration);
                    }
                    if (isset($interval)) {
                        // $duration = $interval->format('%d'); // für Wochen habe ich nichts gefunden :(
                        // $duration = $duration / 7; // also wird von Tagen in Wochen umgerechnet

                        // * Fehler: DateInterval::__construct() expects parameter 1 to be string, object given
                        // $timeInterval      = new DateInterval($interval);
                        $intervalinseconds = new DateTime();
                        $intervalinseconds->setTimeStamp(5097600);
                        $intervalinseconds->add($interval);
                        $intervalinseconds = $intervalinseconds->getTimeStamp();
                        $intervalinseconds = $intervalinseconds - 5097600;
                        $duration = round($intervalinseconds / (60 * 60 * 24 * 7));
                        // *

                        if ($duration == 1) {
                            $duration = $duration . ' ' . get_string('week') . $access;
                        } else {
                            $duration = $duration . ' ' . get_string('weeks') . $access;
                        }
                    }

                    // wenn kein enddate vorhanden ist dann wird mit duration gerechnet
                    $enddate = 0;
                    if (isset($moochubcourse->attributes->enddate)) {
                        $enddate = strtotime($moochubcourse->attributes->enddate);
                    } else if ($duration != '' and isset($moochubcourse->attributes->startDate)) {
                        $enddate = strtotime($moochubcourse->attributes->startDate) + $interval->format('%s');
                    }

                    $startdate = get_string('no_trainer', 'local_ildcourseinfo');
                    if ((isset($moochubcourse->attributes->startDate) and $enddate > time())
                            or
                            isset($moochubcourse->attributes->startDate) and
                            strtotime($moochubcourse->attributes->startDate) < time() and $enddate == 0) {
                        $startdate = date('d.m.Y', strtotime($moochubcourse->attributes->startDate));
                    }

                    // TODO aus dem Sprachpaket holen
                    $lang = array('de' => 'Deutsch', 'en' => 'English', 'fr' => 'Français', 'ar' => 'Arabic');
                    $languages = '';
                    foreach ($moochubcourse->attributes->languages as $language) {
                        if ($languages != '') {
                            $languages .= ', ';
                        }
                        $languages .= $lang[$language];
                    }

                    if ($moochubcourse->attributes->isAccessibleForFree == 'true') {
                        $cash = get_string('for_free', 'local_ildcourseinfo');
                    } else {
                        $cash = '';
                        foreach ($moochubcourse->attributes->offers as $offer) {
                            if ($offer->priceCurrency == 'EUR') {
                                $currency = '€';
                            } else {
                                $currency = $offer->priceCurrency;
                            }
                            $price = $offer->price;
                            if (strpos($price, '.') == false) {
                                $displayprice = number_format($price, 0, "", "");
                            } else {
                                $displayprice = number_format($price, 2, ",", "");
                            }

                            $cash = $displayprice . ' ' . $currency;
                            break;
                        }
                        /*
                        foreach ($moochubcourse->attributes->offers as $offer) {
                            $cash = $offer->price;
                            break;
                        }
                        */
                    }

                    $publishers = '';
                    foreach ($moochubcourse->attributes->publisher as $p) {
                        if ($publishers != '') {
                            $publishers .= '<br />';
                        }
                        $publishers .= $p->name;
                    }

                    $participantsdiv = '';
                    $context = context_course::instance($course->id);
                    $participants = get_enrolled_users($context);
                    $cp = count($participants);
                    if ($cp > 0) {
                        $participantsdiv = '<div class="inf6 inf_line">' . $cp . '</div>';
                    }

                    // ermitteln ob user bereits eingeschrieben ist und entsprechend kurs oder infoseite verlinken
                    $courseurl = $CFG->wwwroot . '/local/ildcourseinfo/index.php?id=' . $moochubcourse->attributes->courseCode;
                    if (isset($participants[$USER->id]) and $participants[$USER->id]->username == $USER->username) {
                        $courseurl = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
                    }
                    /* ob_start();
                    print_object($moochubcourse->attributes);
                    $test = ob_get_contents();
                    ob_end_clean();
                    $content .= '* '.$test; */
                    // echo $course->fullname.' ';
                    $bgcolorclass = '';
                    $category = 2;
                    if (isset($course->category)) {
                        $category = $course->category;
                    }
                    if ($category == 4) {
                        $bgcolorclass = 'oc_info';
                    }
                    // $content .= '*'.$moochubcourse->attributes->name.' '.strlen($moochubcourse->attributes->name).'*';
                    $content .= '<a href="' . $courseurl . '">
								<div class="course_box" style="background-image: url(' . $moochubcourse->attributes->image . ');">
									<div class="upper" ' . $style . '>' . format_string($moochubcourse->attributes->name) . '</div>
									<div class="info ' . $bgcolorclass . '">
										<div class="inner_info">
											<div class="inf1 inf_line">' . $startdate . '</div>
											<div class="inf2 inf_line">' . $publishers . '</div>
											<div class="inf3 inf_line">' . $languages . '</div>
											<div class="inf4 inf_line">' . $duration . '</div>
											<div class="inf5 inf_line">' . $cash . '</div>';
                    $content .= $participantsdiv;
                    if (isset($moochubcourse->attributes->starRating)) {
                        $content .= '<div class="infstar inf_line">' . $moochubcourse->attributes->starRating . ' ' .
                                get_string('of_5', 'block_oc_my_courses') . '</div>';
                    }
                    $content .= '</div>
									</div>
								</div>
							 </a>';
                    $coursecount++;
                }
                // *
            }
        }

        $coursecountclass = '';
        if ($coursecount == 1) {
            $coursecountclass = ' ein-kurs';
        } else if ($coursecount == 2) {
            $coursecountclass = ' zwei-kurse';
        } else if ($coursecount > 2) {
            $coursecountclass = ' viele-kurse';
        }

        $precontent .= '<section class="section_general" name="kurse_bei_mooin">' .
                '<div class="wrapper_general' . $coursecountclass . '">' .
                '<div class="course_grid' . $coursecountclass . '">';

        $content .= '<div style="clear:both;"></div><div style="clear:both;"></div></div></section>';

        $content .= '<script type="text/javascript" src="/blocks/oc_my_courses/jquery.js"></script>';
        $content .= '<script type="text/javascript" src="/blocks/oc_my_courses/mc.js"></script>';
        $content .= '<script type="text/javascript" src="/blocks/oc_my_courses/mooin.js"></script>';

        $this->content = new stdClass();
        $this->content->text = $precontent . $content;

        return $this->content;
    }
}
