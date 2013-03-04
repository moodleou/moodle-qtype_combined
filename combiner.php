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
 * Code that deals with finding and loading code from subqs.
 *
 * @package    qtype
 * @subpackage combined
 * @copyright  2013 The Open University
 * @author     James Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class qtype_combined_combiner {

    /**
     * @var qtype_combined_combinable_base[] array of sub questions, in question text, in form and in db. One instance for each
     *                                          question instance.
     */
    protected $subqs = array();


    const EMBEDDED_CODE_PREFIX = '[[';
    const EMBEDDED_CODE_POSTFIX = ']]';
    const EMBEDDED_CODE_SEPARATOR = ':';

    const FIELD_NAME_PREFIX = 'subq:{qtype}:{qid}:';

    /**
     * Question identifier must be one or more alphanumeric characters
     */
    const VALID_QUESTION_IDENTIFIER_PATTTERN = '[a-zA-Z0-9]+';
    /**
     * Question identifier must be one or more alphanumeric characters
     */
    const VALID_QUESTION_TYPE_IDENTIFIER_PATTTERN = '[a-zA-Z0-9_-]+';

    public function default_question_text() {
        return "[[1:numeric:__10__]]\n\n".
            "[[2:pmatch]]\n\n".
            "[[3:multiresponse:v]]\n\n".
            "[[4:selectmenu:1]]\n";
    }

    public function form_for_subqs($questiontext, moodleform $combinedform, MoodleQuickForm $mform, $repeatenabled) {
        if ($questiontext === null) {
            $questiontext = $this->default_question_text();
        }
        $this->find_included_subqs_in_question_text($questiontext);
        $weightingdefault = round(1/count($this->subqs), 7);
        $weightingdefault = "$weightingdefault";
        foreach ($this->subqs as $subq) {
            $a = new stdClass();
            $a->qtype = $subq->type->get_identifier();
            $a->qid = $subq->get_identifier();
            $mform->addElement('header', $subq->field_name('subqheader'), get_string('subqheader', 'qtype_combined', $a));
            $gradeoptions = question_bank::fraction_options();
            $mform->addElement('select', $subq->field_name('defaultmark'), get_string('weighting', 'qtype_combined'),
                               $gradeoptions);
            $mform->setDefault($subq->field_name('defaultmark'), $weightingdefault);
            $subq->add_form_fragment($combinedform, $mform, $repeatenabled);
            $mform->addElement('editor', $subq->field_name('generalfeedback'), get_string('incorrectfeedback', 'qtype_combined'),
                                                                                array('rows' => 5), $combinedform->editoroptions);
            $mform->setType($subq->field_name('generalfeedback'), PARAM_RAW);
            $mform->addElement('hidden', $subq->field_name('qtypeid'), $subq->type->get_identifier());

        }
    }

    public function validate_subqs_data_in_form($fromform, $files) {
        $errors = $this->validate_question_text($fromform['questiontext']['text']);
        $errors += $this->validate_subqs((object)$fromform);
        return $errors;
    }


    protected function validate_question_text($questiontext) {
        $questiontexterror = $this->find_included_subqs_in_question_text($questiontext);
        if ($questiontexterror !== null) {
            $errors = array('questiontext' => $questiontexterror);
        } else {
            $errors = array();
        }
        return $errors;
    }

    /**
     * @param $questiontext string the question text
     * @return null|string either null if no error or an error message.
     */
    protected function find_included_subqs_in_question_text($questiontext) {
        $this->subqs = array();
        $pattern = '!'.
                    preg_quote(static::EMBEDDED_CODE_PREFIX, '!') .
                    '(.*?)'.
                    preg_quote(static::EMBEDDED_CODE_POSTFIX, '!')
                    .'!';
        $matches = array();
        preg_match_all($pattern, $questiontext, $matches);
        foreach ($matches[1] as $codeinsideprepostfix) {
            $error = $this->make_combinable_instance_from_code_in_question_text($codeinsideprepostfix);
            if ($error !== null) {
                return $error;
            }
        }
        return null;
    }

    /**
     * @param $codeinsideprepostfix string The embedded code minus the enclosing brackets.
     * @return string|null first error encountered or null if no error.
     */
    protected function make_combinable_instance_from_code_in_question_text($codeinsideprepostfix) {
        list($questionidentifier, $qtypeidentifier, $thirdparam) =
                                                    $this->decode_code_in_question_text($codeinsideprepostfix);
        $getstringhash = new stdClass();
        $getstringhash->fullcode = static::EMBEDDED_CODE_PREFIX.$codeinsideprepostfix.static::EMBEDDED_CODE_POSTFIX;
        $getstringhash->qtype = $qtypeidentifier;
        $getstringhash->qid = $questionidentifier;
        if ($questionidentifier === null || $qtypeidentifier === null || $qtypeidentifier === '') {
            return get_string('err_insufficientnoofcodeparts', 'qtype_combined', $getstringhash);
        }
        $qidpattern = '!'. self::VALID_QUESTION_IDENTIFIER_PATTTERN . '$!A';
        if (1 !== preg_match($qidpattern, $questionidentifier)) {
            return get_string('err_invalidquestionidentifier', 'qtype_combined', $getstringhash);
        }
        if (!qtype_combined_type_manager::is_identifier_known($qtypeidentifier)) {
            return get_string('err_unrecognisedqtype', 'qtype_combined', $getstringhash);
        }

        $subq = $this->get_question_instance($qtypeidentifier, $questionidentifier);

        $error = $subq->found_in_question_text($thirdparam);
        if (null !== $error) {
            return $error;
        }
        return null; // Done, no error.
    }

    protected function find_question_instance($qtypeidentifier, $questionidentifier) {
        foreach ($this->subqs as $subq) {
            if ($subq->get_identifier() == $questionidentifier && $subq->type->get_identifier() == $qtypeidentifier) {
                return $subq;
            }
        }
        return null;
    }

    protected function get_question_instance($qtypeidentifier, $questionidentifier) {
        $existing = $this->find_question_instance($qtypeidentifier, $questionidentifier);
        if ($existing !== null) {
            return $existing;
        } else {
            $new = qtype_combined_type_manager::new_subq_instance($qtypeidentifier, $questionidentifier);
            $this->subqs[] = $new;
            return $new;
        }
    }

    protected function decode_code_in_question_text($codeinsideprepostfix) {
        $codeparts = explode(static::EMBEDDED_CODE_SEPARATOR, $codeinsideprepostfix, 3);
        //replace any missing parts with null before return.
        $codeparts = $codeparts + array(null, null, null);
        return $codeparts;
    }

    protected function validate_subqs($fromform) {
        $this->get_subq_data_from_form_data($fromform);
        $errors = array();
        $fractionsum = 0;
        foreach ($this->subqs as $subq) {
            // If verifying the question text and updating the form then formdata for subq can be not set or empty but
            // if not empty then need to validate.

            $subqid = $subq->get_identifier();

            if ($subq->is_in_form() && !$subq->form_is_empty()) {
                $errors += $subq->validate();
            } else if (!isset($fromform->updateform)) {
                if ($subq->is_in_form()) {
                    $errors += array($subq->field_name('defaultmark') =>
                                                            get_string('err_fillinthedetailshere', 'qtype_combined'));
                    $errors += array('questiontext' => get_string('err_fillinthedetailsforsubq', 'qtype_combined', $subqid));
                } else {
                    $errors += array('questiontext' => get_string('err_pressupdateformandfillin', 'qtype_combined', $subqid));
                }

            }
            if ($subq->is_in_form() && $subq->is_in_question_text()) {
                $defaultmarkfieldname = $subq->field_name('defaultmark');
                $fractionsum += $fromform->$defaultmarkfieldname;
            }
        }
        if ((!isset($fromform->updateform)) && $fractionsum != 1) {
            foreach ($this->subqs as $subq) {
                if ($subq->is_in_question_text()) {
                    $errors += array($subq->field_name('defaultmark') => get_string('err_weightingsdonotaddup', 'qtype_combined'));
                }
            }
        }

        return $errors;
    }

    protected function get_subq_data_from_form_data($questiondata) {
        $subqs = array();
        foreach ($questiondata as $key => $unused) {
            $qidpart = preg_quote('{qid}', '!');
            $qtypepart = preg_quote('{qtype}', '!');
            $pregquotedprefixpattern = preg_quote(self::FIELD_NAME_PREFIX, '!');
            $patternforprefix = str_replace($qidpart, '(?P<qid>'.self::VALID_QUESTION_IDENTIFIER_PATTTERN.')',
                                            $pregquotedprefixpattern);
            $patternforprefix = str_replace($qtypepart, '(?P<qtype>'.self::VALID_QUESTION_TYPE_IDENTIFIER_PATTTERN.')',
                                            $patternforprefix);
            $matches = array();
            if (preg_match("!{$patternforprefix}qtypeid$!A", $key, $matches)) {
                $subq = $this->get_question_instance($matches['qtype'], $matches['qid']);
                $subq->get_this_form_data_from($questiondata);
            }
        }
        return $subqs;
    }

    public function save_subqs($fromform) {
        $this->load_subq_data_from_db($fromform->id);
        $this->get_subq_data_from_form_data($fromform);
        foreach ($this->subqs as $subq) {
            $subq->save();
        }
    }

    protected function load_subq_data_from_db($questionid) {
        global $DB;
        if ($subqrecs = $DB->get_records('question', array('parent' => $questionid))) {
            foreach ($subqrecs as $subqrec) {
                $qtypeid = qtype_combined_type_manager::translate_qtype_to_qtype_identifier($subqrec->qtype);
                $subq = $this->get_question_instance($qtypeid, $subqrec->name);
                $subq->found_in_db($subqrec);
            }
        }
    }


}

