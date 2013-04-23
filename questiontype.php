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
        $this->move_files_in_combined_feedback($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_combined_feedback($questionid, $contextid);
        $this->delete_files_in_hints($questionid, $contextid);
    }

    public function save_question_options($fromform) {
        global $DB;
        $combiner = new qtype_combined_combiner_for_saving_subqs();

        if (!$options = $DB->get_record('qtype_combined', array('questionid' => $fromform->id))) {
            $options = new stdClass();
            $options->questionid = $fromform->id;
            $options->correctfeedback = '';
            $options->partiallycorrectfeedback = '';
            $options->incorrectfeedback = '';
            $options->id = $DB->insert_record('qtype_combined', $options);
        }
        $options = $this->save_combined_feedback_helper($options, $fromform, $fromform->context, true);
        $DB->update_record('qtype_combined', $options);

        if (isset($fromform->subquestions)) {
            // Question import.
            foreach ($fromform->subquestions as $subquestion) {
                $subquestion->parent = $fromform->id;
                $subquestion->context = $fromform->context;
                $subquestion->category = $fromform->category;
                question_bank::get_qtype($subquestion->qtype)->save_imported_question($subquestion);
            }

        } else {
            $combiner->save_subqs($fromform, $fromform->context->id);
        }

        $this->save_hints($fromform, true);
    }


    public function make_question($questiondata) {
        $question = parent::make_question($questiondata);
        $question->combiner = new qtype_combined_combiner_for_run_time_question_instance();

        // Need to process question text to get third param if any.
        $question->combiner->find_included_subqs_in_question_text($questiondata->questiontext);

        $question->combiner->create_subqs_from_subq_data($questiondata->subquestions);
        $question->combiner->make_subqs();
        return $question;
    }

    protected function make_hint($hint) {
        return question_hint_with_parts::load_from_record($hint);
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $this->initialise_combined_feedback($question, $questiondata, true);
    }

    public function get_question_options($question) {
        global $DB;
        if (false === parent::get_question_options($question)) {
            return false;
        }
        $question->options = $DB->get_record('qtype_combined', array('questionid' => $question->id), '*', MUST_EXIST);
        $question->subquestions = qtype_combined_combiner_base::get_subq_data_from_db($question->id, true);
        return true;
    }

    public function get_random_guess_score($questiondata) {
        // TODO needs to pass through to sub-questions.
        return 0;
    }

    public function get_possible_responses($questiondata) {
        // TODO needs to pass through to sub-questions.
        return array();
    }

    public function export_to_xml($question, qformat_xml $format, $extra = null) {
        $output = '';
        $output .= $format->write_combined_feedback($question->options, $question->id, $question->contextid);
        $output .= "<subquestions>\n";
        foreach ($question->subquestions as $subquestion) {
            $output .= $format->writequestion($subquestion);
        }
        $output .= "</subquestions>\n";
        return $output;
    }

    public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
        if (!isset($data['@']['type']) || $data['@']['type'] != 'combined') {
            return false;
        }

        $question = $format->import_headers($data);
        $question->qtype = 'combined';

        $format->import_combined_feedback($question, $data, true);
        $format->import_hints($question, $data, true, false, $format->get_format($question->questiontextformat));

        $question->subquestions = $format->import_questions($data['#']['subquestions'][0]['#']['question']);

        return $question;
    }
}
