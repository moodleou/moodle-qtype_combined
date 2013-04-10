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

/**
 * Class qtype_combined_combiner
 * An instance of this class stores everything to do with the sub questions for one combined question.
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

    public function form_for_subqs($questionid, $questiontext, moodleform $combinedform, MoodleQuickForm $mform, $repeatenabled) {
        if ($questiontext === null) {
            $questiontext = $this->default_question_text();
        }
        $this->find_included_subqs_in_question_text($questiontext);
        if (count($this->subqs) === 0) {
            $message = get_string('noembeddedquestions', 'qtype_combined');
            $message ='<span class="noembeddedquestionsmessage">'.$message.'</span>';
            $beforeqt = $mform->createElement('static', 'noembeddedquestionsmessage', '', $message);
            $mform->insertElementBefore($beforeqt, 'questiontext');
        }
        if ($questionid !== null) {
            $this->load_subq_data_from_db($questionid, true);
        }

        foreach ($this->subqs as $subq) {
            $weightingdefault = round(1/count($this->subqs), 7);
            $weightingdefault = "$weightingdefault";

            $a = new stdClass();
            $a->qtype = $subq->type->get_identifier();
            $a->qid = $subq->get_identifier();

            if ($subq->is_in_question_text()) {
                $headerlegend = get_string('subqheader', 'qtype_combined', $a);
            } else {
                $headerlegend = get_string('subqheader_not_in_question_text', 'qtype_combined', $a);
                $headerlegend ='<span class="not_in_question_text">'.$headerlegend.'</span>';
            }

            $mform->addElement('header', $subq->field_name('subqheader'), $headerlegend);

            if (!$subq->is_in_question_text()) {
                $message = $subq->message_in_form_if_not_included_in_question_text();
                $message = '<div class="not_in_question_text">'.$message.'</div>';
                $mform->addElement('static', 'notincludedinquestiontext', '', $message);
            }

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
    public function find_included_subqs_in_question_text($questiontext) {
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
        // Replace any missing parts with null before return.
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
                if ($subq->is_in_question_text()) {
                    if ($subq->is_in_form()) {
                        $errors += array($subq->field_name('defaultmark') =>
                                         get_string('err_fillinthedetailshere', 'qtype_combined'));
                        $errors += array('questiontext' => get_string('err_fillinthedetailsforsubq', 'qtype_combined', $subqid));
                    } else {
                        $errors += array('questiontext' => get_string('err_pressupdateformandfillin', 'qtype_combined', $subqid));
                    }
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

    public function save_subqs($fromform, $contextid) {
        $this->find_included_subqs_in_question_text($fromform->questiontext);
        $this->load_subq_data_from_db($fromform->id);
        $this->get_subq_data_from_form_data($fromform);
        foreach ($this->subqs as $subq) {
            $subq->save($contextid);
        }
    }

    public function all_subqs_in_question_text() {
        foreach ($this->subqs as $subq) {
            if ($subq->is_in_form() && !$subq->form_is_empty() && !$subq->is_in_question_text()) {
                return false;
            }
        }
        return true;
    }

    public function no_subqs() {
        return (count($this->subqs) === 0);
    }

    public function load_subq_data_from_db($questionid, $getoptions = false) {
        $subquestionsdata = static::get_subq_data_from_db($questionid, $getoptions);
        $this->create_subqs_from_subq_data($subquestionsdata);
    }

    public static function get_subq_data_from_db($questionid, $getoptions = false) {
        global $DB;
        $sql = 'SELECT q.*, qc.contextid FROM {question} q '.
            'JOIN {question_categories} qc ON q.category = qc.id ' .
            'WHERE q.parent = $1';

        // Load the questions
        if (!$subqrecs = $DB->get_records_sql($sql, array($questionid))) {
            return array();
        }
        if ($getoptions) {
            get_question_options($subqrecs);
        }
        return $subqrecs;
    }

    public function data_to_form($questionid, $toform, $context, $fileoptions) {
        $this->load_subq_data_from_db($questionid, true);
        foreach ($this->subqs as $subq) {
            $fromsubqtoform = $subq->data_to_form($context, $fileoptions);
            foreach ($fromsubqtoform as $property => $value) {
                $fieldname = $subq->field_name($property);
                $toform->{$fieldname} = $value;
            }
        }
        return $toform;
    }

    public function create_subqs_from_subq_data($subquestionsdata) {
        foreach ($subquestionsdata as $subquestiondata) {
            $qtypeid = qtype_combined_type_manager::translate_qtype_to_qtype_identifier($subquestiondata->qtype);
            $subq = $this->get_question_instance($qtypeid, $subquestiondata->name);
            $subq->found_in_db($subquestiondata);
        }
    }

    public function make_subqs() {
        foreach ($this->subqs as $subq) {
            $subq->make();
        }
    }

    /**
     * @param string                   $questiontext question text with embed codes to replace
     * @param question_attempt         $qa
     * @param question_display_options $options
     * @return string                  question text with embed codes replaced
     */
    public function render_subqs($questiontext, question_attempt $qa, question_display_options $options) {
        foreach ($this->subqs as $subq) {
            $embedcodes = $subq->question_text_embed_codes();
            foreach ($embedcodes as $placeno => $embedcode) {
                $renderedembeddedquestion = $subq->type->embedded_renderer()->subquestion($qa, $options, $subq, $placeno);
                $questiontext = str_replace($embedcode, $renderedembeddedquestion, $questiontext);
            }
        }
        return $questiontext;
    }

    /**
     * @param $substep question_attempt_step_subquestion_adapter|null
     * @param $response array
     * @return array
     */
    protected function add_prefixes_to_response_array($substep, $response) {
        $keysadded= array();
        foreach ($response as $key => $value) {
            $keysadded[$substep->add_prefix($key)] = $value;
        }
        return $keysadded;
    }

    /**
     * Call a method on question_definition object for all sub questions.
     * @param string $methodname
     * @param qtype_combined_param_to_pass_through_to_subq_base|mixed  $params,....
     * @return array of return values returned from method call on all subqs.
     */
    public function call_all_subqs($methodname/*, ... */) {
        $returned = array();
        $args = func_get_args();

        foreach ($this->subqs as $i => $unused) {
            // Call $this->call_subq($i, then same arguments as used to call this method).
            $returned[$i] = call_user_func_array(array($this, 'call_subq'), array_merge(array($i), $args));
        }
        return $returned;
    }

    /**
     * Call a method on question_definition object for all sub questions.
     * @param integer $i the index no of the sub question
     * @param string $methodname
     * @param qtype_combined_param_to_pass_through_to_subq_base|mixed  $params,....
     * @return array of return values returned from method call on all subqs.
     */
    public function call_subq($i, $methodname/*, ... */) {
        $subq = $this->subqs[$i];
        $paramsarray = array_slice(func_get_args(), 2);
        $paramsarrayfiltered = array();
        foreach ($paramsarray as $paramno => $param) {
            if (is_a($param, 'qtype_combined_param_to_pass_through_to_subq_base')) {
                $paramsarrayfiltered[$paramno] = $param->for_subq($subq);
            } else {
                $paramsarrayfiltered[$paramno] = $param;
            }
        }
        return call_user_func_array(array($subq->question, $methodname), $paramsarrayfiltered);
    }

    public function get_subq_property($i, $propertyname) {
        return $this->subqs[$i]->question->{$propertyname};
    }

    /**
     * @param array $arrays array of response arrays returned from method subq_method_calls.
     * @param null|question_attempt_step $step
     * @return array aggregated array with prefixes added to each subqs response array keys.
     */
    public function aggregate_response_arrays($arrays, $step = null) {
        $aggregated = array();
        foreach ($arrays as $i => $array) {
            $substep = $this->subqs[$i]->get_substep($step);
            $aggregated += $this->add_prefixes_to_response_array($substep, $array);
        }
        return $aggregated;
    }

    public function compute_final_grade($responses, $totaltries) {
        $allresponses = new qtype_combined_array_of_response_arrays_param($responses);
        foreach ($this->subqs as $subqno => $subq) {
            $subqresponses = $allresponses->for_subq($subq);
            if (is_a($subq, 'question_graded_automatically_with_countback')) {
                // Question may still need some help to get grading right.
                // Look at final response and see if that response has been given before.
                // If it has, grade that response given before and ignore all responses after.
                $responsestograde =
                                $this->responses_upto_first_response_identical_to_final_response($subq->question, $subqresponses);
                $subqfinalgrade = $subq->question->compute_final_grade($responsestograde, $totaltries);
            } else {
                // No compute final grade method for this question type.
                $subqfinalgrade = $this->compute_subq_final_grade($subq, $subqresponses);
            }
            // Weight grade by subq weighting stored in default mark.
            $finalgrades[$subqno] = $subqfinalgrade * $subq->question->defaultmark;
        }
        return array_sum($finalgrades);
    }

    protected function responses_upto_first_response_identical_to_final_response($question, $subqresponses) {
        $finalresponse = end($subqresponses);
        foreach (array_values($subqresponses) as $responseno => $subqresponse) {
            if ($question->is_same_response($subqresponse, $finalresponse)) {
                return array_slice($subqresponses, 0, $responseno+1);
            }
        }
        return $subqresponses;
    }

    /**
     * @param $subq qtype_combined_combinable_base
     * @param $subqresponses array
     * @return number fraction between 0 and 1.
     */
    public function compute_subq_final_grade($subq, $subqresponses) {
        $subqlastresponse = array_pop($subqresponses);
        $penalty = count($subqresponses) * $subq->question->penalty;
        foreach ($subqresponses as $subqresponseno => $subqresponse) {
            if ($subq->question->is_same_response($subqresponse, $subqlastresponse)) {
                $penalty = $subqresponseno * $subq->question->penalty;
                break;
            }
        }
        list($finalresponsegrade, ) = $subq->question->grade_response($subqlastresponse);
        return max(0, $finalresponsegrade * (1 - $penalty));
    }
}

