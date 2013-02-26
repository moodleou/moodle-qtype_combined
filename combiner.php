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

class qtype_combined_type_manager {

    /**
     * @var array of qtype indentifier string => qtype_combined_combinable_type_base child classes.
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

    public function get_identifier() {
        return $this->identifier;
    }

    public function get_qtype_name() {
        return $this->qtypename;
    }
}


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

    /**
     * Question identifier must be one or more alphanumeric characters
     */
    const VALID_QUESTION_IDENTIFIER_PATTTERN = '![a-zA-Z0-9]+$!A';

    public function validate_question_text($questiontext) {
        $questiontexterror = $this->find_included_subqs_in_question_text($questiontext);
        if ($questiontexterror !== null) {
            $errors = array('questiontext' => $questiontexterror);
        } else {
            $errors = array();
        }
        return $errors;
    }

    public function form_for_subqs($questiontext, moodleform $combinedform, MoodleQuickForm $mform, $repeatenabled) {
        $this->find_included_subqs_in_question_text($questiontext);
        $weightingdefault = round(1/count($this->subqsfoundinquestiontext), 7);
        $weightingdefault = "$weightingdefault";
        foreach ($this->subqsfoundinquestiontext as $questionidentifier => $subq) {
            $a = new stdClass();
            $a->qtype = $subq->type->get_identifier();
            $a->qid = $questionidentifier;
            $mform->addElement('header', $subq->field_name('subqheader'), get_string('subqheader', 'qtype_combined', $a));
            $gradeoptions = question_bank::fraction_options();
            $mform->addElement('select', $subq->field_name('fraction'), get_string('weighting', 'qtype_combined'), $gradeoptions);
            $mform->setDefault($subq->field_name('fraction'), $weightingdefault);
            $subq->add_form_fragment($combinedform, $mform, $repeatenabled);
            $mform->addElement('editor', $subq->field_name('feedback'), get_string('incorrectfeedback', 'qtype_combined'),
                                                                                array('rows' => 5), $combinedform->editoroptions);
            $mform->addElement('hidden', $subq->field_name('qtypeid'), $subq->type->get_identifier());
        }
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

    protected function decode_code_in_question_text($codeinsideprepostfix) {
        $codeparts = explode(static::EMBEDDED_CODE_SEPARATOR, $codeinsideprepostfix, 3);
        $fullcode = static::EMBEDDED_CODE_PREFIX.$codeinsideprepostfix.static::EMBEDDED_CODE_POSTFIX;
        //replace any missing parts with null before return.
        $codeparts = $codeparts + array(null, null, null);
        return $codeparts;
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
        if (1 !== preg_match(static::VALID_QUESTION_IDENTIFIER_PATTTERN, $questionidentifier)) {
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

    protected function field_name_prefix() {
        return 'subq_'.$this->questionidentifier.'_';
    }

    public function field_name($elementname) {
        return $this->field_name_prefix().$elementname;
    }

    /**
     * @param string          $questionidfieldnamepostfix "subq_{question id}_"
     * @param moodleform      $combinedform
     * @param MoodleQuickForm $mform
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