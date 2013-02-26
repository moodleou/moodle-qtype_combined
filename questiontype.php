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
 * Question type class for the combined question type.
 *
 * @package    qtype
 * @subpackage combined
 * @copyright  2013 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/combined/question.php');
require_once($CFG->dirroot .'/question/type/combined/combiner.php');

/**
 * The combined question type.
 *
 * @copyright  2013 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_combined extends question_type {

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_hints($questionid, $contextid);
    }

    public function save_question_options($question) {
        $this->save_hints($question);
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        // TODO needs to pass through to sub-questions.
        parent::initialise_question_instance($question, $questiondata);
    }

    public function get_random_guess_score($questiondata) {
        // TODO needs to pass through to sub-questions.
        return 0;
    }

    public function get_possible_responses($questiondata) {
        // TODO needs to pass through to sub-questions.
        return array();
    }

    public function finished_edit_wizard($fromform) {
        //keep browser from moving onto next page after saving question and
        //recalculating variable values.
        if (!empty($fromform->updateform)) {
            return false;
        } else {
            return true;
        }
    }
}
