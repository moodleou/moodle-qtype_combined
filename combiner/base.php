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
 * Classes contained here:
 * - qtype_combined_combiner_base - An instance of this class stores everything to do with the subqs for one combined question.
 * - qtype_combined_type_manager - Code to find hook classes for all question types that are available and load them.
 *
 * @package    qtype_combined
 * @copyright  2013 The Open University
 * @author     James Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/combined/combinable/combinablebase.php');
require_once($CFG->dirroot.'/question/type/combined/combinable/combinabletypebase.php');

/**
 * Class qtype_combined_combiner_base
 * This is a base class which holds common code used by combiner classes that are used to produce forms,
 * save and produce run time questions.
 * An instance of this class stores everything to do with the sub questions for one combined question.
 *
 */
abstract class qtype_combined_combiner_base {

    /**
     * @var qtype_combined_combinable_base[] array of sub questions, in question text, in form and in db. One instance for each
     *                                          question instance.
     */
    protected $subqs = array();

    /**
     * EMBEDDED_CODE_* and VALID_QUESTION_* defines the syntax for embedded subqs in question text.
     */
    const EMBEDDED_CODE_PREFIX = '[[';

    const EMBEDDED_CODE_POSTFIX = ']]';

    const EMBEDDED_CODE_SEPARATOR = ':';

    /** Question identifier must be one or more alphanumeric characters. */
    const VALID_QUESTION_IDENTIFIER_PATTTERN = '[a-zA-Z0-9]+';

    /**
     * Prefix both for field names in sub question form fragments and also for collecting student responses in run-time question.
     */
    const FIELD_NAME_PREFIX = 'subq:{qtype}:{qid}:';


    /**
     * Creates array of subq objects from the embedded codes in the question text.
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
        if (0 === preg_match_all($pattern, $questiontext, $matches)) {
            return  get_string('noembeddedquestions', 'qtype_combined');
        }

        $controlno = 1;
        foreach ($matches[1] as $codeinsideprepostfix) {
            $error = $this->make_combinable_instance_from_code_in_question_text($codeinsideprepostfix, $controlno);
            $controlno++;
            if ($error !== null) {
                return $error;
            }
        }
        $duplicatedids = $this->find_duplicate_question_identifiers();
        if (count($duplicatedids) !== 0) {
            return get_string('err_duplicateids', 'qtype_combined', join(',', $duplicatedids));
        }
        return null;
    }

    /**
     * @return string[] array of duplicate ids.
     */
    protected function find_duplicate_question_identifiers() {
        $listofsubqids = array();
        $duplicateids = array();
        foreach ($this->subqs as $subq) {
            $subqidentifier = $subq->get_identifier();
            if (false !== array_search($subqidentifier, $listofsubqids, true)) {
                $duplicateids[] = $subqidentifier;
            }
            $listofsubqids[] = $subqidentifier;
        }
        return $duplicateids;
    }

    /**
     * Create or just pass through the third embedded code param to each subq from question text.
     * @param $codeinsideprepostfix string The embedded code minus the enclosing brackets.
     * @param $controlno integer the control no, each subq can be responsible for more than one control in the question text.
     * @return string|null first error encountered or null if no error.
     */
    protected function make_combinable_instance_from_code_in_question_text($codeinsideprepostfix, $controlno) {
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
        if (strlen($questionidentifier) > 10) {
            return get_string('err_questionidentifiertoolong', 'qtype_combined', $questionidentifier);
        }
        if (!qtype_combined_type_manager::is_identifier_known($qtypeidentifier)) {
            return get_string('err_unrecognisedqtype', 'qtype_combined', $getstringhash);
        }

        $subq = $this->find_or_create_question_instance($qtypeidentifier, $questionidentifier);

        return $subq->found_in_question_text($thirdparam, $controlno);
    }

