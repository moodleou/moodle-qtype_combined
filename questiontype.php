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

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_combined_feedback($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);

        $combiner = new qtype_combined_combiner_for_question_type();
        $combiner->move_subq_files($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_combined_feedback($questionid, $contextid);
        $this->delete_files_in_hints($questionid, $contextid);
    }

    public function save_question_options($fromform) {
        global $DB;
        $combiner = new qtype_combined_combiner_for_question_type();

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
                $this->save_imported_question($subquestion);
            }

        } else {
            $combiner->save_subqs($fromform, $fromform->context->id);
        }

        $this->save_hints($fromform, true);
    }

    /**
     * This is a duplication of the functionality used to save an imported question. This function will be removed in Moodle 2.6
     * when core Moodle is refactored so that save_question is used to save imported questions.
     * @param $fromimport stdClass  Data from question import.
     * @return bool|null            null if everything went OK, true if there is an error or false if a notice.
     */
    protected function save_imported_question($fromimport) {
        global $USER, $DB, $CFG, $OUTPUT;
        $fromimport->stamp = make_unique_id_code(); // Set the unique code (not to be changed).

        $fromimport->createdby = $USER->id;
        $fromimport->timecreated = time();
        $fromimport->modifiedby = $USER->id;
        $fromimport->timemodified = time();
        $fileoptions = array(
            'subdirs'  => false,
            'maxfiles' => -1,
            'maxbytes' => 0,
        );

        $fromimport->id = $DB->insert_record('question', $fromimport);

        if (isset($fromimport->questiontextitemid)) {
            $fromimport->questiontext = file_save_draft_area_files($fromimport->questiontextitemid,
                                                                   $fromimport->context->id, 'question', 'questiontext',
                                                                   $fromimport->id,
                                                                   $fileoptions, $fromimport->questiontext);
        } else if (isset($fromimport->questiontextfiles)) {
            foreach ($fromimport->questiontextfiles as $file) {
                $this->import_file($fromimport->context, 'question', 'questiontext', $fromimport->id, $file);
            }
        }
        if (isset($fromimport->generalfeedbackitemid)) {
            $fromimport->generalfeedback = file_save_draft_area_files($fromimport->generalfeedbackitemid,
                                                                      $fromimport->context->id, 'question', 'generalfeedback',
                                                                      $fromimport->id,
                                                                      $fileoptions, $fromimport->generalfeedback);
        } else if (isset($fromimport->generalfeedbackfiles)) {
            foreach ($fromimport->generalfeedbackfiles as $file) {
                $this->import_file($fromimport->context, 'question', 'generalfeedback', $fromimport->id, $file);
            }
        }
        $DB->update_record('question', $fromimport);

        // Now to save all the answers and type-specific options.

        $result = question_bank::get_qtype($fromimport->qtype)->save_question_options($fromimport);

        if (!empty($result->error)) {
            echo $OUTPUT->notification($result->error);
            return false;
        }

        if (!empty($result->notice)) {
            echo $OUTPUT->notification($result->notice);
            return true;
        }

        if (!empty($CFG->usetags) && isset($fromimport->tags)) {
            require_once($CFG->dirroot.'/tag/lib.php');
            core_tag_tag::set_item_tags('core_question', 'question', $fromimport->id,
                    $fromimport->context, $fromimport->tags);
        }
        // Give the question a unique version stamp determined by question_hash().
        $DB->set_field('question', 'version', question_hash($fromimport),
                       array('id' => $fromimport->id));

        return null;

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
        $overallrandomguessscore = 0;
        foreach ($questiondata->subquestions as $subqdata) {
            $subqrandomguessscore = question_bank::get_qtype($subqdata->qtype)->get_random_guess_score($subqdata);
            $overallrandomguessscore += $subqdata->defaultmark * $subqrandomguessscore;
        }
        return $overallrandomguessscore;
    }

    public function get_possible_responses($questiondata) {
        $allpossibleresponses = array();
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
