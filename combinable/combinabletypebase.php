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
 * Base class for 'combinable' type class. This contains methods for a question type rather than an instance of
 * a question. The base classes for the instance are in combinablebase.php.
 *
 * @package    qtype_combined
 * @copyright  2013 The Open University
 * @author     James Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Class qtype_combined_combinable_type_base
 * Collects methods common to a sub question type.
 */
abstract class qtype_combined_combinable_type_base {

    /**
     * @var null|string string used to identify this question type, in question type syntax after first colon
     * should be assigned a value in child class.
     */
    protected $identifier = null;

    /**
     * @var string this is the internal Moodle question type name.
     */
    protected $qtypename;

    /**
     * @var int either @link qtype_combined_type_manager::FOUND_IN_COMBINABLE_DIR_OF_COMBINED
     *          or @link qtype_combined_type_manager::FOUND_IN_OTHER_QTYPE_DIR
     */
    protected $foundwhere;

    /**
     * @param string $qtypename this is the internal Moodle question type name
     * @param integer $foundwhere
     * @see qtype_combined_type_manager::FOUND_IN_COMBINABLE_DIR_OF_COMBINED
     * @see qtype_combined_type_manager::FOUND_IN_OTHER_QTYPE_DIR
     */
    public function __construct($qtypename, $foundwhere) {
        $this->qtypename = $qtypename;
        $this->foundwhere = $foundwhere;
    }

    /**
     * @return qtype_renderer
     */
    public function embedded_renderer() {
        global $PAGE, $CFG;
        if ($this->foundwhere === qtype_combined_type_manager::FOUND_IN_COMBINABLE_DIR_OF_COMBINED) {
            require_once($CFG->dirroot."/question/type/combined/combinable/{$this->qtypename}/renderer.php");
            return $PAGE->get_renderer('qtype_combined', $this->qtypename.'_embedded');
        } else {
            require_once($CFG->dirroot."/question/type/{$this->qtypename}/combinable/renderer.php");
            return $PAGE->get_renderer('qtype_'.$this->qtypename, 'embedded');
        }
    }

    /**
     * @param $questionidentifier string the question identifier found in the question text or otherwise
     * @return qtype_combined_combinable_base
     */
    public function new_subq_instance($questionidentifier) {
        $classname = 'qtype_combined_combinable_'.$this->qtypename;
        return new $classname($this, $questionidentifier);
    }

    /**
     * Get a student readable description of the part of the sub question to be used in validation message.
     * The name of the thing embedded in the text that the student sees, they should need to have no knowledge of the underlying
     * sub question type to understand the message eg. numeric input, text input, drop down boxes.
     * There is no need to define a plural lang string if the sub question cannot have more than one control in the question.
     * @param bool $plural
     * @return string the name of the control or if plural is true the controls.
     */
    public function get_control_name($plural) {
        if ($this->foundwhere === qtype_combined_type_manager::FOUND_IN_COMBINABLE_DIR_OF_COMBINED) {
            $langfile = 'qtype_combined';
            $stringid = 'controlname'.$this->qtypename;
        } else {
            $langfile = 'qtype_'.$this->qtypename;
            $stringid = 'combinedcontrolname'.$this->qtypename;
        }
        if ($plural) {
            $stringid = $stringid.'plural';
        }
        return get_string($stringid, $langfile);
    }

    /**
     * @return string question type identifier used in question text that can be different to internal Moodle question type name.
     */
    public function get_identifier() {
        return $this->identifier;
    }

    /**
     * @param bool $withparts
     * @return array
     */
    protected function combined_feedback_properties($withparts = true) {
        $properties = array();
        foreach (array('correct', 'partiallycorrect', 'incorrect') as $feedbacktype) {
            $properties[$feedbacktype.'feedback'] = array('text' => '', 'format' => FORMAT_HTML);
        }
        if ($withparts) {
            $properties['shownumcorrect'] = 1;
        }
        return $properties;
    }