class qtype_combined_type_manager {

    /**
     * @var array containing qtype indentifier string => qtype_combined_combinable_type_base child classes.
     */
    protected static $combinableplugins = null;

    const FOUND_IN_COMBINABLE_DIR_OF_COMBINED = 1;

    const FOUND_IN_OTHER_QTYPE_DIR = 2;

    /**
     * Finds and loads all hook classes. And saves plugin names for use later.
     */
    protected static function find_and_load_all_combinable_qtype_hook_classes() {
        global $CFG;
        if (null === self::$combinableplugins) {
            self::$combinableplugins = array();
            $pluginselsewhere = get_plugin_list_with_file('qtype', 'combinable.php', true);
            foreach ($pluginselsewhere as $qtypename) {
                self::instantiate_type_class($qtypename, self::FOUND_IN_OTHER_QTYPE_DIR);
            }
            $pluginshere = get_list_of_plugins('question/type/combined/combinable');
            foreach ($pluginshere as $qtypename) {
                include_once($CFG->dirroot.'/question/type/combined/combinable/'.$qtypename.'/combinable.php');
                self::instantiate_type_class($qtypename, self::FOUND_IN_COMBINABLE_DIR_OF_COMBINED);
            }
        }
    }

    protected static function instantiate_type_class($qtypename, $where) {
        $classname = 'qtype_combined_combinable_type_'.$qtypename;
        $typeobj = new $classname($qtypename, $where);
        self::$combinableplugins[$typeobj->get_identifier()] = $typeobj;
    }

