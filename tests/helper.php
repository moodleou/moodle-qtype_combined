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
 * Test helper class for the combined question type.
 *
 * @package   qtype_combined
 * @copyright 2013 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_combined_test_helper extends question_test_helper {

    public function get_test_questions() {
        return ['allsubparts', 'pmatchsynonyms', 'numerical'];
    }

    /**
     * Get the data you would get by saving the editing form for question with one subpart of each type.
     *
     * @return stdClass simulated form data.
     */
    public function get_combined_question_form_data_allsubparts(): stdClass {
        $fromform = new stdClass();

        $fromform->name = 'New combined question';
        $fromform->questiontext = ['text' => 'What is the pH of a 0.1M solution? [[1:numeric:__10__]]<br/>' .
                'What is the IUPAC name of the molecule? [[2:pmatch:__20__]]<br/>' .
                'Which elements are shown? [[3:multiresponse]]<br/>' .
                'Which element is shown as white? [[6:singlechoice]]<br/>' .
                'When a solution is combined with oil the result is a [[4:selectmenu:2]]<br/>' .
                'Showworking [[5:showworking:__80x5__]]', 'format' => FORMAT_HTML];
        $fromform->defaultmark = 1.0;
        if (class_exists('\core_question\local\bank\question_version_status')) {
            $fromform->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;
        }
        $fromform->generalfeedback = ['text' => 'The molecule is ethanoic acid which is more commonly known' .
                ' as acetic acid or in dilute solution as vinegar. The constituent elements are carbon (grey),' .
                ' hydrogen (white) and oxygen (red). A 0.1M solution has a pH of 2.88 and when a solution ' .
                'is combined with oil the result is a vinaigrette.', 'format' => FORMAT_HTML];

        $fromform->subqfragment_id = [
            'numeric_1' => '1',
            'pmatch_2' => '2',
            'multiresponse_3' => '3',
            'singlechoice_6' => '6',
            'selectmenu_4' => '4',
            'showworking_5' => '5',
        ];
        $fromform->subqfragment_type = [
            'numeric_1' => 'numeric',
            'pmatch_2' => 'pmatch',
            'multiresponse_3' => 'multiresponse',
            'singlechoice_6' => 'singlechoice',
            'selectmenu_4' => 'selectmenu',
            'showworking_5' => 'showworking',
        ];

        $fromform->{'subq:numeric:1:defaultmark'} = '0.2';
        $fromform->{'subq:numeric:1:answer'} = ['2.88'];
        $fromform->{'subq:numeric:1:error'} = [''];
        $fromform->{'subq:numeric:1:requirescinotation'} = '0';
        $fromform->{'subq:numeric:1:generalfeedback'} = ['text' => 'You have the incorrect value for the pH',
                'format' => FORMAT_HTML];

        $fromform->{'subq:pmatch:2:defaultmark'} = '0.2';
        $fromform->{'subq:pmatch:2:allowsubscript'} = '0';
        $fromform->{'subq:pmatch:2:allowsuperscript'} = '0';
        $fromform->{'subq:pmatch:2:usecase'} = '0';
        $fromform->{'subq:pmatch:2:applydictionarycheck'} = '-';
        $fromform->{'subq:pmatch:2:extenddictionary'} = '';
        $fromform->{'subq:pmatch:2:sentencedividers'} = '.?!';
        $fromform->{'subq:pmatch:2:converttospace'} = ',;:';
        $fromform->{'subq:pmatch:2:modelanswer'} = 'ethanoic acid';
        $fromform->{'nosynonymssubq:pmatch:2:synonymsdata'} = 1;
        $fromform->{'subq:pmatch:2:synonymsdata'} = [['word' => '', 'synonyms' => '']];
        $fromform->{'subq:pmatch:2:answer'} = ['match_mw (ethanoic acid)'];
        $fromform->{'subq:pmatch:2:generalfeedback'} = ['text' => 'You have the incorrect IUPAC name.',
                'format' => FORMAT_HTML];

        $fromform->{'subq:multiresponse:3:defaultmark'} = '0.2';
        $fromform->{'subq:multiresponse:3:shuffleanswers'} = '1';
        $fromform->{'subq:multiresponse:3:answernumbering'} = 'none';
        $fromform->{'subq:multiresponse:3:noofchoices'} = 7;
        $fromform->{'subq:multiresponse:3:answer'} = [
                ['text' => 'C/carbon', 'format' => FORMAT_HTML],
                ['text' => 'H/hydrogen', 'format' => FORMAT_HTML],
                ['text' => 'O/oxygen', 'format' => FORMAT_HTML],
                ['text' => 'N/nitrogen', 'format' => FORMAT_HTML],
                ['text' => 'F/fluorine', 'format' => FORMAT_HTML],
                ['text' => 'Cl/chlorine', 'format' => FORMAT_HTML],
                ['text' => '<b>Br/bromine</b>', 'format' => FORMAT_HTML],
            ];
        $fromform->{'subq:multiresponse:3:correctanswer'} = [
                '1',
                '1',
                '1',
                '0',
                '0',
                '0',
                '0',
            ];
        $fromform->{'subq:multiresponse:3:generalfeedback'} = [
                'text' => 'Your choice of elements is not entirely correct.', 'format' => FORMAT_HTML];

        $fromform->{'subq:singlechoice:6:defaultmark'} = '0.2';
        $fromform->{'subq:singlechoice:6:shuffleanswers'} = '1';
        $fromform->{'subq:singlechoice:6:answernumbering'} = 'none';
        $fromform->{'subq:singlechoice:6:noofchoices'} = 3;
        $fromform->{'subq:singlechoice:6:answer'} = [
                ['text' => 'C carbon', 'format' => FORMAT_HTML],
                ['text' => 'H hydrogen', 'format' => FORMAT_HTML],
                ['text' => '<b>O oxygen</b>', 'format' => FORMAT_HTML],
            ];
        $fromform->{'subq:singlechoice:6:fraction'} = [
                '0.0',
                '1.0',
                '0.0',
            ];
        $fromform->{'subq:singlechoice:6:feedback'} = [
                ['text' => 'Carbon is conventionally black', 'format' => FORMAT_HTML],
                ['text' => 'That is correct', 'format' => FORMAT_HTML],
                ['text' => 'Oxygen is conventionally red', 'format' => FORMAT_HTML],
            ];
        $fromform->{'subq:singlechoice:6:generalfeedback'} = [
            'text' => 'Your name for the white atoms is incorrect.', 'format' => FORMAT_HTML];

        $fromform->{'subq:selectmenu:4:defaultmark'} = '0.2';
        $fromform->{'subq:selectmenu:4:shuffleanswers'} = '0';
        $fromform->{'subq:selectmenu:4:noofchoices'} = 7;
        $fromform->{'subq:selectmenu:4:answer'} = [
                'Wine',
                'Vinagrette',
                'Paint Thinner',
                'Mayonnaise',
        ];
        $fromform->{'subq:selectmenu:4:generalfeedback'} = [
              'text' => 'Your name for the mixture is incorrect.', 'format' => FORMAT_HTML];

        test_question_maker::set_standard_combined_feedback_form_data($fromform);
        $fromform->shownumcorrect = 0;
        $fromform->penalty = '0.3333333';
        $fromform->numhints = 2;
        $fromform->hint = [
                ['text' => 'First hint', 'format' => FORMAT_HTML],
                ['text' => 'Second hint', 'format' => FORMAT_HTML],
            ];
        $fromform->hintclearwrong = [1, 1];
        $fromform->hintshownumcorrect = [0, 0];

        return $fromform;
    }

    /**
     * Get the data from saving the form for a question with one pmatch subpart with synonyms.
     *
     * @return stdClass simulated form data.
     */
    public function get_combined_question_form_data_pmatchsynonyms(): stdClass {
        $fromform = new stdClass();

        $fromform->name = 'Combined pmatch with synonyms';
        $fromform->questiontext = ['text' => 'The UK prime minister lives at [[1:pmatch]].',
                'format' => FORMAT_HTML];
        $fromform->defaultmark = 1.0;
        if (class_exists('\core_question\local\bank\question_version_status')) {
            $fromform->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;
        }
        $fromform->generalfeedback = [
                'text' => "General feedback: The UK prime minister lives at 'Number 10' (Downing Street).",
                'format' => FORMAT_HTML];

        $fromform->subqfragment_id = [
            'pmatch_1' => '1',
        ];
        $fromform->subqfragment_type = [
            'pmatch_1' => 'pmatch',
        ];

        $fromform->{'subq:pmatch:1:defaultmark'} = '1.0';
        $fromform->{'subq:pmatch:1:allowsubscript'} = '0';
        $fromform->{'subq:pmatch:1:allowsuperscript'} = '0';
        $fromform->{'subq:pmatch:1:usecase'} = '0';
        $fromform->{'subq:pmatch:1:applydictionarycheck'} = '-';
        $fromform->{'subq:pmatch:1:extenddictionary'} = '';
        $fromform->{'subq:pmatch:1:sentencedividers'} = '.?!';
        $fromform->{'subq:pmatch:1:converttospace'} = ',;:';
        $fromform->{'subq:pmatch:1:modelanswer'} = 'ethanoic acid';
        $fromform->{'nosynonymssubq:pmatch:1:synonymsdata'} = 1;
        $fromform->{'subq:pmatch:1:synonymsdata'} = [['word' => 'ten', 'synonyms' => '10']];
        $fromform->{'subq:pmatch:1:answer'} = ['match(number ten)'];
        $fromform->{'subq:pmatch:1:generalfeedback'} = ['text' => 'That is not what we are looking for.',
                'format' => FORMAT_HTML];

        test_question_maker::set_standard_combined_feedback_form_data($fromform);
        $fromform->shownumcorrect = 0;
        $fromform->penalty = '0.3333333';
        $fromform->numhints = 0;
        $fromform->hint = [];
        $fromform->hintclearwrong = [];
        $fromform->hintshownumcorrect = [];

        return $fromform;
    }

    /**
     * Get the data from saving the form for a question with two numerical sub-parts.
     *
     * @return stdClass simulated form data.
     */
    public function get_combined_question_form_data_numerical(): stdClass {
        $fromform = new stdClass();

        $fromform->name = 'Combined numerical';
        $fromform->questiontext = ['text' => 'What 1.5 + 5? [[no1:numeric:__10__]]<br/>' .
                'What 10 + 1? [[no2:numeric:__10__]]', 'format' => FORMAT_HTML];
        $fromform->defaultmark = 1.0;
        if (class_exists('\core_question\local\bank\question_version_status')) {
            $fromform->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;
        }
        $fromform->generalfeedback = [
                'text' => "General feedback: 1.5 + 5 = 6.5. 10 + 1 = 11.",
                'format' => FORMAT_HTML];

        $fromform->subqfragment_id = [
            'numeric_no1' => 'no1',
            'numeric_no2' => 'no2',
        ];
        $fromform->subqfragment_type = [
            'numeric_no1' => 'numeric',
            'numeric_no2' => 'numeric',
        ];

        $fromform->{'subq:numeric:no1:defaultmark'} = '0.5';
        $fromform->{'subq:numeric:no1:answer'} = ['6.5'];
        $fromform->{'subq:numeric:no1:error'} = [''];
        $fromform->{'subq:numeric:no1:requirescinotation'} = '0';
        $fromform->{'subq:numeric:no1:generalfeedback'} = ['text' => 'That is not correct.',
                'format' => FORMAT_HTML];

        $fromform->{'subq:numeric:no2:defaultmark'} = '0.5';
        $fromform->{'subq:numeric:no2:answer'} = ['11'];
        $fromform->{'subq:numeric:no2:error'} = [''];
        $fromform->{'subq:numeric:no2:requirescinotation'} = '0';
        $fromform->{'subq:numeric:no2:generalfeedback'} = ['text' => 'That is not correct.',
                'format' => FORMAT_HTML];

        test_question_maker::set_standard_combined_feedback_form_data($fromform);
        $fromform->shownumcorrect = 0;
        $fromform->penalty = '0.3333333';
        $fromform->numhints = 0;
        $fromform->hint = [];
        $fromform->hintclearwrong = [];
        $fromform->hintshownumcorrect = [];

        return $fromform;
    }

    /**
     * @param array $qtypes, ... variable number of params accepted, they are all strings, qtypes whose helpers to include
     * @return bool|string error - false or a message about qtypes that are not installed
     */
    public static function safe_include_test_helpers(...$qtypes) {
        global $CFG;
        $notfound = [];
        foreach ($qtypes as $qtype) {
            if (!is_readable($CFG->dirroot . '/question/type/' . $qtype . '/tests/helper.php')) {
                $notfound[] = $qtype;
            }
        }
        if (count($notfound)) {
            return "Test skipped some required question types are not installed " . join(', ', $notfound) . ".";
        }
        foreach ($qtypes as $qtype) {
            require_once($CFG->dirroot . '/question/type/' . $qtype . '/tests/helper.php');
        }
        return false;
    }

    /**
     * @param string $name
     * @return qtype_gapselect_question
     */
    protected static function make_a_gapselect_question($name) {
        /** @var qtype_gapselect_question $gapselect */
        $gapselect = test_question_maker::make_question('gapselect');
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
        /** @var qtype_oumultiresponse_question $mr */
        $mr = test_question_maker::make_question('oumultiresponse', 'two_of_four');
        $mr->name = $name;
        $mr->shuffleanswers = false;
        $mr->hints = [];
        return $mr;
    }

    /**
     * @param string $name
     * @return qtype_multichoice_single_question
     */
    protected static function make_multichoice_question_one_of_four($name) {
        $mc = test_question_maker::make_a_multichoice_single_question();
        $mc->name = $name;
        $mc->shuffleanswers = false;
        return $mc;
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
    public static function make_a_combined_question_with_multichoice_subquestion() {
        question_bank::load_question_definition_classes('combined');
        $combined = new qtype_combined_question();

        test_question_maker::initialise_a_question($combined);

        $combined->name = 'Combined with single response subquestion';
        $combined->questiontext = 'Which of these is how you write 1? [[sr:singlechoice]].';
        $combined->generalfeedback = 'The answer is "One".';
        $combined->qtype = question_bank::get_qtype('combined');

        test_question_maker::set_standard_combined_feedback_fields($combined);

        $combined->combiner = new qtype_combined_combiner_for_run_time_question_instance();
        $combined->combiner->find_included_subqs_in_question_text($combined->questiontext);

        $subq = $combined->combiner->find_or_create_question_instance('singlechoice', 'sr');
        $subq->question = self::make_multichoice_question_one_of_four('sr');

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
     * @return qtype_combined_question
     */
    public static function make_a_combined_question_with_oumr_and_showworking_subquestion() {
        question_bank::load_question_definition_classes('combined');
        $combined = new qtype_combined_question();

        test_question_maker::initialise_a_question($combined);

        $combined->name = 'Combined with working';
        $combined->questiontext = 'Choose correct 2 check boxes [[mc:multiresponse]]. '.
            'Why do you think that? [[sw:showworking:__80x5__]]';
        $combined->generalfeedback = 'You need to choose 2 of the 4 check boxes.';
        $combined->qtype = question_bank::get_qtype('combined');

        test_question_maker::set_standard_combined_feedback_fields($combined);

        $combined->combiner = new qtype_combined_combiner_for_run_time_question_instance();
        $combined->combiner->find_included_subqs_in_question_text($combined->questiontext);

        $subq = $combined->combiner->find_or_create_question_instance('multiresponse', 'mc');
        $subq->question = self::make_oumultiresponse_question_two_of_four('mc');
        $subq->question->defaultmark = 1;

        $subq = $combined->combiner->find_or_create_question_instance('showworking', 'sw');
        $subq->question = new qtype_combined_showworking_fake_question('sw');
        $subq->question->defaultmark = 0;

        $combined->hints = [
            new question_hint_with_parts(1, 'Hint 1.', FORMAT_HTML, true, false),
            new question_hint_with_parts(2, 'Hint 2.', FORMAT_HTML, true, true),
        ];

        return $combined;
    }

    /**
     * @param string $name
     * @return qtype_pmatch_question
     */
    protected static function make_a_pmatch_question($name) {
        $pm = qtype_pmatch_test_helper::make_a_pmatch_question();
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
        $combined->contextid = context_system::instance()->id;

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
        $subq->question->contextid = $combined->contextid;

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
        $combined->contextid = context_system::instance()->id;

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
        $subq->question->contextid = $combined->contextid;
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
