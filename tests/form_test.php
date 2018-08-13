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
 * Unit tests for the combined question editing form.
 *
 * @package    qtype_combined
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');


/**
 * Unit tests for qtype_combined editing form.
 *
 * @copyright  2016 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_combined_question_test extends advanced_testcase {

    /**
     * Test editing form validation, particularly with the numeric subquestion.
     */
    public function test_form_validation() {

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $context = context_course::instance($course->id);
        $contexts = new question_edit_contexts($context);
        $category = question_make_default_categories($contexts->all());

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

        $fromform = array(
            'category' => 1,
            'name' => 'Test combined with varnumeric',
            'questiontext' => array(
                    'text' => 'Choose a answer? [[1:multiresponse:v]]<br>What is 1 - 1? [[2:numeric:__10__]]
                        <br>Pmatch question [[3:pmatch]]',
                    'format' => 1
            ),
            'defaultmark' => 1,
            'generalfeedback' => array(
                    'text' => '',
                    'format' => 1
            ),
            'subq:multiresponse:1:defaultmark' => 0.3333333,
            'subq:multiresponse:1:shuffleanswers' => 1,
            'subq:multiresponse:1:noofchoices' => 3,
            'subq:multiresponse:1:answer' => array(['text' => 'a', 'format' => 1], ['text' => 'a', 'format' => 1],
                    ['text' => 'a', 'format' => 1]),
            'subq:multiresponse:1:correctanswer' => array('1', '0', '0'),
            'subq:multiresponse:1:generalfeedback' => array(
                    'text' => 'OK',
                    'format' => '1'
            ),
            'subqfragment_id' => array(
                    'multiresponse_1' => '1',
                    'numeric_2' => '2',
                    'pmatch_3' => '3'
            ),
            'subqfragment_type' => array(
                    'multiresponse_1' => 'multiresponse',
                    'numeric_2' => 'numeric',
                    'pmatch_3' => 'pmatch'
            ),
            'subq:numeric:2:defaultmark' => 0.3333333,
            'subq:numeric:2:answer' => array('0'),
            'subq:numeric:2:error' => array(''),
            'subq:numeric:2:requirescinotation' => 0,
            'subq:numeric:2:generalfeedback' => array(
                    'text' => 'OK',
                    'format' => 1
            ),
            'subq:pmatch:3:defaultmark' => 0.3333333,
            'subq:pmatch:3:allowsubscript' => 0,
            'subq:pmatch:3:allowsuperscript' => 0,
            'subq:pmatch:3:usecase' => 0,
            'subq:pmatch:3:applydictionarycheck' => 1,
            'subq:pmatch:3:extenddictionary' => '',
            'subq:pmatch:3:converttospace' => '[];,./',
            'subq:pmatch:3:synonymsdata' => array(
                    array(
                        'word' => 'AA', 'synonyms' => 'BB'
                    )
            ),
            'subq:pmatch:3:answer' => ['match(100)'],
            'subq:pmatch:3:generalfeedback[text]' => 'ABCDEF',
            'subq:pmatch:3:generalfeedback[format]' => 1,
            'subq:pmatch:3:generalfeedback[itemid]' => 571101409,
            'correctfeedback' => array(
                    'text' => 'Your answer is correct.',
                    'format' => 1
            ),
            'partiallycorrectfeedback' => array(
                    'text' => 'Your answer is partially correct.',
                    'format' => 1
            ),
            'shownumcorrect' => 1,
            'incorrectfeedback' => array(
                    'text' => 'Your answer is incorrect.',
                    'format' => 1
            ),
            'penalty' => 0.3333333,
            'numhints' => 0,
            'hints' => array(),
            'hintshownumcorrect' => array(),
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
        );

        // Try a form that has all options for validation.
        $errors = $mform->validation($fromform, array());
        $this->assertEmpty($errors);

        // Try an empty numeric answer (should not validate).
        $fromform['subq:numeric:2:answer'] = array('');
        $errors = $mform->validation($fromform, array());
        $this->assertNotEmpty($errors);

        // Check duplicate synonym.
        $fromform['subq:numeric:2:answer'] = array(0);
        $fromform['subq:pmatch:3:synonymsdata'][] = array('word' => 'AA', 'synonyms' => 'BB');
        $errors = $mform->validation($fromform, array());
        $this->assertNotEmpty($errors);
    }
}