    /**
     * Access the array of subqs using the qtype and question identifier (identifiers as in question text).
     * @param string $qtypeidentifier the identifier as used in the question text ie. not the internal Moodle question type name.
     * @param string $questionidentifier the question identifier that is the first param of the embedded code.
     * @return null|qtype_combined_combinable_base null if not found or the existing subq if there is one that matches.
     */
    protected function find_question_instance($qtypeidentifier, $questionidentifier) {
        foreach ($this->subqs as $subq) {
            if ($subq->get_identifier() == $questionidentifier && $subq->type->get_identifier() == $qtypeidentifier) {
                return $subq;
            }
        }
        return null;
    }

    /**
     * Same as @see find_question_instance but creates subq instance if it does not exist.
     * @param $qtypeidentifier
     * @param $questionidentifier
     * @return qtype_combined_combinable_base
     */
    public function find_or_create_question_instance($qtypeidentifier, $questionidentifier) {
        $existing = $this->find_question_instance($qtypeidentifier, $questionidentifier);
        if ($existing !== null) {
            return $existing;
        } else {
            $new = qtype_combined_type_manager::new_subq_instance($qtypeidentifier, $questionidentifier);
            $this->subqs[] = $new;
            return $new;
        }
    }

    /**
     * Break down code in question text into three params, with null meaning no param.
     * @param string $codeinsideprepostfix code taken from inside square brackets.
     * @return array three params.
     */
    protected function decode_code_in_question_text($codeinsideprepostfix) {
        $codeparts = explode(static::EMBEDDED_CODE_SEPARATOR, $codeinsideprepostfix, 3);
        // Replace any missing parts with null before return.
        $codeparts = $codeparts + array(null, null, null);
        return $codeparts;
    }

    /**
     * Used for question subq validation and saving. Run through question data and find or create the subq object
     * and pass through the form data to be stored in the subq object.
     * @param $questiondata stdClass submitted question data
     */
    protected function get_subq_data_from_form_data($questiondata) {
        foreach ($questiondata->subqfragment_id as $subqkey => $qid) {
            $subq = $this->find_or_create_question_instance($questiondata->subqfragment_type[$subqkey], $qid);
            $subq->get_this_form_data_from($questiondata);
        }
    }

    /**
     * @param integer  $questionid The question id
     * @param bool     $getoptions Whether to also fetch the question options for each subq.
     */
    public function load_subq_data_from_db($questionid, $getoptions = false) {
        $subquestions = static::get_subq_data_from_db($questionid, $getoptions);
        $this->create_subqs_from_subq_data($subquestions);
    }

    /**
     * The db operation to fetch all sub-question data from the db. For run time question instances this is run before
     * question instance data caching as it seems more straight forward to have Moodle MUC cache stdClass rather than other
     * classes.
     * @param integer  $questionid The question id
     * @param bool     $getoptions Whether to also fetch the question options for each subq.
     * @return stdClass[]
     */
    public static function get_subq_data_from_db($questionid, $getoptions = false) {
        global $DB;
        $sql = 'SELECT q.*, qc.contextid FROM {question} q '.
            'JOIN {question_categories} qc ON q.category = qc.id ' .
            'WHERE q.parent = $1';

        // Load the questions.
        if (!$subqrecs = $DB->get_records_sql($sql, array($questionid))) {
            return array();
        }
        if ($getoptions) {
            get_question_options($subqrecs);
        }
        return $subqrecs;
    }

    /**
     * @param $subquestions
     */
    public function create_subqs_from_subq_data($subquestions) {
        foreach ($subquestions as $subquestion) {
            $qtypeid = qtype_combined_type_manager::translate_qtype_to_qtype_identifier($subquestion->qtype);
            $subq = $this->find_or_create_question_instance($qtypeid, $subquestion->name);
            $subq->found_in_db($subquestion);
        }
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


    /**
     * Take a response array from a subq and add prefixes.
     * @param question_attempt_step_subquestion_adapter|null $substep
     * @param array $response
     * @return array
     */
    protected function add_prefixes_to_response_array($substep, $response) {
        $keysadded = array();
        foreach ($response as $key => $value) {
            $keysadded[$substep->add_prefix($key)] = $value;
        }
        return $keysadded;
    }

}

/**
 * Class qtype_combined_type_manager. Code to find hook classes that are available and load them.
 */
class qtype_combined_type_manager {

