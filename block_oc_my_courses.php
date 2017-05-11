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
		global $PAGE;
        $this->title = '<a name="kurse_bei_mooin"></a>'.get_string('pluginname', 'block_oc_my_courses');
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
		global $PAGE;
		$oc_page = $PAGE->url->out(false);
		if (strpos($oc_page, '/my/') === false) {
			$this->title = '<a name="kurse_bei_mooin"></a>'.get_string('mooin_courses', 'block_oc_my_courses');
		}
    }

    public function get_content() {
        global $USER, $PAGE, $CFG, $DB;
		ini_set('memory_limit', '256M');
        if ($this->content !== null) {
            return $this->content;
        }
		
		$precontent = '';
		$content = '';
		
		$oc_page = $PAGE->url->out(false);
		if (strpos($oc_page, '/my/') !== false) {
			//$courses = enrol_get_my_courses();
			//$courses = enrol_get_my_courses(NULL, 'idnumber DESC');
			$courses = enrol_get_my_courses(NULL, 'sortorder ASC');
			if (count($courses) == 0) {
				redirect($CFG->wwwroot.'?redirect=0');
			}
		}
		else {
			//$courses = $DB->get_records_sql('SELECT * FROM {course} c WHERE c.id > 0 AND c.visible = 1 AND c.category = 2 ORDER BY c.idnumber DESC');

			// TODO wichtig für das umstellen auf moodalis!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!: Alle categories außer 3 (Administration) Achtung: auch an Kategorie Test denken
			// ALT - $courses = $DB->get_records_sql('SELECT *, 1 notondashboard FROM {course} c WHERE c.id > 0 AND c.visible = 1 AND c.category = 2 ORDER BY c.sortorder ASC');

            $courses = $DB->get_records_sql('SELECT *, 1 notondashboard FROM {course} c WHERE c.id > 0 AND c.visible = 1 AND c.category != 3 ORDER BY c.sortorder ASC');

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
			}
			else { // alles was hier passiert sehen nur die Testuser ///////////////////////////////////////////////////////////////////////
				//* 
				require_once($CFG->dirroot.'/local/ildcourseinfo/locallib.php');
				$moochub_course = get_moochub_course($course->idnumber);
				// Der Kurs muss im json-file existieren und in moodalis auf sichtbar eingestellt sein (außer wir befinden uns auf dem dashboard)
				if ($moochub_course !== false and ($moochub_course->attributes->visible or !isset($course->notondashboard))) {
					$style = '';
					if (strlen(format_string($moochub_course->attributes->name)) > 26) {
						$style = 'style="line-height:21px;"';
					}					
					
					$duration = '';
					$access = '';
					if (isset($moochub_course->attributes->accessDuration) and ($moochub_course->attributes->accessDuration != 'P')) {
						$interval = new DateInterval($moochub_course->attributes->accessDuration);
						$access = ' '.get_string('access', 'local_ildcourseinfo');
					}
					elseif (isset($moochub_course->attributes->duration) and ($moochub_course->attributes->duration != 'P')) {
						$interval = new DateInterval($moochub_course->attributes->duration);
					}
					if (isset($interval)) {
						//$duration = $interval->format('%d'); // für Wochen habe ich nichts gefunden :( 
						//$duration = $duration / 7; 			 // also wird von Tagen in Wochen umgerechnet
						
						//* Fehler: DateInterval::__construct() expects parameter 1 to be string, object given 
						//$timeInterval      = new DateInterval($interval);
						$intervalInSeconds = new DateTime();
						$intervalInSeconds->setTimeStamp(5097600);
						$intervalInSeconds->add($interval);
						$intervalInSeconds = $intervalInSeconds->getTimeStamp();
						$intervalInSeconds = $intervalInSeconds - 5097600;
						$duration = round($intervalInSeconds / (60 * 60 * 24 * 7));
						//*/
						
						if ($duration == 1) {
							$duration = $duration.' '.get_string('week').$access;
						}
						else {
							$duration = $duration.' '.get_string('weeks').$access;
						}
					}

					// wenn kein endDate vorhanden ist dann wird mit duration gerechnet
					$endDate = 0;
					if (isset($moochub_course->attributes->endDate)) {
						$endDate = strtotime($moochub_course->attributes->endDate);
					}
					elseif ($duration != '' and isset($moochub_course->attributes->startDate)) {
						$endDate = strtotime($moochub_course->attributes->startDate) + $interval->format('%s');
					}

					$startDate = get_string('no_trainer', 'local_ildcourseinfo');
					if ((isset($moochub_course->attributes->startDate) and $endDate > time()) 
							or 
						isset($moochub_course->attributes->startDate) and strtotime($moochub_course->attributes->startDate) < time() and $endDate == 0) {
							$startDate = date('d.m.Y', strtotime($moochub_course->attributes->startDate));
					}
					
					// TODO aus dem Sprachpaket holen
					$lang = array('de' => 'Deutsch', 'en' => 'English', 'fr' => 'Français', 'ar' => 'Arabic');
					$languages = '';
					foreach ($moochub_course->attributes->languages as $language) {
						if ($languages != '') {
							$languages .= ', ';
						}
						$languages .= $lang[$language];
					}
					
					if ($moochub_course->attributes->isAccessibleForFree == 'true') {
						$cash = get_string('for_free', 'local_ildcourseinfo');
					}
					else {
						$cash = '';
						foreach ($moochub_course->attributes->offers as $offer) {
							if ($offer->priceCurrency == 'EUR') {
								$currency = '€';
							} else {
								$currency = $offer->priceCurrency;
							}
							$price = $offer->price;
							if(strpos($price, '.') == false) {
								$display_price = number_format ( $price , 0 , "" , "" );
							} else {
								$display_price = number_format ( $price , 2 , "," , "" );
							}
							
							$cash = $display_price . ' ' . $currency;
							break;
						}
						/*
						foreach ($moochub_course->attributes->offers as $offer) {
							$cash = $offer->price;
							break;
						}
						*/
					}
					
					$publishers = '';
					foreach ($moochub_course->attributes->publisher as $p) {
						if ($publishers != '') {
							$publishers .= '<br />';
						}
						$publishers .= $p->name;
					}
					
					$participants_div = '';
					$context = context_course::instance($course->id);
					$participants = get_enrolled_users($context);
					$cp = count($participants);
					if ($cp > 0) {
						$participants_div = '<div class="inf6 inf_line">'.$cp.'</div>';
					}
										
					// ermitteln ob user bereits eingeschrieben ist und entsprechend kurs oder infoseite verlinken
					$course_url = $CFG->wwwroot.'/local/ildcourseinfo/index.php?id='.$moochub_course->attributes->courseCode;
					if (isset($participants[$USER->id]) and $participants[$USER->id]->username == $USER->username) {
						$course_url = $CFG->wwwroot.'/course/view.php?id='.$course->id;
					}
					/* ob_start();
					print_object($moochub_course->attributes);
					$test = ob_get_contents();
					ob_end_clean(); 
					$content .= '* '.$test; */
					//echo $course->fullname.' ';
					$bg_color_class = '';
					$category = 2;
					if (isset($course->category)) {
						$category = $course->category;
					}
					if ($category == 4) {
						$bg_color_class = 'oc_info';
					}
					//$content .= '*'.$moochub_course->attributes->name.' '.strlen($moochub_course->attributes->name).'*';
					$content .= '<a href="'.$course_url.'">
								<div class="course_box" style="background-image: url('.$moochub_course->attributes->image.');">
									<div class="upper" '.$style.'>'.format_string($moochub_course->attributes->name).'</div>
									<div class="info '.$bg_color_class.'">
										<div class="inner_info">
											<div class="inf1 inf_line">'.$startDate.'</div>
											<div class="inf2 inf_line">'.$publishers.'</div>
											<div class="inf3 inf_line">'.$languages.'</div>
											<div class="inf4 inf_line">'.$duration.'</div>
											<div class="inf5 inf_line">'.$cash.'</div>';
					$content .= 			$participants_div;
					if (isset($moochub_course->attributes->starRating)) {
						$content .= 		'<div class="infstar inf_line">'.$moochub_course->attributes->starRating.' '.get_string('of_5', 'block_oc_my_courses').'</div>';
					}
					$content .= 		'</div>
									</div>
								</div>
							 </a>';
					$coursecount++;
				} 
				//*/
			}
		}
		
		$coursecount_class = '';
		if ($coursecount == 1) {
			$coursecount_class = ' ein-kurs';
		}
		else if ($coursecount == 2) {
			$coursecount_class = ' zwei-kurse';
		}
		else if ($coursecount > 2) {
			$coursecount_class = ' viele-kurse';
		}
		
		$precontent .= '<section class="section_general" name="kurse_bei_mooin">'.
						'<div class="wrapper_general'.$coursecount_class.'">'.
							'<div class="course_grid'.$coursecount_class.'">';
		
		$content .= '<div style="clear:both;"></div><div style="clear:both;"></div></div></section>';

		$content .= '<script type="text/javascript" src="/blocks/oc_my_courses/jquery.js"></script>';
		$content .= '<script type="text/javascript" src="/blocks/oc_my_courses/mc.js"></script>';
		$content .= '<script type="text/javascript" src="/blocks/oc_my_courses/mooin.js"></script>';
		
        $this->content = new stdClass();
        $this->content->text = $precontent.$content;

        return $this->content;
    }
}
