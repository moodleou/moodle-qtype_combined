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
 * Code that deals with loading subqs and the users interaction with them at run time, ie. when
 * a combined question is used in an activity such as the quiz or previewed as a question preview.
 *
 * @package    qtype_combined
 * @copyright  2013 The Open University
 * @author     James Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/question/type/combined/combiner/base.php');

/**
 * Class qtype_combined_combiner_for_run_time_question_instance
 */
class qtype_combined_combiner_for_run_time_question_instance extends qtype_combined_combiner_base {

    /**
     * Instantiate question_definition classes for all subqs.
     */
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
        // This will be an array $startpos => array('length' => $embedcodelen, 'replacement' => $html).
        $replacements = array();

        foreach ($this->subqs as $subq) {
            $embedcodes = $subq->question_text_embed_codes();
            $currentpos = 0;
            foreach ($embedcodes as $placeno => $embedcode) {
                $renderedembeddedquestion = $subq->type->embedded_renderer()->subquestion($qa, $options, $subq, $placeno);

                // Now replace the first occurrence of the placeholder.
                $pos = strpos($questiontext, $embedcode, $currentpos);
                if ($pos === false) {
                    throw new coding_exception('Expected subquestion ' . $embedcode .
                            ' code not found in question text ' . $questiontext);
                }
                $embedcodelen = strlen($embedcode);
                $replacements[$pos] = array('length' => $embedcodelen, 'replacement' => $renderedembeddedquestion);
                $questiontext = substr_replace($questiontext,
                        str_repeat('X', $embedcodelen), $pos, $embedcodelen);
                $currentpos = $pos + $embedcodelen;
            }
        }

        // Now we actually do the replacements working from the end of the string,
        // so each replacement does not change the position of things still to be
        // replaced.
        krsort($replacements);
        foreach ($replacements as $startpos => $details) {
            $questiontext = substr_replace($questiontext,
                    $details['replacement'], $startpos, $details['length']);
        }

        return $questiontext;
    }

    /**
     * Call a method on question_definition object for all sub questions.
     * @param string $methodname
     * @param qtype_combined_param_to_pass_through_to_subq_base|mixed  $params,.... a variable number of arguments (or none)
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

    /**
     * @param $i
     * @param $propertyname
     * @return mixed
     */
    public function get_subq_property($i, $propertyname) {
        return $this->subqs[$i]->question->{$propertyname};
    }

    /**
     * @param $responses
     * @param $totaltries
     * @return number
     */
    public function compute_final_grade($responses, $totaltries) {
        $allresponses = new qtype_combined_array_of_response_arrays_param($responses);
        foreach ($this->subqs as $subqno => $subq) {
            $subqresponses = $allresponses->for_subq($subq);
            if (is_a($subq->question, 'question_automatically_gradable_with_countback')) {
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

    /**
     * Used for computing final grade for sub question. Find first identical response to final response for a question and remove
     * all responses  after that response.
     * @param $question question_automatically_gradable
     * @param $subqresponses
     * @return array all responses up to the first response that matches the final one.
     */
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
     * If the subq is not a question_automatically_gradable_with_countback then we need to implement the count back grading
     * for the subq.
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

    /**
     * @param $id integer
     * @return null|qtype_combined_combinable_base
     */
    public function find_subq_with_id($id) {
        foreach ($this->subqs as $subq) {
            if ($subq->question->id === $id) {
                return $subq;
            }
        }
        return null;
    }


    /**
     * @param $response qtype_combined_response_array_param
     * @return string errors
     */
    public function get_validation_error($response) {
        $errors = array();
        foreach ($this->subqs as $subqno => $subq) {
            if (!$this->call_subq($subqno, 'is_complete_response', $response)) {
                $questionerror = $this->call_subq($subqno, 'get_validation_error', $response);
                $controlnos = $subq->get_control_nos();
                $a = new stdClass();
                $a->error = $questionerror;
                if (count($controlnos) > 1) {
                    $a->controlname = $subq->type->get_control_name(true);
                    $a->controlnos = join(', ', $controlnos);
                    $errors[] = get_string('validationerror_multiplecontrols', 'qtype_combined', $a);
                } else {
                    $a->controlname = $subq->type->get_control_name(false);
                    $a->controlno = array_pop($controlnos);
                    $errors[] = get_string('validationerror_singlecontrol', 'qtype_combined', $a);
                }
            }
        }
        $errorliststring = html_writer::alist($errors);

        if (count($errors) > 1) {
            return get_string('validationerrors', 'qtype_combined', $errorliststring);
        } else {
            return get_string('validationerror', 'qtype_combined', $errorliststring);
        }
    }

}

/**
 * Class qtype_combined_param_to_pass_through_to_subq_base
 * Children of this class is used in run time combiner class calls to subqs to transform params from main question to pass to subq.
 * @see qtype_combined_combiner_for_run_time_question_instance::call_subq
 * @see qtype_combined_combiner_for_run_time_question_instance::call_all_subqs
 */
abstract class qtype_combined_param_to_pass_through_to_subq_base {
    /**
     * @param $alldata
     */
    abstract public function __construct($alldata);

    /**
     * @param $subq qtype_combined_combinable_base
     * @return mixed
     */
    abstract public function for_subq($subq);
}

/**
 * Class qtype_combined_response_array_param
 * Take main question response array and find part for each subq.
 */
class qtype_combined_response_array_param extends qtype_combined_param_to_pass_through_to_subq_base {
    /**
     * @var array
     */
    protected $responsearray;

    /**
     * @param array $responsearray
     */
    public function __construct($responsearray) {
        $this->responsearray = $responsearray;
    }

    /**
     * @param $subq qtype_combined_combinable_base
     * @return array response filtered for subq
     */
    public function for_subq($subq) {
        return $subq->get_substep(null)->filter_array($this->responsearray);
    }

}

/**
 * Class qtype_combined_array_of_response_arrays_param
 * Take an array of response arrays and return an array of response arrays for each subq.
 */
class qtype_combined_array_of_response_arrays_param extends qtype_combined_param_to_pass_through_to_subq_base {
    /**
     * @var array
     */
    protected $responsearrays;

    /**
     * @param $responsearrays
     */
    public function __construct($responsearrays) {
        $this->responsearrays = $responsearrays;
    }

    /**
     * @param $subq qtype_combined_combinable_base
     * @return array of arrays of filtered response for subq
     */
    public function for_subq($subq) {
        $filtered = array();
        foreach ($this->responsearrays as $responseno => $responsearray) {
            $filtered[$responseno] = $subq->get_substep(null)->filter_array($responsearray);
        }
        return $filtered;
    }
}

/**
 * Class qtype_combined_step_param
 */
class qtype_combined_step_param extends qtype_combined_param_to_pass_through_to_subq_base {
    /**
     * @var question_attempt_step
     */
    protected $step;

    /**
     * @param $step question_attempt_step for the main question.
     */
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
