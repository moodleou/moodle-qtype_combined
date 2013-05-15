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
 * Base classes for 'combinable' class that is used to make instances of another question type 'combinable' ie. to work as an
 * embeddable sub question in this 'combined' question type.
 *
 * Classes, any of which you might override to make your question type combinable :
 *
 *  - qtype_combined_combinable_base highest level base class.
 *  -- qtype_combined_combinable_accepts_third_param_validated_with_pattern
 *  --- qtype_combined_combinable_text_entry a question with a single text box control field to collect student response.
 *  --- qtype_combined_combinable_accepts_vertical_or_horizontal_layout_param
 *  --- qtype_combined_combinable_accepts_numerical_param third param is a number
 *
 * @package    qtype_combined
 * @copyright  2013 The Open University
 * @author     James Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class qtype_combined_combinable_base
 * Defines a sub question instance.
 */
abstract class qtype_combined_combinable_base {

    /**
     * @var bool whether found in question text.
     */
    protected $foundinquestiontext = false;

    /**
     * @var null|stdClass loaded from the db, might have property options with question options loaded into it.
     */
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

    /**
     * @var array the control nos that this sub question is responsible for. Controls are numbered from 1 onwards in the order they
     *              are found in the question text. Many questions will only have one control embedded in text but some can have
     *              more than one, eg. gapselect.
     */
    protected $controlnos;

    /**
     * @param qtype_combined_combinable_type_base $type
     * @param string $questionidentifier from question text
     */
    public function __construct($type, $questionidentifier) {
        $this->type = $type;
        $this->questionidentifier = $questionidentifier;
    }

    /**
     * Normally set to false, set it to true if there can be more than one control embedded in the question text for this sub
     * question type.
     * @return bool Can there be more of one 'heads' of this question with same identifier in question text.
     */
    public function can_be_more_than_one_of_same_instance() {
        return false;
    }

    /**
     * @return string field name prefix used in forms
     */
    protected function form_field_name_prefix() {
        $prefix = str_replace('{qid}', $this->questionidentifier, qtype_combined_combiner_base::FIELD_NAME_PREFIX);
        return str_replace('{qtype}', $this->type->get_identifier(), $prefix);
    }

    /**
     * @param string $elementname field name
     * @return string field name with prefix unique to this subq used in form
     */
    public function form_field_name($elementname) {
        return $this->form_field_name_prefix().$elementname;
    }


    /**
     * @return string used in question response array and qt_vars.
     */
    protected function step_data_name_prefix() {
        return $this->questionidentifier.':';
    }

    /**
     * @param string $elementname response data key or qt_var name.
     * @return string step data name with prefix unique to this subq used in question response array and qt_vars.
     */
    public function step_data_name($elementname) {
        return $this->step_data_name_prefix().$elementname;
    }

    /**
     * Get the question_attempt_step_subquestion_adapter for this subq. Allows access to the step data for sub-question.
     * @param question_attempt_step $step the step to adapt.
     * @return question_attempt_step_subquestion_adapter.
     */
    public function get_substep($step) {
        return new question_attempt_step_subquestion_adapter($step, $this->step_data_name_prefix());
    }

    /**
     * @param moodleform      $combinedform
     * @param MoodleQuickForm $mform
     * @param bool            $repeatenabled
     */
    abstract public function add_form_fragment(moodleform $combinedform, MoodleQuickForm $mform, $repeatenabled);

    /**
     * Prepare editor data for form.
     * @param string $component
     * @param string $fieldname
     * @param stdClass $object
     * @param context $context
     * @param array $fileoptions text and file options ('subdirs'=>false, 'forcehttps'=>false)
     * @return array data for form
     */
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
        $editorfieldname = $this->form_field_name($fieldname);
        $draftid = file_get_submitted_draft_itemid($editorfieldname);

        $text = file_prepare_draft_area($draftid, $context, $component, $fieldname, $subquestionid, $fileoptions, $text);

