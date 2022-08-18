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
 * Defines the hooks necessary to make the showworking question type combinable
 *
 * @package    qtype_showworking
 * @copyright  2022 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fake_question.php');

/**
 * Class qtype_combined_combinable_type_showworking. Collects methods for the showworking widget.
 */
class qtype_combined_combinable_type_showworking extends qtype_combined_combinable_type_base {

    protected $identifier = 'showworking';

    protected function extra_question_properties() {
        return [];
    }

    protected function extra_answer_properties() {
        return [];
    }

    protected function transform_subq_form_data_to_full($subqdata) {
        return $subqdata;
    }

    public function third_param_for_default_question_text() {
        return '__80x5__';
    }

    public function save($oldsubq, $subqdata, int $oldsubqid) {
    }
}


/**
 * Class qtype_combined_combinable_showworking
 */
class qtype_combined_combinable_showworking extends qtype_combined_combinable_text_entry {

    /** Pattern extra to validate for 'show working' widget. */
    const THIRD_PARAM_PATTERN_EXTRA = '~^_+([0-9]+)x([0-9]+)_+$~';

    /**
     * @var string|null The extra info found in square brackets.
     */
    protected $sizeparam = null;

    public function is_real_subquestion(): bool {
        return false;
    }

    public function add_form_fragment(moodleform $combinedform, MoodleQuickForm $mform, $repeatenabled) {
    }

    public function validate() {
        return [];
    }

    public function get_sup_sub_editor_option() {
        return null;
    }

    public function has_submitted_data() {
        return false;
    }

    protected function store_third_param($thirdparam) {
        $this->sizeparam = $thirdparam;
    }

    protected function get_third_params() {
        return [$this->sizeparam];
    }

    /**
     * Get sizes of the show working.
     *
     * @return array The array contains rows and cols.
     */
    public function get_size(): array {
        $matches = [];
        $rows = 2;
        $cols = 50;
        if (null === $this->sizeparam) {
            return [$rows, $cols];
        }

        if (preg_match('/__([0-9]+)x([0-9]+)__/i', $this->sizeparam, $matches)) {
            $cols = $matches[1];
            $rows = $matches[2];
        } else {
            if (preg_match('/__([0-9]+)__/', $this->sizeparam, $matches)) {
                $cols = $matches[1];
            } else if (preg_match('/_____+/', $this->sizeparam, $matches)) {
                $cols = strlen($matches[0]);
            }
        }

        $rows = round($rows * 1.1);
        $cols = round($cols * 1.1);
        return [$rows, $cols];
    }

    public function validate_third_param($thirdparam) {
        if ($thirdparam === null) {
            return null;
        }

        if ((1 !== preg_match(static::THIRD_PARAM_PATTERN, $thirdparam)) &&
                (1 !== preg_match(self::THIRD_PARAM_PATTERN_EXTRA, $thirdparam))) {
            return $this->error_string_when_third_param_fails_validation($thirdparam);
        } else {
            return null;
        }
    }

    protected function error_string_when_third_param_fails_validation($thirdparam) {
        $qtypeid = $this->type->get_identifier();

        return get_string('err_invalid_width_specifier_postfix_showworking', 'qtype_combined', $qtypeid);
    }

    public function save($contextid) {
    }

    public function delete() {
    }

    public function make() {
        $this->question = new qtype_combined_showworking_fake_question(
                $this->questionidentifier);
    }
}