    public static function is_identifier_known($typeidentifier) {
        self::find_and_load_all_combinable_qtype_hook_classes();
        return isset(self::$combinableplugins[$typeidentifier]);
    }

    /**
     * @param $typeidentifier string
     * @param $questionidentifier string
     * @return qtype_combined_combinable_base
     */
    public static function new_subq_instance($typeidentifier, $questionidentifier) {
        self::find_and_load_all_combinable_qtype_hook_classes();
        $type = self::$combinableplugins[$typeidentifier];
        return $type->new_subq_instance($questionidentifier);
    }

    public static function translate_qtype_to_qtype_identifier($qtypename) {
        self::find_and_load_all_combinable_qtype_hook_classes();
        foreach (self::$combinableplugins as $type) {
            if ($type->get_qtype_name() === $qtypename) {
                return $type->get_identifier();
            }
        }


    }

}


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
     * Overridden by child classes, but they also call this parent class.
     * @param $subqformdata data extracted from form fragment for this subq
     * @return bool Has the user left this form fragment for this subq empty?
     */
    public function is_empty($subqformdata) {
        return html_is_blank($subqformdata->generalfeedback['text']);
    }

    public function save($oldsubq, $subqdata) {
        if ($this->is_empty($subqdata)) {
            return;
        }
        unset($subqdata->qtypeid);
        if ($oldsubq === null) {
            $oldsubq = new stdClass();
        }
        $oldsubq->qtype = $this->get_qtype_name();
        $subqdata = $this->transform_subq_form_data_to_full($subqdata);
        $qtype = $this->get_qtype_obj();
        $qtype->save_question($oldsubq, $subqdata);
    }

}

