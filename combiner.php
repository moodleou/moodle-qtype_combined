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
     * @var array of qtype_combined_combinable_base child objects for questions embedded in question type.
     */
    protected $subqsfoundinquestiontext;


    /**
     * @var array of qtype_combined_combinable_base child objects for questions embedded in question type.
     */
    protected $subqsnotfoundinquestiontext;

    /**
     * @var array with alphanumeric keys which are the identifiers of questions from in the question text, in order.
     *            The values in the array are instances of child classes of qtype_combined_combinable_base
     */
    protected $subqsidentifiersinquestiontext;


    const EMBEDDED_CODE_PREFIX = '[[';
    const EMBEDDED_CODE_POSTFIX = ']]';
    const EMBEDDED_CODE_SEPARATOR = ':';

    const FIELD_NAME_PREFIX = 'subq_{qid}_';

    /**
     * Question identifier must be one or more alphanumeric characters
     */
    const VALID_QUESTION_IDENTIFIER_PATTTERN = '[a-zA-Z0-9]+';

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
        $weightingdefault = round(1/count($this->subqsfoundinquestiontext), 7);
        $weightingdefault = "$weightingdefault";
        foreach ($this->subqsfoundinquestiontext as $questionidentifier => $subq) {
            $a = new stdClass();
            $a->qtype = $subq->type->get_identifier();
            $a->qid = $questionidentifier;
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
        $errors += $this->validate_subqs($fromform);
        return $errors;
    }


    public function validate_question_text($questiontext) {
        $questiontexterror = $this->find_included_subqs_in_question_text($questiontext);
        if ($questiontexterror !== null) {
            $errors = array('questiontext' => $questiontexterror);
        } else {
            $errors = array();
        }
        return $errors;
    }

    /**
     * @param $questiontext the question text
     * @return null|string either null if no error or an error message.
     */
    protected function find_included_subqs_in_question_text($questiontext) {
        $this->subqsfoundinquestiontext = array();
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
     * @param $codeinsideprepostfix The embedded code minus the enclosing brackets.
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
        if (!isset($this->subqsfoundinquestiontext[$questionidentifier])) {
            $this->subqsfoundinquestiontext[$questionidentifier] =
                                            qtype_combined_type_manager::new_subq_instance($qtypeidentifier, $questionidentifier);
        } else if ($qtypeidentifier !==
                        $this->subqsfoundinquestiontext[$questionidentifier]->type->get_identifier()) {
            return get_string('err_twodifferentqtypessameidentifier', 'qtype_combined', $getstringhash);

        } else if (!$this->subqsfoundinquestiontext[$questionidentifier]->can_be_more_than_one_of_same_instance()) {
            return get_string('err_thisqtypecannothavemorethanonecontrol', 'qtype_combined', $getstringhash);
        }
        $subq = $this->subqsfoundinquestiontext[$questionidentifier];
        $error = $subq->process_third_param($thirdparam);
        if (null !== $error) {
            return $error;
        }
        return null; // Done, no error.
    }

    protected function decode_code_in_question_text($codeinsideprepostfix) {
        $codeparts = explode(static::EMBEDDED_CODE_SEPARATOR, $codeinsideprepostfix, 3);
        //replace any missing parts with null before return.
        $codeparts = $codeparts + array(null, null, null);
        return $codeparts;
    }

    protected static function field_name_prefix($questionid) {
        return str_replace('{qid}', $questionid, self::FIELD_NAME_PREFIX);
    }

    public static function field_name($questionid, $elementname) {
        return self::field_name_prefix($questionid).$elementname;
    }

    protected function validate_subqs($fromform) {
        $fromsubqformfragments = $this->find_subq_data_in_form_data($fromform);
        $errors = array();
        $fractionsum = 0;
        foreach ($this->subqsfoundinquestiontext as $subqid => $subq) {
            // If verifying the question text and updating the form then formdata for subq can be not set or empty but
            // if not empty then need to validate.

            if (isset($fromsubqformfragments[$subqid]) && !$subq->type->is_empty($fromsubqformfragments[$subqid])) {
                $errors += $this->subqsfoundinquestiontext[$subqid]->validate($fromsubqformfragments[$subqid]);
            } else if (!isset($fromform['updateform'])) {
                if (isset($fromsubqformfragments[$subqid])) {
                    $errors += array($this->field_name($subqid, 'defaultmark') =>
                                                            get_string('err_fillinthedetailshere', 'qtype_combined'));
                    $errors += array('questiontext' => get_string('err_fillinthedetailsforsubq', 'qtype_combined', $subqid));
                } else {
                    $errors += array('questiontext' => get_string('err_pressupdateformandfillin', 'qtype_combined', $subqid));
                }

            }
            $fractionsum += $fromsubqformfragments[$subqid]->defaultmark;
        }
        if ($fractionsum != 1) {
            foreach (array_keys($this->subqsfoundinquestiontext) as $subqid) {
                $errors += array($this->field_name($subqid, 'defaultmark') =>
                                 get_string('err_weightingsdonotaddup', 'qtype_combined'));
            }
        }

        return $errors;
    }

    protected function find_subq_data_in_form_data($questiondata) {
        $subqdata = array();
        $questiondata = (array)$questiondata;
        foreach (array_keys($questiondata) as $key) {
            $find = preg_quote('{qid}', '!');
            $subject = preg_quote(self::FIELD_NAME_PREFIX, '!');
            $patternforprefix = str_replace($find, '('.self::VALID_QUESTION_IDENTIFIER_PATTTERN.')', $subject);
            $matches = array();
            if (preg_match("!{$patternforprefix}qtypeid$!A", $key, $matches)) {
                $subqid = $matches[1];
                $prefix = self::field_name_prefix($subqid);
                $subqdata[$subqid] = new stdClass();
                foreach ($questiondata as $key2 => $value) {
                    if (strpos($key2, $prefix) === 0) {
                        $afterprefix = substr($key2, strlen($prefix));
                        $subqdata[$subqid]->$afterprefix = $value;
                    }
                }
            }
        }
        return $subqdata;
    }

    public function save_subqs($fromform) {
        global $USER;
        $oldsubqs = $this->load_all_subqs($fromform->id);
        $fromsubqformfragments = $this->find_subq_data_in_form_data($fromform);
        foreach ($fromsubqformfragments as $subqid => $subqdata) {
            $subqdata->name = $subqid;
            $subqdata->parent = $fromform->id;
            $subqdata->category = $fromform->category;
            if (isset($oldsubqs[$subqid])) {
                $oldsubq = $oldsubqs[$subqid];
            } else {
                $oldsubq = new stdClass();
            }
            qtype_combined_type_manager::save_subq($oldsubq, $subqdata);
        }
    }

    protected function load_all_subqs($questionid) {
        global $DB;
        $subqsindexedbyname = array();
        if ($subqs = $DB->get_records('question', array('parent' => $questionid))) {
            foreach ($subqs as $subq) {
                if (isset($subqsindexedbyname[$subq->name])) {
                    throw new invalid_state_exception('More than one combined subq in db with the same identifier!');
                }
                $subqsindexedbyname[$subq->name] = $subq;
            }
        }
        return $subqsindexedbyname;
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
    public static function new_subq_instance($typeidentifier, $questionidentifier) {
        self::find_and_load_all_combinable_qtype_hook_classes();
        $type = self::$combinableplugins[$typeidentifier];
        return $type->new_subq_instance($questionidentifier);
    }

    public static function save_subq($oldsubq, $subqdata) {
        self::find_and_load_all_combinable_qtype_hook_classes();
        $type = self::$combinableplugins[$subqdata->qtypeid];
        unset($subqdata->qtypeid);
        return $type->save_subq($oldsubq, $subqdata);
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

    public function save_subq($oldsubq, $subqdata) {
        if ($this->is_empty($subqdata)) {
            return;
        }
        $qtype = $this->get_qtype_obj();
        $oldsubq->qtype = $this->get_qtype_name();
        $subqdata = $this->add_question_properties($subqdata);
        $qtype->save_question($oldsubq, $subqdata);
    }

    /**
     * @return string question type name as per directory name in question/type/
     */
    protected function get_qtype_name() {
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
}

abstract class qtype_combined_combinable_base {

    protected $questionrec;


    /**
     * @var string question identifier found in question text for this instance
     */
    protected $questionidentifier;

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

    public function field_name($elementname) {
        return qtype_combined_combiner::field_name($this->questionidentifier, $elementname);
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

    public function process_third_param($thirdparam) {
        $qtypename = $this->type->get_identifier();
        return get_string('err_thisqtypedoesnotacceptextrainfo', 'qtype_combined', $qtypename);
    }

    /**
     * @param $subqdata
     * @return array empty or containing errors with field name keys.
     */
    abstract public function validate($subqdata);
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