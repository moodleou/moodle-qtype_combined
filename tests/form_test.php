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
class qtype_combined_form_test extends advanced_testcase {

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
            'subq:pmatch:3:modelanswer' => '',
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

        // Check model answer.
        $fromform['subq:pmatch:3:modelanswer'] = 'frog';
        $errors = $mform->validation($fromform, array());
        $this->assertNotEmpty($errors);
    }

    /**
     * Test form submit.
     */
    public function test_form_submit_delete() {
        $this->resetAfterTest(true);

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $context = context_course::instance($course->id);
        $contexts = new question_edit_contexts($context);
        $category = question_make_default_categories($contexts->all());

        $fromform = [
                'category' => $category->id,
                'name' => 'Test combined with varnumeric',
                'questiontext' => '<pre>Cat : [[1:selectmenu:1]], Dog: [[2:selectmenu:1]]</pre>',
                'defaultmark' => 1,
                'generalfeedback' => [
                        'text' => '',
                        'format' => 1
                ],
                'subq:selectmenu:1:defaultmark' => 0.5000000,
                'subq:selectmenu:1:shuffleanswers' => 0,
                'subq:selectmenu:1:noofchoices' => 6,
                'subq:selectmenu:1:answer' => ['Kitten', 'Tadpole'],
                'subq:selectmenu:1:generalfeedback' => [
                        'text' => 'OK',
                        'format' => '1'
                ],
                'subq:selectmenu:1:notincludedinquestiontextwilldelete' => true,
                'subqfragment_id' => [
                        'selectmenu_1' => '1',
                        'selectmenu_2' => '2',
                ],
                'subqfragment_type' => [
                        'selectmenu_1' => 'selectmenu',
                        'selectmenu_2' => 'selectmenu',
                ],
                'subq:selectmenu:2:defaultmark' => 0.5,
                'subq:selectmenu:2:shuffleanswers' => 0,
                'subq:selectmenu:2:noofchoices' => 6,
                'subq:selectmenu:2:answer' => ['Puppy', 'Foal'],
                'subq:selectmenu:2:generalfeedback' => [
                        'text' => 'OK',
                        'format' => '1'
                ],
                'subq:selectmenu:2:notincludedinquestiontextwilldelete' => true,
                'correctfeedback' => [
                        'text' => 'Your answer is correct.',
                        'format' => 1
                ],
                'partiallycorrectfeedback' => [
                        'text' => 'Your answer is partially correct.',
                        'format' => 1
                ],
                'shownumcorrect' => 1,
                'incorrectfeedback' => [
                        'text' => 'Your answer is incorrect.',
                        'format' => 1
                ],
                'penalty' => 0.3333333,
                'numhints' => 0,
                'hints' => [],
                'hintshownumcorrect' => [],
                'tags' => 0,
                'id' => 1,
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
        $fromformojb = (object) $fromform;
        $combiner = new qtype_combined_combiner_for_question_type();
        $combiner->save_subqs($fromformojb, $context->id);
        $combiner->load_subq_data_from_db(1);
        $subq1 = $combiner->find_or_create_question_instance('selectmenu', 1);
        $subq2 = $combiner->find_or_create_question_instance('selectmenu', 2);
        $this->assertTrue($subq1->is_in_db());
        $this->assertTrue($subq2->is_in_db());
        // We created a combine question with false data case 1 is_in_db = false .
        $fromform2 = [
                'category' => $category->id,
                'name' => 'Test combined with varnumeric',
                'questiontext' => '<pre>Cat : [[1:selectmenu:1]], Dog: [[2:selectmenu:1]]</pre>',
                'defaultmark' => 1,
                'generalfeedback' => [
                        'text' => '',
                        'format' => 1
                ],
                'subq:selectmenu:1:defaultmark' => 0.5000000,
                'subq:selectmenu:1:shuffleanswers' => 0,
                'subq:selectmenu:1:noofchoices' => 6,
                'subq:selectmenu:1:answer' => ['Kitten', 'Tadpole'],
                'subq:selectmenu:1:generalfeedback' => [
                        'text' => 'OK',
                        'format' => '1'
                ],
                'subq:selectmenu:1:notincludedinquestiontextwilldelete' => true,
                'subqfragment_id' => [
                        'selectmenu_1' => '1',
                        'selectmenu_2' => '2',
                ],
                'subqfragment_type' => [
                        'selectmenu_1' => 'selectmenu',
                        'selectmenu_2' => 'selectmenu',
                ],
                'subq:selectmenu:2:defaultmark' => 0.5,
                'subq:selectmenu:2:shuffleanswers' => 0,
                'subq:selectmenu:2:noofchoices' => 6,
                'subq:selectmenu:2:answer' => ['Puppy', 'Foal'],
                'subq:selectmenu:2:generalfeedback' => [
                        'text' => 'OK',
                        'format' => '1'
                ],
                'subq:selectmenu:2:notincludedinquestiontextwilldelete' => true,
                'subq:selectmenu:3:defaultmark' => 0.5,
                'subq:selectmenu:3:shuffleanswers' => 0,
                'subq:selectmenu:3:noofchoices' => 6,
                'subq:selectmenu:3:answer' => ['Puppy', 'Foal'],
                'subq:selectmenu:3:generalfeedback' => [
                        'text' => 'OK',
                        'format' => '1'
                ],
                'subq:selectmenu:3:notincludedinquestiontextwilldelete' => true,
                'correctfeedback' => [
                        'text' => 'Your answer is correct.',
                        'format' => 1
                ],
                'partiallycorrectfeedback' => [
                        'text' => 'Your answer is partially correct.',
                        'format' => 1
                ],
                'shownumcorrect' => 1,
                'incorrectfeedback' => [
                        'text' => 'Your answer is incorrect.',
                        'format' => 1
                ],
                'penalty' => 0.3333333,
                'numhints' => 0,
                'hints' => [],
                'hintshownumcorrect' => [],
                'tags' => 0,
                'id' => 1,
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

        $fromformojb2 = (object) $fromform2;
        $combiner->save_subqs($fromformojb2, $context->id);
        $combiner = new qtype_combined_combiner_for_question_type();
        $combiner->load_subq_data_from_db(1);
        $subq1 = $combiner->find_or_create_question_instance('selectmenu', 1);
        $subq2 = $combiner->find_or_create_question_instance('selectmenu', 2);
        $subq3 = $combiner->find_or_create_question_instance('selectmenu', 3);

        $this->assertTrue($subq1->is_in_db());
        $this->assertTrue($subq2->is_in_db());
        $this->assertFalse($subq3->is_in_db());

        // We created a combine quesstion with false data case 2 is_in_db = true.
        $fromform3 = [
                'category' => $category->id,
                'name' => 'Test combined with varnumeric',
                'questiontext' => '<pre>Cat : [[1:selectmenu:1]]</pre>',
                'defaultmark' => 1,
                'generalfeedback' => [
                        'text' => '',
                        'format' => 1
                ],
                'subq:selectmenu:1:defaultmark' => 1,
                'subq:selectmenu:1:shuffleanswers' => 0,
                'subq:selectmenu:1:noofchoices' => 6,
                'subq:selectmenu:1:answer' => ['Kitten', 'Tadpole'],
                'subq:selectmenu:1:generalfeedback' => [
                        'text' => 'OK',
                        'format' => '1'
                ],
                'subq:selectmenu:1:notincludedinquestiontextwilldelete' => true,
                'subqfragment_id' => [
                        'selectmenu_1' => '1',
                ],
                'subqfragment_type' => [
                        'selectmenu_1' => 'selectmenu',
                ],
                'subq:selectmenu:2:defaultmark' => 0.5,
                'subq:selectmenu:2:shuffleanswers' => 0,
                'subq:selectmenu:2:noofchoices' => 6,
                'subq:selectmenu:2:answer' => ['Puppy', 'Foal'],
                'subq:selectmenu:2:generalfeedback' => [
                        'text' => 'OK',
                        'format' => '1'
                ],
                'subq:selectmenu:2:notincludedinquestiontextwilldelete' => true,
                'correctfeedback' => [
                        'text' => 'Your answer is correct.',
                        'format' => 1
                ],
                'partiallycorrectfeedback' => [
                        'text' => 'Your answer is partially correct.',
                        'format' => 1
                ],
                'shownumcorrect' => 1,
                'incorrectfeedback' => [
                        'text' => 'Your answer is incorrect.',
                        'format' => 1
                ],
                'penalty' => 0.3333333,
                'numhints' => 0,
                'hints' => [],
                'hintshownumcorrect' => [],
                'tags' => 0,
                'id' => 1,
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

        $fromformojb3 = (object) $fromform3;
        $combiner->save_subqs($fromformojb3, $context->id);
        $combiner = new qtype_combined_combiner_for_question_type();
        $combiner->load_subq_data_from_db(1);
        $subq1 = $combiner->find_or_create_question_instance('selectmenu', 1);
        $subq2 = $combiner->find_or_create_question_instance('selectmenu', 2);
        $this->assertTrue($subq1->is_in_db());
        $this->assertFalse($subq2->is_in_db());
    }
}
