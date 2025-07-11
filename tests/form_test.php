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

namespace qtype_combined;

use context_course;
use qtype_combined_combiner_for_form;
use qtype_combined_combiner_for_question_type;
use question_bank;
use ReflectionMethod;
use stdClass;
use qtype_combined_test_helper;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/combined/combiner/forform.php');
require_once($CFG->dirroot . '/question/type/combined/combiner/forquestiontype.php');
require_once($CFG->dirroot . '/question/type/combined/tests/helper.php');

/**
 * Unit tests for qtype_combined editing form.
 *
 * @package    qtype_combined
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \qtype_combined_edit_form
 * @covers \qtype_combined_combiner_for_form
 * @covers \qtype_combined_combiner_base
 */
final class form_test extends \advanced_testcase {

    /**
     * Deal with the different location of question_edit_contexts in 3.x and 4.x.
     *
     * @param \context $context A context.
     * @return \question_edit_contexts|\core_question\local\bank\question_edit_contexts The one for that context.
     */
    protected function get_question_edit_contexts(\context $context) {
        global $CFG;

        if (class_exists('\core_question\local\bank\question_edit_contexts')) {
            // Moodle 4.x.
            return new \core_question\local\bank\question_edit_contexts($context);
        } else {
            // Moodle 3.x.
            require_once($CFG->libdir . '/questionlib.php');
            return new \question_edit_contexts($context);
        }
    }

