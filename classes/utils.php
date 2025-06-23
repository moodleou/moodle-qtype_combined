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

namespace qtype_combined;
use question_utils;

/**
 * Helper functions for qtype_combined.
 *
 * @package   qtype_combined
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
    /**
     * @param int $num The number, starting at 0.
     * @param string $style The style to render the number in. One of the
     * @return string the number $num in the requested style.
     */
    public static function number_in_style($num, $style) {
        switch($style) {
            case 'abc':
                $number = chr(ord('a') + $num);;
                break;
            case 'ABCD':
                $number = chr(ord('A') + $num);;
                break;
            case '123':
                $number = $num + 1;
                break;
            case 'iii':
                $number = question_utils::int_to_roman($num + 1);
                break;
            case 'IIII':
                $number = strtoupper(question_utils::int_to_roman($num + 1));
                break;
            case 'none':
                return '';
            default:
                return 'ERR';
        }
        return $number . '. ';
    }

    /**
     * Are we running in a Moodle version with question versioning.
     *
     * @return bool true if the question versions exist.
     */
    public static function has_question_versioning(): bool {
        global $DB;
        static $hasversionning = null;
        if ($hasversionning === null) {
            $hasversionning = $DB->get_manager()->table_exists('question_bank_entries');
        }
        return $hasversionning;
    }

    /**
     * Replace the placeholder in subquestion id/name.
     *
     * @param string $stringtodisplay
     * @return string the place holder to display in the UI.
     */
    public static function replace_embed_placeholder(string $stringtodisplay): string {
        return str_replace(\qtype_combined_combiner_base::EMBEDDED_CODE_PLACEHOLDER,
            \qtype_combined_combiner_base::EMBEDDED_CODE_PLACEHOLDER_DISPLAY, $stringtodisplay);
    }
}
