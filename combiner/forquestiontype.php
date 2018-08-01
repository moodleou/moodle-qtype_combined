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
 * Code that deals with saving subqs data from form.
 *
 * @package    qtype_combined
 * @copyright  2013 The Open University
 * @author     James Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/combined/combiner/base.php');


/**
 * Class qtype_combined_combiner_for_saving_subqs
 */
class qtype_combined_combiner_for_question_type extends qtype_combined_combiner_base {

    /**
     * Save subq data. Default values are added to the values from the form and then the data is passed through to the
     * save_question method for that question_type.
     * @param $fromform stdClass Data from form
     * @param $contextid integer question context id
     */
    public function save_subqs($fromform, $contextid) {
        $this->find_included_subqs_in_question_text($fromform->questiontext);
        $this->load_subq_data_from_db($fromform->id);
        $this->get_subq_data_from_form_data($fromform);
        foreach ($this->subqs as $subq) {
            if (!$subq->is_in_question_text() && !$subq->preserve_submitted_data()) {
                if ($subq->is_in_db()) {
                    $subq->delete();
                }
            } else {
                $subq->save($contextid);
            }
        }
    }

    public function move_subq_files($questionid, $oldcontextid, $newcontextid) {
        $subqrecs = $this->get_subq_data_from_db($questionid);
        foreach ($subqrecs as $subq) {
            question_bank::get_qtype($subq->qtype, true)->move_files($subq->id, $oldcontextid, $newcontextid);
        }
    }


}
