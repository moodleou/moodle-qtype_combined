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
 * Contains the helper class for the combined question type tests.
 *
 * @package   qtype_combined
 * @copyright 2013 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Test helper class for the combined question type.
 *
 * @copyright 2013 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_combined_test_helper {

    /**
     * @param string $qtype, .... variable number of params accepted, they are all strings, qtypes whose helpers to include
     * @return bool|string error - false or a message about qtypes that are not installed
     */
    public static function safe_include_test_helpers(/*... */) {
        global $CFG;
        $notfound = array();
        foreach (func_get_args() as $qtype) {
            if (!is_readable($CFG->dirroot.'/question/type/'.$qtype.'/tests/helper.php')) {
                $notfound[] = $qtype;
            }
        }
        if (count($notfound)) {
            return "Test skipped some required question types are not installed ".join(', ', $notfound).".";
        }
        foreach (func_get_args() as $qtype) {
            require_once($CFG->dirroot.'/question/type/'.$qtype.'/tests/helper.php');
        }
        return false;
    }

    /**
     * @param string $name
     * @return qtype_gapselect_question
     */
    protected static function make_a_gapselect_question($name) {
        $gapselect = qtype_gapselect_test_helper::make_a_gapselect_question();
        $gapselect->name = $name;
        $gapselect->questiontext = '[[1]][[2]][[3]]';
        $gapselect->generalfeedback = 'You made at least one incorrect choice.';
        $gapselect->shufflechoices = false;

        return $gapselect;
    }

    /**
     * @return qtype_combined_question
     */
    public static function make_a_combined_question_with_gapselect_subquestion() {
        question_bank::load_question_definition_classes('combined');
        $combined = new qtype_combined_question();

        test_question_maker::initialise_a_question($combined);

        $combined->name = 'Selection from drop down list question';
        $combined->questiontext = 'The [[gs:selectmenu:1]] brown [[gs:selectmenu:2]] jumped over the [[gs:selectmenu:3]] dog.';
        $combined->generalfeedback = 'This sentence uses each letter of the alphabet.';
        $combined->qtype = question_bank::get_qtype('combined');

        test_question_maker::set_standard_combined_feedback_fields($combined);

        $combined->combiner = new qtype_combined_combiner_for_run_time_question_instance();
        $combined->combiner->find_included_subqs_in_question_text($combined->questiontext);

        $subq = $combined->combiner->find_or_create_question_instance('selectmenu', 'gs');
        $subq->question = self::make_a_gapselect_question('gs');

        $combined->hints = array(
            new question_hint_with_parts(1, 'This is the first hint.', FORMAT_HTML, false, false),
            new question_hint_with_parts(2, 'This is the second hint.', FORMAT_HTML, true, true),
        );

        return $combined;
    }

    /**
     * @param string $name
     * @return qtype_oumultiresponse_question
     */
    protected static function make_oumultiresponse_question_two_of_four($name) {
        $mr = qtype_oumultiresponse_test_helper::make_oumultiresponse_question_two_of_four();
        $mr->name = $name;
        $mr->shuffleanswers = false;
        return $mr;
    }

    /**
     * @return qtype_combined_question
     */
    public static function make_a_combined_question_with_oumr_subquestion() {
        question_bank::load_question_definition_classes('combined');
        $combined = new qtype_combined_question();

        test_question_maker::initialise_a_question($combined);

        $combined->name = 'Selection from drop down list question';
        $combined->questiontext = 'Choose correct 2 check boxes [[mc:multiresponse]].';
        $combined->generalfeedback = 'You need to choose 2 of the 4 check boxes.';
        $combined->qtype = question_bank::get_qtype('combined');

        test_question_maker::set_standard_combined_feedback_fields($combined);

        $combined->combiner = new qtype_combined_combiner_for_run_time_question_instance();
        $combined->combiner->find_included_subqs_in_question_text($combined->questiontext);

        $subq = $combined->combiner->find_or_create_question_instance('multiresponse', 'mc');
        $subq->question = self::make_oumultiresponse_question_two_of_four('mc');

        $combined->hints = array(
            new question_hint_with_parts(1, 'Hint 1.', FORMAT_HTML, true, false),
            new question_hint_with_parts(2, 'Hint 2.', FORMAT_HTML, true, true)
        );

        return $combined;
    }

    /**
     * @return qtype_combined_question
     */
    public static function make_a_combined_question_with_oumr_and_gapselect_subquestion() {
        question_bank::load_question_definition_classes('combined');
        $combined = new qtype_combined_question();

        test_question_maker::initialise_a_question($combined);

        $combined->name = 'Selection from drop down list question';
        $combined->questiontext = 'Choose correct 2 check boxes [[mc:multiresponse]]. '.
            'The [[gs:selectmenu:1]] brown [[gs:selectmenu:2]] jumped over the [[gs:selectmenu:3]] dog.';
        $combined->generalfeedback = 'You need to choose 2 of the 4 check boxes. Then the next sentence contains every letter of'.
                                        'the alphabet.';
        $combined->qtype = question_bank::get_qtype('combined');

        test_question_maker::set_standard_combined_feedback_fields($combined);

        $combined->combiner = new qtype_combined_combiner_for_run_time_question_instance();
        $combined->combiner->find_included_subqs_in_question_text($combined->questiontext);

        $subq = $combined->combiner->find_or_create_question_instance('multiresponse', 'mc');
        $subq->question = self::make_oumultiresponse_question_two_of_four('mc');
        $subq->question->defaultmark = 0.5;

        $subq = $combined->combiner->find_or_create_question_instance('selectmenu', 'gs');
        $subq->question = self::make_a_gapselect_question('gs');
        $subq->question->defaultmark = 0.5;

        $combined->hints = array(
            new question_hint_with_parts(1, 'Hint 1.', FORMAT_HTML, true, false),
            new question_hint_with_parts(2, 'Hint 2.', FORMAT_HTML, true, true),
            new question_hint_with_parts(3, 'Hint 3.', FORMAT_HTML, true, true)
        );

        return $combined;
    }

    /**
     * @param string $name
     * @return qtype_pmatch_question
     */
    protected static function make_a_pmatch_question($name) {
        $pm = qtype_pmatch_test_helper::make_a_pmatch_question(false);
        $pm->name = $name;
        unset($pm->answers[14]);
        unset($pm->answers[15]);
        return $pm;
    }

    /**
     * @return qtype_combined_question
     */
    public static function make_a_combined_question_with_oumr_pmatch_and_gapselect_subquestion() {
        question_bank::load_question_definition_classes('combined');
        $combined = new qtype_combined_question();

        test_question_maker::initialise_a_question($combined);

        $combined->name = 'Selection from drop down list question';
        $combined->questiontext = 'Choose correct 2 check boxes [[mc:multiresponse]]. '.
            'The [[gs:selectmenu:1]] brown [[gs:selectmenu:2]] jumped over the [[gs:selectmenu:3]] dog. [[pm:pmatch]].';
        $combined->generalfeedback = 'You need to choose 2 of the 4 check boxes. Then the next sentence contains every letter of'.
            'the alphabet.';
        $combined->qtype = question_bank::get_qtype('combined');

        test_question_maker::set_standard_combined_feedback_fields($combined);

        $combined->combiner = new qtype_combined_combiner_for_run_time_question_instance();
        $combined->combiner->find_included_subqs_in_question_text($combined->questiontext);

        $subq = $combined->combiner->find_or_create_question_instance('multiresponse', 'mc');
        $subq->question = self::make_oumultiresponse_question_two_of_four('mc');
        $subq->question->defaultmark = 0.25;

        $subq = $combined->combiner->find_or_create_question_instance('selectmenu', 'gs');
        $subq->question = self::make_a_gapselect_question('gs');
        $subq->question->defaultmark = 0.5;

        $subq = $combined->combiner->find_or_create_question_instance('pmatch', 'pm');
        $subq->question = self::make_a_pmatch_question('pm');
        $subq->question->defaultmark = 0.25;

        $combined->hints = array(
            new question_hint_with_parts(1, 'Hint 1.', FORMAT_HTML, true, false),
            new question_hint_with_parts(2, 'Hint 2.', FORMAT_HTML, true, true),
            new question_hint_with_parts(3, 'Hint 3.', FORMAT_HTML, true, true)
        );

        return $combined;
    }


    /**
     * @param string $name
     * @return qtype_varnumericset_question
     */
    protected static function make_a_varnumericset_question($name) {
        $vn = test_question_maker::make_question('varnumericset', 'no_accepted_error');
        $vn->name = $name;
        $vn->answers = array();
        $vn->answers[0] = new qtype_varnumericset_answer('1', // Id.
                                                         '-4.2',  // Answer.
                                                         '1',     // Fraction.
                                                         '<p>Your answer is correct.</p>', // Feedback.
                                                         'html',  // Feedbackformat.
                                                         '0',     // Sigfigs.
                                                         '',      // Error.
                                                         '0.1',   // Syserrorpenalty.
                                                         '0',     // Checknumerical.
                                                         '0',     // Checkscinotation.
                                                         '0',     // Checkpowerof10.
                                                         '0');    // Checkrounding.
        return $vn;
    }

    /**
     * @return qtype_combined_question
     */
    public static function make_a_combined_question_with_oumr_pmatch_varnum_and_gapselect_subquestion() {
        question_bank::load_question_definition_classes('combined');
        $combined = new qtype_combined_question();

        test_question_maker::initialise_a_question($combined);

        $combined->name = 'Selection from drop down list question';
        $combined->questiontext = 'Choose correct 2 check boxes [[mc:multiresponse]]. '.
            'The [[gs:selectmenu:1]] brown [[gs:selectmenu:2]] jumped over the [[gs:selectmenu:3]] dog.'.
            ' [[pm:pmatch]].  [[vn:numeric]].';
        $combined->generalfeedback = 'You need to choose 2 of the 4 check boxes. Then the next sentence contains every letter of'.
            'the alphabet.';
        $combined->qtype = question_bank::get_qtype('combined');

        test_question_maker::set_standard_combined_feedback_fields($combined);

        $combined->combiner = new qtype_combined_combiner_for_run_time_question_instance();
        $combined->combiner->find_included_subqs_in_question_text($combined->questiontext);

        $subq = $combined->combiner->find_or_create_question_instance('multiresponse', 'mc');
        $subq->question = self::make_oumultiresponse_question_two_of_four('mc');
        $subq->question->defaultmark = 0.25;

        $subq = $combined->combiner->find_or_create_question_instance('selectmenu', 'gs');
        $subq->question = self::make_a_gapselect_question('gs');
        $subq->question->defaultmark = 0.25;

        $subq = $combined->combiner->find_or_create_question_instance('pmatch', 'pm');
        $subq->question = self::make_a_pmatch_question('pm');
        $subq->question->defaultmark = 0.25;

        $subq = $combined->combiner->find_or_create_question_instance('numeric', 'vn');
        $subq->question = self::make_a_varnumericset_question('vn');
        $subq->question->defaultmark = 0.25;

        $combined->hints = array(
            new question_hint_with_parts(1, 'Hint 1.', FORMAT_HTML, true, false),
            new question_hint_with_parts(2, 'Hint 2.', FORMAT_HTML, true, true),
            new question_hint_with_parts(3, 'Hint 3.', FORMAT_HTML, true, true)
        );

        return $combined;
    }
}