        return array($fieldname => array('text' =>  $text,
                                         'format' => $format,
                                         'itemid' => $draftid));
    }

    /**
     * Prepare data to populate form.
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
            foreach (array_keys($this->type->subq_form_fragment_question_option_fields()) as $fieldname) {
                $subqoptions[$fieldname] = $this->questionrec->options->$fieldname;
            }
            return array('defaultmark' => $this->questionrec->defaultmark) + $generalfb + $subqoptions;
        }
    }

    /**
     * Hash to pass to get_string
     * @return stdClass
     */
    protected function get_string_hash() {
        $getstringhash = new stdClass();
        $getstringhash->qtype = $this->type->get_identifier();
        $getstringhash->qid = $this->get_identifier();
        return $getstringhash;
    }

    /**
     * This sub question has been found in question text. Store third param, third param is null if no third param.
     * @param $thirdparam null|mixed the third param in the embedded code, null if only two params in embedded code.
     * @param $controlno integer the control no, each subq can be responsible for more than one control in the question text.
     * @return null|string null if OK, string returned if there is an error.
     */
    public function found_in_question_text($thirdparam, $controlno) {
        if ($this->foundinquestiontext && !$this->can_be_more_than_one_of_same_instance()) {
            $getstringhash = $this->get_string_hash();
            return get_string('err_thisqtypecannothavemorethanonecontrol', 'qtype_combined', $getstringhash);
        }
        $this->foundinquestiontext = true;
        $this->store_control_no($controlno);
        return $this->process_third_param($thirdparam);
    }

    protected function store_control_no($controlno) {
        $this->controlnos[] = $controlno;
    }

    /**
     * @return array control nos.
     */
    public function get_control_nos() {
        return $this->controlnos;
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

    /**
     * Extracts the data for this sub question from the full form data.
     * @param stdClass $allformdata
     */
    public function get_this_form_data_from($allformdata) {
        $this->formdata = new stdClass();
        foreach ($allformdata as $key => $value) {
            if (strpos($key, $this->form_field_name_prefix()) === 0) {
                $afterprefix = substr($key, strlen($this->form_field_name_prefix()));
                $this->formdata->$afterprefix = $value;
            }
        }
        // Stuff to copy from parent question.
        foreach (array('parent' => 'id', 'category' => 'category', 'penalty' => 'penalty') as $thisprop => $parentprop) {
            $this->formdata->$thisprop = $allformdata->{$parentprop};
        }

    }

    /**
     * @return bool has this sub question been found in question text.
     */
    public function is_in_question_text() {
        return $this->foundinquestiontext;
    }

    /**
     * @return bool has it been loaded from db.
     */
    public function is_in_db() {
        return $this->questionrec !== null;
    }

    /**
     * @return bool has form data been found in form.
     */
    public function is_in_form() {
        return $this->formdata !== null;
    }

    /**
     * @return string
     */
    public function get_identifier() {
        return $this->questionidentifier;
    }

    /**
     * @param $questionrec
     */
    public function found_in_db($questionrec) {
        $this->questionrec = $questionrec;
    }

    /**
     * @return bool Should form fragment for this subq be redisplayed to prevent data loss.
     */
    public function preserve_submitted_data() {
        return ($this->has_submitted_data()
            && !optional_param($this->form_field_name('notincludedinquestiontextwilldelete'), false, PARAM_BOOL));
    }

    /**
     * Overridden by child classes, but they should also call this parent class.
     * @return bool Has the user entered data in this sub question form fragment?
     */
    public function has_submitted_data() {
        if ($this->has_submitted_question_option_data()) {
            return true;
        } else if ($this->html_field_has_submitted_data('generalfeedback')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $fieldname
     * @return bool
     */
    protected function html_field_has_submitted_data($fieldname) {
        $htmlfielddata = optional_param_array($this->form_field_name($fieldname), array(), PARAM_RAW_TRIMMED);
        return isset($htmlfielddata['text']) && !html_is_blank($htmlfielddata['text']);
    }

    /**
     * @return bool
     */
    protected function has_submitted_question_option_data() {
        foreach ($this->type->subq_form_fragment_question_option_fields() as $fieldname => $default) {
            if ($default === false) { // Default is empty.
                if (optional_param($this->form_field_name($fieldname), false, PARAM_BOOL)) {
                    // Has data if true.
                    return true;
                }
            } else if ($default === true) { // Default is not empty.
                if (!optional_param($this->form_field_name($fieldname), true, PARAM_BOOL)) {
                    // Has data if false.
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param $fieldname
     * @return bool is the submitted data in array with index $fieldname for this subq empty?
     */
    protected function submitted_data_array_not_empty($fieldname) {
        foreach (optional_param_array($this->form_field_name($fieldname), array(), PARAM_RAW_TRIMMED) as $value) {
            if (!empty($value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Save question data.
     * @param $contextid
     */
    public function save($contextid) {
        $this->formdata->name = $this->get_identifier();
        $this->type->save($this->questionrec, $this->formdata);
    }

    public function delete() {
        $this->type->delete_question($this->questionrec->id, $this->questionrec->contextid);
    }

    /**
     * @return string human readable instructions to be used in validation error strings in from to tell user how to construct
     * embed code.
     */
    abstract protected function code_construction_instructions();

    /**
     * @return string
     */
    public function message_in_form_if_not_included_in_question_text() {
        $a = $this->code_construction_instructions();
        return get_string('err_subq_not_included_in_question_text', 'qtype_combined', $a);
    }

    /**
     * Instantiate the question_definition class for run time question.
     */
    public function make() {
        $this->question = question_bank::make_question($this->questionrec);
    }

    /**
     * @return array one or more embed codes to replace in question text. Key $place is passed through to renderer to know which
     *                  embedded control to render.
     */
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

    /**
     * @return array The third params found in question text. One control is rendered for each value in this array. Key $place is
     * passed through  to renderer to know which embedded control to render.
     */
    abstract protected function get_third_params();

    public function get_id() {
        return $this->questionrec->id;
    }
}

/**
 * Class qtype_combined_combinable_accepts_third_param_validated_with_pattern
 */
abstract class qtype_combined_combinable_accepts_third_param_validated_with_pattern
    extends qtype_combined_combinable_base {

    /** Needs to be overridden in child class. */
    const THIRD_PARAM_PATTERN = '!undefined!';

    /**
     * @param string $thirdparam
     * @return string|null string if there is an error or null.
     */
    protected function process_third_param($thirdparam) {
        $error = $this->validate_third_param($thirdparam);
        if (null !== $error) {
            return $error;
        } else {
            $this->store_third_param($thirdparam);
            return null;
        }
    }

    /**
     * @param $thirdparam
     */
    abstract protected function store_third_param($thirdparam);

    /**
     * @param $thirdparam
     * @return string
     */
    abstract protected function error_string_when_third_param_fails_validation($thirdparam);

    /**
     * Validation for the extra info after second colon, if any.
     * @param $thirdparam string|null the extra info found in square brackets -  anything after second colon
     * @return string|null null if no error or any array of errors to display in the form if there are errors.
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

/**
 * Class qtype_combined_combinable_text_entry
 */
abstract class qtype_combined_combinable_text_entry
    extends qtype_combined_combinable_accepts_third_param_validated_with_pattern {

    /**
     * @var string|null the string after second colon in embedded code if there is one.
     */
    protected $widthparam = null;

    const THIRD_PARAM_PATTERN = '!_+[0-9]*_+$!A';

    /**
     * @param $thirdparam
     * @return string
     */
    protected function error_string_when_third_param_fails_validation($thirdparam) {
        $qtypeid = $this->type->get_identifier();
        return get_string('err_invalid_width_specifier_postfix', 'qtype_combined', $qtypeid);
    }

    /**
     * @return string
     */
    protected function code_construction_instructions() {
        $a = $this->get_string_hash();
        return get_string('widthspecifier_embed_code', 'qtype_combined', $a);
    }

    /**
     * @param string $thirdparam
     */
    protected function store_third_param($thirdparam) {
        $this->widthparam = $thirdparam;
    }

    /**
     * @return array
     */
    protected function get_third_params() {
        return array($this->widthparam);
    }

    /**
     * @return float
     */
    public function get_width() {
        $matches = array();
        if (null === $this->widthparam) {
            return 20;
        } else if (1 === preg_match('![0-9]+!', $this->widthparam, $matches)) {
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

/**
 * Class qtype_combined_combinable_accepts_vertical_or_horizontal_layout_param
 */
abstract class qtype_combined_combinable_accepts_vertical_or_horizontal_layout_param
    extends qtype_combined_combinable_accepts_third_param_validated_with_pattern {

    /**
     * @var string|null the string after second colon in embedded code if there is one.
     */
    protected $layoutparam = null;

    const THIRD_PARAM_PATTERN = '![vh]$!A';

    /**
     * @param $thirdparam
     * @return string
     */
    protected function error_string_when_third_param_fails_validation($thirdparam) {
        $qtypeid = $this->type->get_identifier();
        return get_string('err_accepts_vertical_or_horizontal_layout_param', 'qtype_combined', $qtypeid);
    }

    /**
     * @return string
     */
    protected function code_construction_instructions() {
        $a = $this->get_string_hash();
        return get_string('vertical_or_horizontal_embed_code', 'qtype_combined', $a);
    }

    /**
     * @param $thirdparam
     */
    protected function store_third_param($thirdparam) {
        $this->layoutparam = $thirdparam;
    }

    /**
     * @return null|string
     */
    public function get_layout() {
        return $this->layoutparam;
    }

    /**
     * @return array
     */
    protected function get_third_params() {
        return array($this->layoutparam);
    }
}

/**
 * Class qtype_combined_combinable_accepts_numerical_param
 */
abstract class qtype_combined_combinable_accepts_numerical_param
    extends qtype_combined_combinable_accepts_third_param_validated_with_pattern {

    const THIRD_PARAM_PATTERN = '![0-9]+$!A';

    /**
     * @param $thirdparam
     * @return string
     */
    protected function error_string_when_third_param_fails_validation($thirdparam) {
        $qtypeid = $this->type->get_identifier();
        return get_string('err_invalid_number', 'qtype_combined', $qtypeid);
    }
}