abstract class qtype_combined_param_to_pass_through_to_subq_base {
    abstract public function __construct($alldata);

    abstract public function for_subq($subq);
}

class qtype_combined_response_array_param extends qtype_combined_param_to_pass_through_to_subq_base {
    /**
     * @var array
     */
    protected $responsearray;

    public function __construct($responsearray) {
        $this->responsearray = $responsearray;
    }

    public function for_subq($subq) {
        return $subq->get_substep(null)->filter_array($this->responsearray);
    }

}
class qtype_combined_array_of_response_arrays_param extends qtype_combined_param_to_pass_through_to_subq_base {
    /**
     * @var array
     */
    protected $responsearrays;

    public function __construct($responsearrays) {
        $this->responsearrays = $responsearrays;
    }

    /**
     * @param $subq qtype_combined_combinable_base
     * @return array of filtered response for subq
     */
    public function for_subq($subq) {
        $filtered = array();
        foreach ($this->responsearrays as $responseno => $responsearray) {
            $filtered[$responseno] = $subq->get_substep(null)->filter_array($responsearray);
        }
        return $filtered;
    }
}

class qtype_combined_step_param extends qtype_combined_param_to_pass_through_to_subq_base {
    /**
     * @var array
     */
    protected $step;

    public function __construct($step) {
        $this->step = $step;
    }