    /**
     * @var qtype_combined_combinable_type_base[] key is qtype identifier string.
     */
    protected static $combinableplugins = null;

    /**
     * The combinable class is in question/type/combined/combinable/{qtypename}/combinable.php.
     * We expect to find renderer.php for subq in the same directory.
     */
    const FOUND_IN_COMBINABLE_DIR_OF_COMBINED = 1;

    /**
     * The combinable class is in question/type/{qtypename}/combinable.php
     * Subq renderer class should be in question/type/{qtypename}/renderer.php.
     */
    const FOUND_IN_OTHER_QTYPE_DIR = 2;

    /**
     * Finds and loads all hook classes. And saves plugin names for use later.
     */
    protected static function find_and_load_all_combinable_qtype_hook_classes() {
        global $CFG;
        if (null === self::$combinableplugins) {
            self::$combinableplugins = array();
            $pluginselsewhere = core_component::get_plugin_list_with_file('qtype', 'combinable/combinable.php', true);
            foreach ($pluginselsewhere as $qtypename => $unused) {
                self::instantiate_type_class($qtypename, self::FOUND_IN_OTHER_QTYPE_DIR);
            }
            $pluginshere = get_list_of_plugins('question/type/combined/combinable');
            foreach ($pluginshere as $qtypename) {
                require_once($CFG->dirroot.'/question/type/combined/combinable/'.$qtypename.'/combinable.php');
                self::instantiate_type_class($qtypename, self::FOUND_IN_COMBINABLE_DIR_OF_COMBINED);
            }
        }
        if (count(self::$combinableplugins) === 0) {
            print_error('nosubquestiontypesinstalled', 'qtype_combined');
        }
    }

    /**
     * @param string $qtypename
     * @param integer $where FOUND_IN_COMBINABLE_DIR_OF_COMBINED or FOUND_IN_OTHER_QTYPE_DIR
     */
    protected static function instantiate_type_class($qtypename, $where) {
        $classname = 'qtype_combined_combinable_type_'.$qtypename;
        $typeobj = new $classname($qtypename, $where);
        self::$combinableplugins[$typeobj->get_identifier()] = $typeobj;
    }

    /**
     * @param string $typeidentifier the identifier as found in the question text.
     * @return bool
     */
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
        if (!isset(self::$combinableplugins[$qtypename])) {
            print_error('subquestiontypenotinstalled', 'qtype_combined', '', $qtypename);
        }

    }

    /**
     * @return string the default question text when you first open the form. Also used to determine what subq form fragments
     * should be shown when you first start to create a question.
     */
    public static function default_question_text() {
        $i = 1;
        $codes = array();
        self::find_and_load_all_combinable_qtype_hook_classes();
        $identifiers = array_keys(self::$combinableplugins);
        sort($identifiers);
        foreach ($identifiers as $identifier) {
            $type = self::$combinableplugins[$identifier];
            $codes[] = $type->embedded_code_for_default_question_text($i);
            $i++;
        }
        return join("\n\n", $codes);
    }

    /**
     * Function used by response analysis reporting code to create unique id string for each response part.
     * @param $subqid string second param of embed code
     * @param $subqtype string Moodle question type
     * @param $subqresponseid string the response part id string used within subq.
     * @return string
     */
    public static function response_id($subqid, $subqtype, $subqresponseid) {
        $subtypeid = self::translate_qtype_to_qtype_identifier($subqtype);
        return join(':', array($subqid, $subtypeid, $subqresponseid));
    }
}
