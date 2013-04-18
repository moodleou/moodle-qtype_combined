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
 * Base classes for 'combinable' classes that are used to make another question type to work as an
 * embeddable sub question in this question type.
 *
 * @package    qtype
 * @subpackage combined
 * @copyright  2013 The Open University
 * @author     James Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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

    protected $qtypename;

    public function __construct($qtypename, $foundwhere) {
        $this->qtypename = $qtypename;
        $this->foundwhere = $foundwhere;
    }

    /**
     * @return qtype_combined_embedded_renderer_base
     */
    public function embedded_renderer() {
        global $PAGE, $CFG;
        if ($this->foundwhere === qtype_combined_type_manager::FOUND_IN_COMBINABLE_DIR_OF_COMBINED) {
            require_once($CFG->dirroot."/question/type/combined/combinable/{$this->qtypename}/renderer.php");
            return $PAGE->get_renderer('qtype_combined', $this->qtypename.'_embedded');
        } else {
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
     * @return string question type identifier used in question text that can be different to question type name.
     */
    public function get_identifier() {
        return $this->identifier;
    }

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

    abstract protected function extra_question_properties();

    abstract protected function extra_answer_properties();

    protected function add_per_answer_properties($questiondata) {
        foreach (array_keys($questiondata->answer) as $answerkey) {
            foreach ($this->extra_answer_properties() as $prop => $value) {
                $questiondata->{$prop}[$answerkey] = $value;
            }
        }
        return $questiondata;
    }

    /**
     * Default just adds defaults from default_question_properties but this might be extended in
     * child class.
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
     * @return question_type for this subq type
     */
    protected function get_qtype_obj() {
        return question_bank::get_qtype($this->get_qtype_name(), true);
    }

    /**
     * @param $questiondata stdClass question record object to add extra options to.
     */
    public function get_question_options($questiondata) {
        $this->get_qtype_obj()->get_question_options($questiondata);
    }

    public function delete_question($questionid, $contextid) {
        global $DB;
        $DB->delete_records('question', array('id' => $questionid));
        $this->get_qtype_obj()->delete_question($questionid, $contextid);
    }

    public function save($oldsubq, $subqdata) {
        if ($oldsubq === null) {
            $oldsubq = new stdClass();
        }
        $oldsubq->qtype = $this->get_qtype_name();
        $subqdata = $this->transform_subq_form_data_to_full($subqdata);
        $this->get_qtype_obj()->save_question($oldsubq, $subqdata);
    }

    /**
     * @return array keys are field names of extra question fields in subq
     *               form values are how to test for default null means don't check, true means not empty, false means empty.
     */
    public function get_question_option_fields() {
        return array();
    }

}

/**
 * Class qtype_combined_combinable_base
 * Defines a sub question instance.
 */
abstract class qtype_combined_combinable_base {

    /**
     * @var bool whether found in question text.
     */
    protected $foundinquestiontext = false;

    protected $questionrec = null;

    /**
     * @var question_graded_automatically the subq question itself.
     */
    public $question = null;


    /**
     * @var string question identifier found in question text for this instance
     */
    protected $questionidentifier;

    /**
     * @var object form data from form fragment for this sub question
     */
    protected $formdata = null;

    /**
     * @var qtype_combined_combinable_type_base
     */
    public $type;

    public function __construct($type, $questionidentifier) {
        $this->type = $type;
        $this->questionidentifier = $questionidentifier;
    }

    /**
     * @return bool Can there be more of one 'heads' of this question with same identifier in question text.
     */
    public function can_be_more_than_one_of_same_instance() {
        return false;
    }

    protected function field_name_prefix() {
        $prefix = str_replace('{qid}', $this->questionidentifier, qtype_combined_combiner_base::FIELD_NAME_PREFIX);
        return str_replace('{qtype}', $this->type->get_identifier(), $prefix);
    }

    public function field_name($elementname) {
        return $this->field_name_prefix().$elementname;
    }

    /**
     * Get the question_attempt_step_subquestion_adapter for this subq. Allows access to the step data for sub-question.
     * @param question_attempt_step $step the step to adapt.
     * @return question_attempt_step_subquestion_adapter.
     */
    public function get_substep($step) {
        return new question_attempt_step_subquestion_adapter($step, $this->field_name_prefix());
    }

    /**
     * @param moodleform      $combinedform
     * @param MoodleQuickForm $mform
     * @param                 $repeatenabled
     */
    abstract public function add_form_fragment(moodleform $combinedform, MoodleQuickForm $mform,
                                               $repeatenabled);


    protected function editor_data_to_form($component, $fieldname, $object, $context, $fileoptions) {
        if ($object !== null) {
            $subquestionid = $this->questionrec->id;
            $text = $object->{$fieldname};
            $format = $object->{"{$fieldname}format"};
        } else {
            $subquestionid = null;
            $text = '';
            $format = editors_get_preferred_format();
        }
        $editorfieldname = $this->field_name($fieldname);
        $draftid = file_get_submitted_draft_itemid($editorfieldname);

        $text = file_prepare_draft_area($draftid, $context, $component, $fieldname, $subquestionid, $fileoptions, $text);

        return array($fieldname => array('text' =>  $text,
                                         'format' => $format,
                                         'itemid' => $draftid));
    }

    /**
     * @param $context
     * @param $fileoptions
     * @return array data to go in form from db with field name as array key not yet with additional question instance prefix.
     */
    public function data_to_form($context, $fileoptions) {
        $generalfb =$this->editor_data_to_form('question', 'generalfeedback', $this->questionrec, $context->id, $fileoptions);

        if ($this->questionrec === null) {
            return $generalfb;
        } else {
            $subqoptions = array();
            foreach (array_keys($this->type->get_question_option_fields()) as $fieldname) {
                $subqoptions[$fieldname] = $this->questionrec->options->$fieldname;
            }
            return array('defaultmark' => $this->questionrec->defaultmark) + $generalfb + $subqoptions;
        }
    }

    protected function get_string_hash() {
        $getstringhash = new stdClass();
        $getstringhash->qtype = $this->type->get_identifier();
        $getstringhash->qid = $this->get_identifier();
        return $getstringhash;
    }

    public function found_in_question_text($thirdparam) {
        if ($this->foundinquestiontext && !$this->can_be_more_than_one_of_same_instance()) {
            $getstringhash = $this->get_string_hash();
            return get_string('err_thisqtypecannothavemorethanonecontrol', 'qtype_combined', $getstringhash);
        }
        $this->foundinquestiontext = true;
        return $this->process_third_param($thirdparam);
    }

    /**
     * @param $thirdparam string third param from code in question text for this embedded question.
     * @return null|string null if no error or error string to display in form.
     */
    protected function process_third_param($thirdparam) {
        if ($thirdparam !== null) {
            $qtypename = $this->type->get_identifier();
            return get_string('err_thisqtypedoesnotacceptextrainfo', 'qtype_combined', $qtypename);
        }
        return null;
    }

    /**
     * @return array empty or containing errors with field name keys.
     */
    abstract public function validate();

    public function get_this_form_data_from($allformdata) {
        $this->formdata = new stdClass();
        foreach ($allformdata as $key => $value) {
            if (strpos($key, $this->field_name_prefix()) === 0) {
                $afterprefix = substr($key, strlen($this->field_name_prefix()));
                $this->formdata->$afterprefix = $value;
            }
        }
        // Stuff to copy from parent question.
        foreach (array('parent' => 'id', 'category' => 'category', 'penalty' => 'penalty') as $thisprop => $parentprop) {
            $this->formdata->$thisprop = $allformdata->{$parentprop};
        }

    }

    public function is_in_question_text() {
        return $this->foundinquestiontext;
    }
    public function is_in_db() {
        return $this->questionrec !== null;
    }
    public function is_in_form() {
        return $this->formdata !== null;
    }
    public function get_identifier() {
        return $this->questionidentifier;
    }

    public function found_in_db($questionrec) {
        $this->questionrec = $questionrec;
    }

    public function preserve_submitted_data() {
        return ($this->has_submitted_data()
            && !optional_param($this->field_name('notincludedinquestiontextwilldelete'), false, PARAM_BOOL));
    }

    /**
     * Overridden by child classes, but they should also call this parent class.
     * @return bool Has the user entered data in this sub question form fragment?
     */
    public function has_submitted_data() {
        if ($this->question_option_fields_have_submitted_data()) {
            return true;
        } else if ($this->html_field_has_submitted_data('generalfeedback')) {
            return true;
        } else {
            return false;
        }
    }

    protected function html_field_has_submitted_data($fieldname) {
        $htmlfielddata = optional_param_array($this->field_name($fieldname), array(), PARAM_RAW_TRIMMED);
        return isset($htmlfielddata['text']) && !html_is_blank($htmlfielddata['text']);
    }


    protected function question_option_fields_have_submitted_data() {
        foreach ($this->type->get_question_option_fields() as $fieldname => $default) {
            if ($default === false) { // Default is empty.
                if (optional_param($this->field_name($fieldname), false, PARAM_BOOL)) {
                    // Has data if true.
                    return true;
                }
            } else if ($default === true) { // Default is not empty.
                if (!optional_param($this->field_name($fieldname), true, PARAM_BOOL)) {
                    // Has data if false.
                    return true;
                }
            }
        }
        return false;
    }

    protected function text_array_has_submitted_data($fieldname) {
        foreach (optional_param_array($this->field_name($fieldname), array(), PARAM_RAW_TRIMMED) as $value) {
            if ('' !== $value) {
                return true;
            }
        }
        return false;
    }

    public function save($contextid) {
        $this->formdata->name = $this->get_identifier();
        $this->type->save($this->questionrec, $this->formdata);
    }

    public function delete() {
        $this->type->delete_question($this->questionrec->id, $this->questionrec->contextid);
    }

    abstract protected function code_construction_instructions();

    public function message_in_form_if_not_included_in_question_text() {
        $a = $this->code_construction_instructions();
        return get_string('err_subq_not_included_in_question_text', 'qtype_combined', $a);
    }


    public function make() {
        $this->question = question_bank::make_question($this->questionrec);
    }

    public function question_text_embed_codes() {
        $codes = array();
        foreach ($this->get_third_params() as $place => $thirdparam) {
            $params = array($this->get_identifier(), $this->type->get_identifier());
            if ($thirdparam !== null) {
                $params[] = $thirdparam;
            }
            $code = join(qtype_combined_combiner_base::EMBEDDED_CODE_SEPARATOR, $params);
            $codes[$place] = qtype_combined_combiner_base::EMBEDDED_CODE_PREFIX.$code.
                            qtype_combined_combiner_base::EMBEDDED_CODE_POSTFIX;
        }
        return $codes;
    }

    abstract protected function get_third_params();
}


abstract class qtype_combined_combinable_accepts_third_param_validated_with_pattern
    extends qtype_combined_combinable_base {

    const THIRD_PARAM_PATTERN = '!undefined!';

    protected function process_third_param($thirdparam) {
        $error = $this->validate_third_param($thirdparam);
        if (null !== $error) {
            return $error;
        } else {
            $this->store_third_param($thirdparam);
            return null;
        }
    }

    abstract protected function store_third_param($thirdparam);

    abstract protected function error_string_when_third_param_fails_validation($thirdparam);

    /**
     * Validation for the extra info after second colon, if any.
     * @param $thirdparam string|null the extra info found in square brackets -  anything after second colon
     * @return array empty if no error or any array of errors to display in the form if there are errors.
     */
    public function validate_third_param($thirdparam) {
        if ($thirdparam === null) {
            return null;
        }
        if (1 !== preg_match(static::THIRD_PARAM_PATTERN, $thirdparam)) {
            return $this->error_string_when_third_param_fails_validation($thirdparam);
        } else {
            return null;
        }
    }
}

abstract class qtype_combined_combinable_text_entry
    extends qtype_combined_combinable_accepts_third_param_validated_with_pattern {

    /**
     * @var string|null the string after second colon in embedded code if there is one.
     */
    protected $widthparam = null;

    const THIRD_PARAM_PATTERN = '!_+[0-9]*_+$!A';

    protected function error_string_when_third_param_fails_validation($thirdparam) {
        $qtypeid = $this->type->get_identifier();
        return get_string('err_invalid_width_specifier_postfix', 'qtype_combined', $qtypeid);
    }

    protected function code_construction_instructions() {
        $a = $this->get_string_hash();
        return get_string('widthspecifier_embed_code', 'qtype_combined', $a);
    }

    protected function store_third_param($thirdparam) {
        $this->widthparam = $thirdparam;
    }

    protected function get_third_params() {
        return array($this->widthparam);
    }

    public function get_width() {
        $matches = array();
        if (null === $this->widthparam) {
            return 20;
        } else if (1 === preg_match('![0-9]*!', $this->widthparam, $matches)) {
            $length = $matches[0];
        } else {
            $length = strlen($this->widthparam);
        }
        return round($length * 1.1);
    }

    /**
     * @return string|null return either sup, sub, both, or null for no editor.
     */
    abstract public function get_sup_sub_editor_option();
}
abstract class qtype_combined_combinable_accepts_vertical_or_horizontal_layout_param
    extends qtype_combined_combinable_accepts_third_param_validated_with_pattern {

    /**
     * @var string|null the string after second colon in embedded code if there is one.
     */
    protected $layoutparam = null;

    const THIRD_PARAM_PATTERN = '![vh]$!A';

    protected function error_string_when_third_param_fails_validation($thirdparam) {
        $qtypeid = $this->type->get_identifier();
        return get_string('err_accepts_vertical_or_horizontal_layout_param', 'qtype_combined', $qtypeid);
    }

    protected function code_construction_instructions() {
        $a = $this->get_string_hash();
        return get_string('vertical_or_horizontal_embed_code', 'qtype_combined', $a);
    }

    protected function store_third_param($thirdparam) {
        $this->layoutparam = $thirdparam;
    }

    public function get_layout() {
        return $this->layoutparam;
    }

    protected function get_third_params() {
        return array($this->layoutparam);
    }
}


abstract class qtype_combined_combinable_accepts_numerical_param
    extends qtype_combined_combinable_accepts_third_param_validated_with_pattern {
    const THIRD_PARAM_PATTERN = '![0-9]+$!A';

    protected function error_string_when_third_param_fails_validation($thirdparam) {
        $qtypeid = $this->type->get_identifier();
        return get_string('err_invalid_number', 'qtype_combined', $qtypeid);
    }
}