    /**
     * Extra properties to add to data from subq form fragment before passing the data through to the save_question method for this
     * question type. Needed as question_type save_question method is expecting full data from full question form.
     * @return array with keys being form field names and values being value to pass.
     */
    abstract protected function extra_question_properties();

    /**
     * Extra per answer properties to add to add to each answer's data from subq form fragment before passing the data through to
     * the  save_question method for this question type.
     * @return array with keys being form field names and values being value to pass.
     */
    abstract protected function extra_answer_properties();

    /**
     * Add properties to each answer.
     * @param stdClass $questiondata
     * @return stdClass
     */
    protected function add_per_answer_properties($questiondata) {
        foreach (array_keys($questiondata->answer) as $answerkey) {
            foreach ($this->extra_answer_properties() as $prop => $value) {
                $questiondata->{$prop}[$answerkey] = $value;
            }
        }
        return $questiondata;
    }

    /**
     * Default just adds defaults from extra_question_properties but this might be extended in
     * child class if we also need to do something more complex.
     *
     * @param $questiondata
     * @return object transformed question data to be passed to qtype save_question method.
     */
    protected function add_question_properties($questiondata) {
        foreach ($this->extra_question_properties() as $propname => $value) {
            $questiondata->$propname = $value;
        }
        return $questiondata;
    }

    /**
     * Do the full transform to convert data from sub q form fragment to something that will be accepted by
     * question type save_question method.
     * @param stdClass $subqdata
     * @return stdClass fleshed out subq data as if from the full question form. Field name prefixes not added yet.
     */
    protected function transform_subq_form_data_to_full($subqdata) {
        $data = $this->add_question_properties($subqdata);
        return $this->add_per_answer_properties($data);
    }

    /**
     * @return string question type name as per directory name in question/type/
     */
    public function get_qtype_name() {
        return $this->qtypename;
    }

    /**
     * @return question_type object for this subq type
     */
    protected function get_qtype_obj() {
        return question_bank::get_qtype($this->get_qtype_name(), true);
    }

    /**
     * @param $questiondata stdClass question record object. Options are added to $questiondata->options
     */
    public function get_question_options($questiondata) {
        $this->get_qtype_obj()->get_question_options($questiondata);
    }

    /**
     * @param integer $subquestionid
     * @param integer $contextid
     */
    public function delete_question($subquestionid, $contextid) {
        global $DB;
        $DB->delete_records('question', array('id' => $subquestionid));
        $this->get_qtype_obj()->delete_question($subquestionid, $contextid);
    }

    /**
     * @param stdClass $oldsubq
     * @param stdClass $subqdata
     */
    public function save($oldsubq, $subqdata) {
        if ($oldsubq === null) {
            $oldsubq = new stdClass();
        }
        $oldsubq->qtype = $this->get_qtype_name();
        $subqdata = $this->transform_subq_form_data_to_full($subqdata);
        $this->get_qtype_obj()->save_question($oldsubq, $subqdata);
    }

    /**
     * Information about what values to expect from subq form fragment and how to tell if form fragment is empty.
     * @return array keys are field names of extra question fields in subq form,
     * values are how to test if the field is empty,
     * null value means don't check,
     * true value means default not empty,
     * false value means default empty.
     */
    public function subq_form_fragment_question_option_fields() {
        return array();
    }

    public function embedded_code_for_default_question_text($questionname) {
        $prefix = qtype_combined_combiner_base::EMBEDDED_CODE_PREFIX;
        $postfix = qtype_combined_combiner_base::EMBEDDED_CODE_POSTFIX;
        $separator = qtype_combined_combiner_base::EMBEDDED_CODE_SEPARATOR;
        $parts = array($questionname, $this->identifier);
        $thirdparam = $this->third_param_for_default_question_text();
        if (!is_null($thirdparam)) {
            $parts[] = $thirdparam;
        }
        return $prefix.join($separator, $parts).$postfix;
    }

    /**
     * Override this method in your child class in order to include a third param in
     * the embedded code in the default question text.
     * @return null|string
     */
    protected function third_param_for_default_question_text() {
        return null;
    }

}
