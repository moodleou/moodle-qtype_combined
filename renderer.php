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
 * Combined question renderer class.
 *
 * @package    qtype
 * @subpackage combined
 * @copyright  2013 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Generates the output for combined questions.
 *
 * @copyright  2013 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_combined_renderer extends qtype_renderer {
    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {

        $question = $qa->get_question();

        $questiontext = $question->format_questiontext($qa);

        foreach ($question->combiner->subqs as $subq) {
            $embedcode = $subq->question_text_embed_code();
            $renderedembeddedquestion = $subq->type->embedded_renderer()->subquestion($qa, $options, $subq);
            $questiontext = str_replace($embedcode, $renderedembeddedquestion, $questiontext);
        }

        $result = html_writer::tag('div', $questiontext, array('class' => 'qtext'));

        // TODO might need a new hook for "$currentanswer = $qa->get_last_qt_var('answer');" in sub q.
        // TODO and to return the validation error.
        /* if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error(array('answer' => $currentanswer)),
                    array('class' => 'validationerror'));
        }*/
        return $result;
    }

    public function specific_feedback(question_attempt $qa) {
        // TODO needs to pass through to sub-questions.
        return '';
    }

    public function correct_response(question_attempt $qa) {
        // TODO needs to pass through to sub-questions.
        return '';
    }
}

/**
 * Subclass for generating the bits of output specific to sub-questions.
 *
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class qtype_combined_embedded_renderer_base extends qtype_renderer {

    abstract public function subquestion(question_attempt $qa,
                                         question_display_options $options,
                                         qtype_combined_combinable_base $subq);
}

class qtype_combined_varnumeric_embedded_renderer extends qtype_combined_embedded_renderer_base {

    public function subquestion(question_attempt $qa,
                                         question_display_options $options,
                                         qtype_combined_combinable_base $subq) {
        return 'varnumeric';
    }
}

class qtype_combined_pmatch_embedded_renderer extends qtype_combined_embedded_renderer_base {

    public function subquestion(question_attempt $qa,
                                question_display_options $options,
                                qtype_combined_combinable_base $subq) {
        return 'pmatch';
    }
}
class qtype_combined_gapselect_embedded_renderer extends qtype_combined_embedded_renderer_base {

    public function subquestion(question_attempt $qa,
                                question_display_options $options,
                                qtype_combined_combinable_base $subq) {
        return 'gapselect';
    }
}
class qtype_combined_oumultiresponse_embedded_renderer extends qtype_combined_embedded_renderer_base {

    public function subquestion(question_attempt $qa,
                                question_display_options $options,
                                qtype_combined_combinable_base $subq) {
        return 'oumultiresponse';
    }
}
