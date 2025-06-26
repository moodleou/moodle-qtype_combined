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
 * @package    qtype_combined
 * @copyright  2013 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/combined/question.php');
require_once($CFG->dirroot . '/question/type/combined/combiner/forquestiontype.php');
require_once($CFG->dirroot . '/question/type/combined/combiner/runtime.php');

/**
 * The combined question type.
 *
 * @copyright  2013 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_combined extends question_type {

    #[\Override]
    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_combined_feedback($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);

        $combiner = new qtype_combined_combiner_for_question_type();
        $combiner->move_subq_files($questionid, $oldcontextid, $newcontextid);
    }

    #[\Override]
    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_combined_feedback($questionid, $contextid);
        $this->delete_files_in_hints($questionid, $contextid);
    }

    #[\Override]
    public function save_question_options($fromform) {
        global $DB;
        $combiner = new qtype_combined_combiner_for_question_type();

        if (!$options = $DB->get_record('qtype_combined', ['questionid' => $fromform->id])) {
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
                $subquestion->idnumber = null;
                $this->save_imported_subquestion($subquestion);
            }

        } else {
            $combiner->save_subqs($fromform, $fromform->context->id);
        }

        $this->save_hints($fromform, true);
    }

    /**
     * This is a copy-paste of a bit in the middle of qformat_default::importprocess with changes to fit this situation.
     *
     * When I came to ugprade this code to Moodle 4.0, I found this comment which is not true:
     *      "This function will be removed in Moodle 2.6 when core Moodle is refactored so that
     *       save_question is used to save imported questions."
     * Clearly that was never done.
     *
     * @param stdClass $fromimport Data from question import.
     * @return bool|null            null if everything went OK, true if there is an error or false if a notice.
     */
    protected function save_imported_subquestion($fromimport) {
        global $USER, $DB, $OUTPUT;

        $fromimport->stamp = make_unique_id_code();  // Set the unique code (not to be changed).

        $fromimport->createdby = $USER->id;
        $fromimport->timecreated = time();
        $fromimport->modifiedby = $USER->id;
        $fromimport->timemodified = time();

        $fileoptions = [
            'subdirs' => true,
            'maxfiles' => -1,
            'maxbytes' => 0,
        ];

        $fromimport->id = $DB->insert_record('question', $fromimport);

        if ($DB->get_manager()->table_exists('question_bank_entries')) {
            // Moodle 4.x.

            // Create a bank entry for each question imported.
            $questionbankentry = new \stdClass();
            $questionbankentry->questioncategoryid = $fromimport->category;
            $questionbankentry->idnumber = null;
            $questionbankentry->ownerid = $fromimport->createdby;
            $questionbankentry->id = $DB->insert_record('question_bank_entries', $questionbankentry);

            // Create a version for each question imported.
            $questionversion = new \stdClass();
            $questionversion->questionbankentryid = $questionbankentry->id;
            $questionversion->questionid = $fromimport->id;
            $questionversion->version = 1;
            $questionversion->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;
            $questionversion->id = $DB->insert_record('question_versions', $questionversion);
        }

        if (isset($fromimport->questiontextitemid)) {
            $fromimport->questiontext = file_save_draft_area_files($fromimport->questiontextitemid,
                    $fromimport->context->id, 'question', 'questiontext', $fromimport->id,
                    $fileoptions, $fromimport->questiontext);
        } else if (isset($fromimport->questiontextfiles)) {
            foreach ($fromimport->questiontextfiles as $file) {
                question_bank::get_qtype($fromimport->qtype)->import_file(
                        $fromimport->context, 'question', 'questiontext', $fromimport->id, $file);
            }
        }
        if (isset($fromimport->generalfeedbackitemid)) {
            $fromimport->generalfeedback = file_save_draft_area_files($fromimport->generalfeedbackitemid,
                    $fromimport->context->id, 'question', 'generalfeedback', $fromimport->id,
                    $fileoptions, $fromimport->generalfeedback);
        } else if (isset($fromimport->generalfeedbackfiles)) {
            foreach ($fromimport->generalfeedbackfiles as $file) {
                question_bank::get_qtype($fromimport->qtype)->import_file(
                        $fromimport->context, 'question', 'generalfeedback', $fromimport->id, $file);
            }
        }
        $DB->update_record('question', $fromimport);

        // Now to save all the answers and type-specific options.
        $result = question_bank::get_qtype($fromimport->qtype)->save_question_options($fromimport);

        if (!empty($result->error)) {
            echo $OUTPUT->notification($result->error);
            // Can't use $transaction->rollback(); since it requires an exception,
            // and I don't want to rewrite this code to change the error handling now.
            $DB->force_transaction_rollback();
            return false;
        }

        if (!empty($result->notice)) {
            echo $OUTPUT->notification($result->notice);
            return true;
        }

        return null;
    }

    #[\Override]
    public function make_question($questiondata) {
        $question = parent::make_question($questiondata);
        $question->combiner = new qtype_combined_combiner_for_run_time_question_instance();

        // Need to process question text to get third param if any.
        $question->combiner->find_included_subqs_in_question_text($questiondata->questiontext);

        $question->combiner->create_subqs_from_subq_data($questiondata->subquestions);
        $question->combiner->make_subqs();
        return $question;
    }

    #[\Override]
    protected function make_hint($hint) {
        return question_hint_with_parts::load_from_record($hint);
    }

    #[\Override]
    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $this->initialise_combined_feedback($question, $questiondata, true);
    }

    #[\Override]
    public function get_question_options($question) {
        global $DB;
        if (false === parent::get_question_options($question)) {
            return false;
        }
        $question->options = $DB->get_record('qtype_combined', ['questionid' => $question->id], '*', MUST_EXIST);
        $question->subquestions = qtype_combined_combiner_base::get_subq_data_from_db($question->id, true);
        return true;
    }

    #[\Override]
    public function get_random_guess_score($questiondata) {
        $overallrandomguessscore = 0;
        foreach ($questiondata->subquestions as $subqdata) {
            $subqrandomguessscore = question_bank::get_qtype($subqdata->qtype)->get_random_guess_score($subqdata);
            $overallrandomguessscore += $subqdata->defaultmark * $subqrandomguessscore;
        }
        return $overallrandomguessscore;
    }

    #[\Override]
    public function get_possible_responses($questiondata) {
        $allpossibleresponses = [];
        foreach ($questiondata->subquestions as $subqdata) {
            $possresponses = question_bank::get_qtype($subqdata->qtype)->get_possible_responses($subqdata);
            foreach ($possresponses as $subqid => $subqpossresponses) {
                $respclassid = qtype_combined_type_manager::response_id($subqdata->name, $subqdata->qtype, $subqid);
                foreach ($subqpossresponses as $subqpossresponse) {
                    $subqpossresponse->fraction = $subqpossresponse->fraction * $subqdata->defaultmark;
                }
                $allpossibleresponses[$respclassid] = $subqpossresponses;
            }
        }
        ksort($allpossibleresponses);
        return $allpossibleresponses;
    }

    #[\Override]
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

    #[\Override]
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