    /**
     * @param $subq qtype_combined_combinable_base
     * @return question_attempt_step_subquestion_adapter
     */
    public function for_subq($subq) {
        return $subq->get_substep($this->step);
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

    /**
     * @param $qtypename string the qtype name as used within Moodle
     * @return null|string null or identifier used as second param in question text embedded code.
     */
    public static function translate_qtype_to_qtype_identifier($qtypename) {
        self::find_and_load_all_combinable_qtype_hook_classes();
        foreach (self::$combinableplugins as $type) {
            if ($type->get_qtype_name() === $qtypename) {
                return $type->get_identifier();
            }
        }
        return null;
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
     * @return qtype_combined_embedded_renderer_base
     */
    public function embedded_renderer() {
        global $PAGE;
        if ($this->foundwhere === qtype_combined_type_manager::FOUND_IN_COMBINABLE_DIR_OF_COMBINED) {
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
     * @param $questiondata question record object to add extra options to.
     */
    public function get_question_options($questiondata) {
        $this->get_qtype_obj()->get_question_options($questiondata);
    }

    public function delete_question($questionid, $contextid) {
        global $DB;
        $DB->delete_records('question', array('id' => $questionid));
        $this->get_qtype_obj()->delete_question($questionid, $contextid);
    }

    /**
     * Overridden by child classes, but they also call this parent class.
     * @param $subqformdata object data extracted from form fragment for this subq
     * @return bool Has the user left this form fragment for this subq empty?
     */
    public function is_empty($subqformdata) {
        if (!$this->are_question_option_fields_empty($subqformdata)) {
            return false;
        }

        return html_is_blank($subqformdata->generalfeedback['text']);
    }

    public function save($oldsubq, $subqdata) {
        unset($subqdata->qtypeid);
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

    protected function are_question_option_fields_empty($subqformdata) {
        foreach ($this->get_question_option_fields() as $fieldname => $default) {
            if ($default === false) { // Default is empty.
                if (!empty($subqformdata->$fieldname)) {
                    return false;
                }
            } else if ($default === true) { // Default is not empty.
                if (empty($subqformdata->$fieldname)) {
                    return false;
                }
            }
        }
        return true;
    }

}

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
        $prefix = str_replace('{qid}', $this->questionidentifier, qtype_combined_combiner::FIELD_NAME_PREFIX);
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
            $this->formdata->$thisprop = $allformdata->$parentprop;
        }

    }

    public function is_in_form() {
        return $this->formdata !== null;
    }
    public function is_in_question_text() {
        return $this->foundinquestiontext;
    }
    public function is_in_db() {
        return $this->questionrec !== null;
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

    public function save($contextid) {
        $questionnotinqt = false;
        if ($this->is_in_form() && $this->form_is_empty() && $this->is_in_db()) {
            $this->type->delete_question($this->questionrec->id, $contextid);
        }
        if ($this->is_in_form() && !$this->form_is_empty()) {
            $this->formdata->name = $this->get_identifier();
            $this->type->save($this->questionrec, $this->formdata);
            if (!$this->is_in_question_text()) {
                $questionnotinqt = true;
            }
        }
        return $questionnotinqt;
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
            $code = join(qtype_combined_combiner::EMBEDDED_CODE_SEPARATOR, $params);
            $codes[$place] = qtype_combined_combiner::EMBEDDED_CODE_PREFIX.$code.qtype_combined_combiner::EMBEDDED_CODE_POSTFIX;
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