    /**
     * Test editing form validation, particularly with the numeric subquestion.
     */
    public function test_form_validation(): void {

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        if (qtype_combined_test_helper::plugin_is_installed('mod_qbank')) {
            $qbank = $gen->create_module('qbank', ['course' => $course->id]);
            $context = \context_module::instance($qbank->cmid);
            $contexts = $this->get_question_edit_contexts($context);
            $category = question_get_default_category($context->id, true);
        } else {
            // TODO: remove this once Moodle 5.0 is the lowest supported version.
            $contexts = $this->get_question_edit_contexts(\context_course::instance($course->id));
            $category = question_make_default_categories($contexts->all());
        }

        $question = new stdClass();
        $question->category = $category->id;
        $question->contextid = $category->contextid;
        $question->qtype = 'combined';
        $question->createdby = 1;
        $question->questiontext = 'Initial text';
        $question->timecreated = '1234567890';
        $question->formoptions = new stdClass();
        $question->formoptions->canedit = true;
        $question->formoptions->canmove = true;
        $question->formoptions->cansaveasnew = false;
        $question->formoptions->repeatelements = true;

        $qtypeobj = question_bank::get_qtype($question->qtype);

        $mform = $qtypeobj->create_editing_form('question.php', $question, $category, $contexts, true);

        $fromform = [
            'category' => 1,
            'name' => 'Test combined with varnumeric',
            'questiontext' => [
                'text' => 'Choose a answer? [[1:multiresponse:v]]<br>What is 1 - 1? [[2:numeric:__10__]]
                    <br>Pmatch question [[3:pmatch]]<br>Showworking [[5:showworking:__80x5__]]',
                'format' => 1,
            ],
            'defaultmark' => 1,
            'generalfeedback' => [
                'text' => '',
                'format' => 1,
            ],
            'subq:multiresponse:1:defaultmark' => 0.3333333,
            'subq:multiresponse:1:shuffleanswers' => 1,
            'subq:multiresponse:1:noofchoices' => 3,
            'subq:multiresponse:1:answer' => [
                ['text' => 'a', 'format' => 1],
                ['text' => 'a', 'format' => 1],
                ['text' => 'a', 'format' => 1],
            ],
            'subq:multiresponse:1:correctanswer' => ['1', '0', '0'],
            'subq:multiresponse:1:generalfeedback' => [
                'text' => 'OK',
                'format' => '1',
            ],
            'subqfragment_id' => [
                'multiresponse_1' => '1',
                'numeric_2' => '2',
                'pmatch_3' => '3',
            ],
            'subqfragment_type' => [
                'multiresponse_1' => 'multiresponse',
                'numeric_2' => 'numeric',
                'pmatch_3' => 'pmatch',
            ],
            'subq:numeric:2:defaultmark' => 0.3333333,
            'subq:numeric:2:answer' => ['0'],
            'subq:numeric:2:error' => [''],
            'subq:numeric:2:requirescinotation' => 0,
            'subq:numeric:2:generalfeedback' => [
                'text' => 'OK',
                'format' => 1,
            ],
            'subq:pmatch:3:defaultmark' => 0.3333333,
            'subq:pmatch:3:allowsubscript' => 0,
            'subq:pmatch:3:allowsuperscript' => 0,
            'subq:pmatch:3:usecase' => 0,
            'subq:pmatch:3:modelanswer' => '',
            'subq:pmatch:3:applydictionarycheck' => 1,
            'subq:pmatch:3:extenddictionary' => '',
            'subq:pmatch:3:converttospace' => '[];,./',
            'subq:pmatch:3:synonymsdata' => [
                [
                    'word' => 'AA',
                    'synonyms' => 'BB',
                ],
            ],
            'subq:pmatch:3:answer' => ['match(100)'],
            'subq:pmatch:3:generalfeedback[text]' => 'ABCDEF',
            'subq:pmatch:3:generalfeedback[format]' => 1,
            'subq:pmatch:3:generalfeedback[itemid]' => 571101409,
            'correctfeedback' => [
                'text' => 'Your answer is correct.',
                'format' => 1,
            ],
            'subq:showworking:5:defaultmark' => 0,
            'subq:showworking:5:answer' => ['text' => 'test showworking'],
            'partiallycorrectfeedback' => [
                'text' => 'Your answer is partially correct.',
                'format' => 1,
            ],
            'shownumcorrect' => 1,
            'incorrectfeedback' => [
                'text' => 'Your answer is incorrect.',
                'format' => 1,
            ],
            'penalty' => 0.3333333,
            'numhints' => 0,
            'hints' => [],
            'hintshownumcorrect' => [],
            'tags' => 0,
            'id' => 0,
            'inpopup' => 0,
            'cmid' => 0,
            'courseid' => $course->id,
            'returnurl' => '/mod/quiz/edit.php?cmid=0',
            'scrollpos' => 0,
            'appendqnumstring' => '',
            'qtype' => 'combined',
            'makecopy' => 0,
            'updatebutton' => 'Save changes and continue editing',
        ];

        // Try a form that has all options for validation.
        $errors = $mform->validation($fromform, []);
        $this->assertEquals([], $errors);

        // Try an empty numeric answer (should not validate).
        $fromform['subq:numeric:2:answer'] = [''];
        $errors = $mform->validation($fromform, []);
        $this->assertEquals('You need to enter a valid number here in the answer field.', $errors['subq:numeric:2:answergroup']);

        // Check duplicate synonym.
        $fromform['subq:numeric:2:answer'] = [0];
        $fromform['subq:pmatch:3:synonymsdata'][] = ['word' => 'AA', 'synonyms' => 'BB'];
        $errors = $mform->validation($fromform, []);
        $this->assertNotEmpty($errors);

        // Check var numberic qtype has HTML in answer.
        $fromform['subq:numeric:2:answer'] = ['3 x 10<sup>8</sup>'];
        $errors = $mform->validation($fromform, []);
        $this->assertEquals(
            'You must not use HTML in the answer formula. Input numbers like 3.e8 or 3.14159.',
            $errors['subq:numeric:2:answergroup']
        );

        // Check model answer.
        $fromform['subq:pmatch:3:modelanswer'] = 'frog';
        $errors = $mform->validation($fromform, []);
        $this->assertNotEmpty($errors);
    }

    /**
     * Test validate_question_text.
     *
     * @dataProvider get_validate_question_text_provider
     * @param string $questiontext The question text to validate.
     * @param array $expectederrors The expected error messages.
     */
    public function test_validate_question_text(string $questiontext, array $expectederrors): void {
        $this->resetAfterTest();
        $combiner = new qtype_combined_combiner_for_form();
        $method = new ReflectionMethod(qtype_combined_combiner_for_form::class, 'validate_question_text');
        $method->setAccessible(true);
        $result = $method->invoke($combiner, $questiontext);
        $this->assertEquals($expectederrors, $result);
    }

