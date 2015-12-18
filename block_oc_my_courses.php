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
 * @package   block_oc_my_courses
 * @copyright 2015 oncampus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
		
        if ($this->content !== null) {
            return $this->content;
        }
		
		$precontent = '';
		$content = '';
		
		if ($USER->username == 'rieger') {
			//print_object($PAGE);die();
		}
		
		$oc_page = $PAGE->url->out(false);
		if (strpos($oc_page, '/my/') !== false) {
			$courses = enrol_get_my_courses();
			if (count($courses) == 0) {
				redirect($CFG->wwwroot.'?redirect=0');
			}
		}
		else {
			$courses = $DB->get_records_sql('SELECT * FROM {course} c WHERE c.id > 0 AND c.visible = 1 ORDER BY c.idnumber DESC');
		}
		$coursecount = 0;
		foreach ($courses as $course) {
			$record = $DB->get_record('course', array('id' => $course->id));
			$content .= $record->summary;
			if ($record->summary != '') {
				$coursecount++;
			}
			/* Beispiel für den Inhalt der Kursbeschreibung ////////////////////////////////////////////////////////
			'<a href="kurs_mooc.html">
			 	<div class="mooc2 course_box">
			 		<div class="upper">'.$course->fullname.'</div>
			 		<div class="info">
			 			<div class="inner_info">
			 				<div class="inf1 inf_line">30.03.2015</div>
			 				<div class="inf2 inf_line">Max Mustermann</div>
			 				<div class="inf3 inf_line">deutsch</div>
			 				<div class="inf4 inf_line">6 Wochen</div>
			 				<div class="inf5 inf_line">kostenlos</div>
			 			</div>
			 		</div>
			 	</div>
			 </a>'; 
			*/
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