abstract class qtype_combined_combinable_base {

    /**
     * @var bool whether found in question text.
     */
    protected $foundinquestiontext = false;

    protected $questionrec = null;


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
        $prefix = str_replace('{qid}', $this->questionidentifier, qtype_combined_combiner::FIELD_NAME_PREFIX);
        return str_replace('{qtype}', $this->type->get_identifier(), $prefix);
    }

    public function field_name($elementname) {
        return $this->field_name_prefix().$elementname;
    }


    /**
     * @param moodleform      $combinedform
     * @param MoodleQuickForm $mform
     * @param                 $repeatenabled
     */
    abstract public function add_form_fragment(moodleform $combinedform, MoodleQuickForm $mform,
                                               $repeatenabled);

    /**
     * @return mixed
     */
    abstract public function set_form_data();

    public function found_in_question_text($thirdparam) {
        if ($this->foundinquestiontext && !$this->can_be_more_than_one_of_same_instance()) {
            $getstringhash = new stdClass();
            $getstringhash->qtype = $this->type->get_identifier();
            $getstringhash->qid = $this->get_identifier();
            return get_string('err_thisqtypecannothavemorethanonecontrol', 'qtype_combined', $getstringhash);
        }
        $this->foundinquestiontext = true;
        return $this->process_third_param($thirdparam);
    }

    protected function process_third_param($thirdparam) {
        if ($thirdparam !== null) {
            $qtypename = $this->type->get_identifier();
            return get_string('err_thisqtypedoesnotacceptextrainfo', 'qtype_combined', $qtypename);
        }
    }

    /**
     * @param $subqdata
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
        //stuff to copy from parent question
        foreach (array('parent' => 'id', 'category' => 'category') as $thisprop => $parentprop) {
            $this->formdata->$thisprop = $allformdata->$parentprop;
        }

    }

    public function is_in_form() {
        return $this->formdata !== null;
    }
    public function is_in_question_text() {
        return $this->foundinquestiontext;
    }

    public function form_is_empty() {
        return $this->type->is_empty($this->formdata);
    }

    public function get_identifier() {
        return $this->questionidentifier;
    }

    public function found_in_db($questionrec) {
        $this->questionrec = $questionrec;
    }

    public function save() {
        if ($this->is_in_form() && !$this->form_is_empty()) {
            $this->formdata->name = $this->get_identifier();
            $this->type->save($this->questionrec, $this->formdata);
        }
    }

}


abstract class qtype_combined_combinable_accepts_third_param_validated_with_pattern
        extends qtype_combined_combinable_base {

    /**
     * @var string|null the string after second colon in embedded code if there is one.
     */
    protected $thirdparam = null;

    const THIRD_PARAM_PATTERN = '!undefined!';

    public function process_third_param($thirdparam) {
        $this->thirdparam = $thirdparam;
        return $this->validate_third_param($thirdparam);
    }

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

abstract class qtype_combined_combinable_accepts_width_specifier
    extends qtype_combined_combinable_accepts_third_param_validated_with_pattern {


    const THIRD_PARAM_PATTERN = '!_+[0-9]*_+$!A';

    protected function error_string_when_third_param_fails_validation($thirdparam) {
        $qtypeid = $this->type->get_identifier();
        return get_string('err_invalid_width_specifier_postfix', 'qtype_combined', $qtypeid);
    }

}
abstract class qtype_combined_combinable_accepts_vertical_or_horizontal_layout_param
    extends qtype_combined_combinable_accepts_third_param_validated_with_pattern {


    const THIRD_PARAM_PATTERN = '![vh]$!A';

    protected function error_string_when_third_param_fails_validation($thirdparam) {
        $qtypeid = $this->type->get_identifier();
        return get_string('err_accepts_vertical_or_horizontal_layout_param', 'qtype_combined', $qtypeid);
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