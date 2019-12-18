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
 * Unit tests for the select missing words question question definition class.
 *
 * @package   qtype_combined
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/question/format/xml/format.php');
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/combined/tests/helper.php');


/**
 * Unit tests for the combined question definition class.
 *
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_combined_test extends question_testcase {
    /** @var qtype_combined instance of the question type class to test. */
    protected $qtype;

    protected function setUp() {
        $this->qtype = question_bank::get_qtype('combined');;
    }

    protected function tearDown() {
        $this->qtype = null;
    }

    public function test_export_to_xml() {
        $qdata =
            (object)array(
                'id' => '8862',
                'category' => '1299,2005',
                'parent' => '0',
                'name' => 'combined with singlechoice',
                'questiontext' => '<p><span style="text-align: inherit;">Select the capital cities.</span></p>Germany:'
                    . '[[1:singlechoice:v]]<br>Spain: [[2:singlechoice:v]]<br><br><p></p>',
                'questiontextformat' => '1',
                'generalfeedback' => '',
                'generalfeedbackformat' => '1',
                'defaultmark' => 1.0,
                'penalty' => 0.3333333,
                'qtype' => 'combined',
                'length' => '1',
                'stamp' => 'mk4359.vledev3.open.ac.uk+191209172943+8eATKi',
                'version' => 'mk4359.vledev3.open.ac.uk+191209172943+f8G7pL',
                'hidden' => '0',
                'timecreated' => '1575912583',
                'timemodified' => '1575912583',
                'createdby' => '2',
                'modifiedby' => '2',
                'idnumber' => null,
                'options' =>
                    (object)array(
                        'id' => '126',
                        'questionid' => '8862',
                        'correctfeedback' => 'Your answer is correct.',
                        'correctfeedbackformat' => '1',
                        'partiallycorrectfeedback' => 'Your answer is partially correct.',
                        'partiallycorrectfeedbackformat' => '1',
                        'incorrectfeedback' => 'Your answer is incorrect.',
                        'incorrectfeedbackformat' => '1',
                        'shownumcorrect' => '1',
                    ),
                'hints' =>
                    array(),
                'subquestions' =>
                    array(
                        8864 =>
                            (object)array(
                                'id' => '8864',
                                'category' => '1299',
                                'parent' => '8862',
                                'name' => '2',
                                'questiontext' => '',
                                'questiontextformat' => '0',
                                'generalfeedback' => '',
                                'generalfeedbackformat' => '1',
                                'defaultmark' => 0.5,
                                'penalty' => 0.3333333,
                                'qtype' => 'multichoice',
                                'length' => '1',
                                'stamp' => 'mk4359.vledev3.open.ac.uk+191209172943+Nb2MRy',
                                'version' => 'mk4359.vledev3.open.ac.uk+191209172943+KH9Dlx',
                                'hidden' => '0',
                                'timecreated' => '1575912583',
                                'timemodified' => '1575912583',
                                'createdby' => '2',
                                'modifiedby' => '2',
                                'idnumber' => null,
                                'contextid' => '2005',
                                'options' =>
                                    (object)array(
                                        'id' => '1452',
                                        'questionid' => '8864',
                                        'layout' => '0',
                                        'single' => '1',
                                        'shuffleanswers' => '1',
                                        'correctfeedback' => '',
                                        'correctfeedbackformat' => '1',
                                        'partiallycorrectfeedback' => '',
                                        'partiallycorrectfeedbackformat' => '1',
                                        'incorrectfeedback' => '',
                                        'incorrectfeedbackformat' => '1',
                                        'answernumbering' => 'none',
                                        'shownumcorrect' => '1',
                                        'answers' =>
                                            array(
                                                28406 =>
                                                    (object)array(
                                                        'id' => '28406',
                                                        'question' => '8864',
                                                        'answer' => '<p>Barcelona</p>',
                                                        'answerformat' => '1',
                                                        'fraction' => '0.0000000',
                                                        'feedback' => '',
                                                        'feedbackformat' => '1',
                                                    ),
                                                28407 =>
                                                    (object)array(
                                                        'id' => '28407',
                                                        'question' => '8864',
                                                        'answer' => '<p>Madrid</p>',
                                                        'answerformat' => '1',
                                                        'fraction' => '1.0000000',
                                                        'feedback' => '',
                                                        'feedbackformat' => '1',
                                                    ),
                                                28408 =>
                                                    (object)array(
                                                        'id' => '28408',
                                                        'question' => '8864',
                                                        'answer' => '<p>Salamanca</p>',
                                                        'answerformat' => '1',
                                                        'fraction' => '0.0000000',
                                                        'feedback' => '',
                                                        'feedbackformat' => '1',
                                                    ),
                                            ),
                                    ),
                                'hints' =>
                                    array(),
                                'categoryobject' =>
                                    (object)array(
                                        'id' => '1299',
                                        'name' => 'qtype_svg-icons',
                                        'contextid' => '2005',
                                        'info' => '<p>qtype_svg-icons</p>',
                                        'infoformat' => '1',
                                        'stamp' => 'mk4359.vledev3.open.ac.uk+190909142034+Cun3Jr',
                                        'parent' => '1298',
                                        'sortorder' => '999',
                                        'idnumber' => null,
                                    ),
                            ),
                        8863 =>
                            (object)array(
                                'id' => '8863',
                                'category' => '1299',
                                'parent' => '8862',
                                'name' => '1',
                                'questiontext' => '',
                                'questiontextformat' => '0',
                                'generalfeedback' => '',
                                'generalfeedbackformat' => '1',
                                'defaultmark' => 0.5,
                                'penalty' => 0.3333333,
                                'qtype' => 'multichoice',
                                'length' => '1',
                                'stamp' => 'mk4359.vledev3.open.ac.uk+191209172943+GOVrt0',
                                'version' => 'mk4359.vledev3.open.ac.uk+191209172943+0cgrZv',
                                'hidden' => '0',
                                'timecreated' => '1575912583',
                                'timemodified' => '1575912583',
                                'createdby' => '2',
                                'modifiedby' => '2',
                                'idnumber' => null,
                                'contextid' => '2005',
                                'options' =>
                                    (object)array(
                                        'id' => '1451',
                                        'questionid' => '8863',
                                        'layout' => '0',
                                        'single' => '1',
                                        'shuffleanswers' => '1',
                                        'correctfeedback' => '',
                                        'correctfeedbackformat' => '1',
                                        'partiallycorrectfeedback' => '',
                                        'partiallycorrectfeedbackformat' => '1',
                                        'incorrectfeedback' => '',
                                        'incorrectfeedbackformat' => '1',
                                        'answernumbering' => 'none',
                                        'shownumcorrect' => '1',
                                        'answers' =>
                                            array(
                                                28403 =>
                                                    (object)array(
                                                        'id' => '28403',
                                                        'question' => '8863',
                                                        'answer' => '<p>Berlin</p>',
                                                        'answerformat' => '1',
                                                        'fraction' => '1.0000000',
                                                        'feedback' => '',
                                                        'feedbackformat' => '1',
                                                    ),
                                                28404 =>
                                                    (object)array(
                                                        'id' => '28404',
                                                        'question' => '8863',
                                                        'answer' => '<p>Bonn</p>',
                                                        'answerformat' => '1',
                                                        'fraction' => '0.2000000',
                                                        'feedback' => '',
                                                        'feedbackformat' => '1',
                                                    ),
                                                28405 =>
                                                    (object)array(
                                                        'id' => '28405',
                                                        'question' => '8863',
                                                        'answer' => '<p>Hamburg</p>',
                                                        'answerformat' => '1',
                                                        'fraction' => '0.0000000',
                                                        'feedback' => '',
                                                        'feedbackformat' => '1',
                                                    ),
                                            ),
                                    ),
                                'hints' =>
                                    array(),
                                'categoryobject' =>
                                    (object)array(
                                        'id' => '1299',
                                        'name' => 'qtype_svg-icons',
                                        'contextid' => '2005',
                                        'info' => '<p>qtype_svg-icons</p>',
                                        'infoformat' => '1',
                                        'stamp' => 'mk4359.vledev3.open.ac.uk+190909142034+Cun3Jr',
                                        'parent' => '1298',
                                        'sortorder' => '999',
                                        'idnumber' => null,
                                    ),
                            ),
                    ),
                'categoryobject' =>
                    (object)array(
                        'id' => '1299',
                        'name' => 'qtype_svg-icons',
                        'contextid' => '2005',
                        'info' => '<p>qtype_svg-icons</p>',
                        'infoformat' => '1',
                        'stamp' => 'mk4359.vledev3.open.ac.uk+190909142034+Cun3Jr',
                        'parent' => '1298',
                        'sortorder' => '999',
                        'idnumber' => null,
                    ),
                'coursetagobjects' =>
                    array(),
                'coursetags' =>
                    array(),
                'tagobjects' =>
                    array(),
                'tags' =>
                    array(),
                'formoptions' =>
                    (object)array(
                        'canedit' => true,
                        'canmove' => true,
                        'cansaveasnew' => true,
                        'repeatelements' => true,
                        'mustbeusable' => false,
                    ),
                'contextid' => '2005',
                'scrollpos' => 0,
                'categorymoveto' => '1299,2005',
                'appendqnumstring' => '',
                'returnurl' => '/question/edit.php?courseid=35&cat=1299%2C2005&recurse=1&showhidden=1&qbshowtext=0',
                'makecopy' => 0,
                'courseid' => '35',
                'inpopup' => 0
            );

        $exporter = new qformat_xml();
        $xml = $exporter->writequestion($qdata);
        $expectedxml =
            '<!-- question: 8862  -->
  <question type="combined">
    <name>
      <text>combined with singlechoice</text>
    </name>
    <questiontext format="html">
      <text><![CDATA[<p><span style="text-align: inherit;">Select the capital cities.</span></p>Germany:'
            . '[[1:singlechoice:v]]<br>Spain: [[2:singlechoice:v]]<br><br><p></p>]]></text>
    </questiontext>
    <generalfeedback format="html">
      <text></text>
    </generalfeedback>
    <defaultgrade>1</defaultgrade>
    <penalty>0.3333333</penalty>
    <hidden>0</hidden>
    <idnumber></idnumber>
    <correctfeedback format="html">
      <text>Your answer is correct.</text>
    </correctfeedback>
    <partiallycorrectfeedback format="html">
      <text>Your answer is partially correct.</text>
    </partiallycorrectfeedback>
    <incorrectfeedback format="html">
      <text>Your answer is incorrect.</text>
    </incorrectfeedback>
    <shownumcorrect/>
<subquestions>
<!-- question: 8864  -->
  <question type="multichoice">
    <name>
      <text>2</text>
    </name>
    <questiontext format="moodle_auto_format">
      <text></text>
    </questiontext>
    <generalfeedback format="html">
      <text></text>
    </generalfeedback>
    <defaultgrade>0.5</defaultgrade>
    <penalty>0.3333333</penalty>
    <hidden>0</hidden>
    <idnumber></idnumber>
    <single>true</single>
    <shuffleanswers>true</shuffleanswers>
    <answernumbering>none</answernumbering>
    <correctfeedback format="html">
      <text></text>
    </correctfeedback>
    <partiallycorrectfeedback format="html">
      <text></text>
    </partiallycorrectfeedback>
    <incorrectfeedback format="html">
      <text></text>
    </incorrectfeedback>
    <shownumcorrect/>
    <answer fraction="0" format="html">
      <text><![CDATA[<p>Barcelona</p>]]></text>
      <feedback format="html">
        <text></text>
      </feedback>
    </answer>
    <answer fraction="100" format="html">
      <text><![CDATA[<p>Madrid</p>]]></text>
      <feedback format="html">
        <text></text>
      </feedback>
    </answer>
    <answer fraction="0" format="html">
      <text><![CDATA[<p>Salamanca</p>]]></text>
      <feedback format="html">
        <text></text>
      </feedback>
    </answer>
  </question>
<!-- question: 8863  -->
  <question type="multichoice">
    <name>
      <text>1</text>
    </name>
    <questiontext format="moodle_auto_format">
      <text></text>
    </questiontext>
    <generalfeedback format="html">
      <text></text>
    </generalfeedback>
    <defaultgrade>0.5</defaultgrade>
    <penalty>0.3333333</penalty>
    <hidden>0</hidden>
    <idnumber></idnumber>
    <single>true</single>
    <shuffleanswers>true</shuffleanswers>
    <answernumbering>none</answernumbering>
    <correctfeedback format="html">
      <text></text>
    </correctfeedback>
    <partiallycorrectfeedback format="html">
      <text></text>
    </partiallycorrectfeedback>
    <incorrectfeedback format="html">
      <text></text>
    </incorrectfeedback>
    <shownumcorrect/>
    <answer fraction="100" format="html">
      <text><![CDATA[<p>Berlin</p>]]></text>
      <feedback format="html">
        <text></text>
      </feedback>
    </answer>
    <answer fraction="20" format="html">
      <text><![CDATA[<p>Bonn</p>]]></text>
      <feedback format="html">
        <text></text>
      </feedback>
    </answer>
    <answer fraction="0" format="html">
      <text><![CDATA[<p>Hamburg</p>]]></text>
      <feedback format="html">
        <text></text>
      </feedback>
    </answer>
  </question>
</subquestions>
  </question>
';

        // Hack so the test passes in both 3.5 and 3.6.
        if (strpos($xml, 'idnumber') === false) {
            $expectedxml = str_replace("    <idnumber></idnumber>\n", '', $expectedxml);
        }

        $this->assertEquals($expectedxml, $xml);
    }
}