    /**
     * Data provider for {@see test_validate_question_text()}.
     * @return array
     */
    public static function get_validate_question_text_provider(): array {

        return [
            'valid' => [
                'Question combined [[1:numeric:__10__]]<br>Showworking [[2:showworking:__80x5__]]
                <br>[[3:pmatch:__20__]]<br>[[4:multiresponse]]<br>[[5:singlechoice]]<br>[[6:selectmenu:2]]',
                [],
            ],
            'missing_close_brackets_showworking' => [
                'Question combined [[1:numeric:__10__]]<br>1 [[2:showworking:____<br>2 [[3:showworking:__80x5__]]',
                ['questiontext' => get_string('err_invalid_width_specifier_postfix_showworking', 'qtype_combined',
                    'showworking')],
            ],
            'invalid_input_format_editor_type_for_showworking_option_plain' => [
                'Question combined [[1:numeric:__10__]] <br> 1 [[2:showworking:__20x5__:text]]' .
                '<br> 2 [[2:showworking:__20x5__:plaintext]]',
                ['questiontext' => get_string('err_invalid_width_specifier_postfix_showworking',
                    'qtype_combined', 'showworking')],
            ],
            'invalid_input_format_editor_type_for_showworking_option_editor' => [
                'Question combined [[1:numeric:__10__]] <br> 1 [[2:showworking:__editor]]' .
                '<br> 2 [[2:showworking:__editor]]',
                ['questiontext' => get_string('err_invalid_width_specifier_postfix_showworking',
                    'qtype_combined', 'showworking')],
            ],
            'invalid_showworking' => [
                'Question combined [[1:numeric:__10__]]<br>1 [[2:showworking:__A__]]',
                ['questiontext' => get_string('err_invalid_width_specifier_postfix_showworking', 'qtype_combined',
                    'showworking')],
            ],
            'needed_sub-question' => [
                'Question combined [[2:showworking:__80x5__]]',
                ['questiontext' => get_string('noembeddedquestions', 'qtype_combined')],
            ],
            'upper_case' => [
                'Question combined [[1:Numeric:__10__]]',
                ['questiontext' => get_string('err_unrecognisedqtype', 'qtype_combined',
                    ['qtype' => 'Numeric', 'fullcode' => '[[1:Numeric:__10__]]'])],
            ],
            'whitespace' => [
                'Question combined [[1:n umeric:__10__]]',
                ['questiontext' => get_string('err_unrecognisedqtype', 'qtype_combined',
                    ['qtype' => 'n umeric', 'fullcode' => '[[1:n umeric:__10__]]'])],
            ],
            'duplicate' => [
                'Question combined [[1:numeric:__10__]]<br>[[1:numeric:__10__]]',
                ['questiontext' => get_string('err_thisqtypecannothavemorethanonecontrol', 'qtype_combined',
                    ['qtype' => 'numeric', 'qid' => '1'])],
            ],
            'invalid_numeric' => [
                'Question combined [[1:numeric:__A__]]',
                ['questiontext' => get_string('err_invalid_width_specifier_postfix', 'qtype_combined', 'numeric')],
            ],
            'valid_multiresponse' => [
                'Question combined [[1:multiresponse:v]]',
                [],
            ],
            'invalid_multiresponse' => [
                'Question combined [[1:multiresponse:T]]',
                ['questiontext' => get_string('err_accepts_vertical_or_horizontal_layout_param',
                    'qtype_combined', 'multiresponse')],
            ],
            'valid_selectmenu' => [
                'Question combined [[4:selectmenu:1]]',
                [],
            ],
            'invalid_selectmenu' => [
                'Question combined [[4:selectmenu:T]]',
                ['questiontext' => get_string('err_invalid_number', 'qtype_combined', 'selectmenu')],
            ],
            'valid_singlechoice' => [
                'Question combined [[1:singlechoice:v]]',
                [],
            ],
            'invalid_singlechoice' => [
                'Question combined [[1:singlechoice:T]]',
                ['questiontext' => get_string('err_accepts_vertical_or_horizontal_layout_param', 'qtype_combined',
                    'singlechoice')],
            ],
            'valid_pmatch' => [
                'Question combined [[3:pmatch]]]',
                [],
            ],
            'invalid_pmatch' => [
                'Question combined [[3:pmatch:__s__]]',
                ['questiontext' => get_string('err_invalid_width_specifier_postfix', 'qtype_combined', 'pmatch')],
            ],
        ];
    }
}
