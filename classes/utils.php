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
    /** @var string - Less than operator */
    const OP_LT = "<";
    /** @var string - equal operator */
    const OP_E = "=";
    /** @var string - greater than operator */
    const OP_GT = ">";

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
     * @param string $stringtodisplay subquestion identifier.
     * @param bool $isremoved Default = false, if true, we will replace the placeholder with empty string.
     * @return string the place holder to display in the UI.
     */
    public static function replace_embed_placeholder(string $stringtodisplay, bool $isremoved = false): string {
        // We replace placeholder with empty string or display string.
        $replacestring = $isremoved ? '' : \qtype_combined_combiner_base::EMBEDDED_CODE_PLACEHOLDER_DISPLAY;
        // Replaces only the first occurrence of place holder code.
        $text = preg_replace('~' . \qtype_combined_combiner_base::EMBEDDED_CODE_PLACEHOLDER . '~',
            $replacestring, $stringtodisplay, 1);
        // Replace all the underscore of the string to space.
        $text = str_replace('_', ' ', $text);
        return $text;
    }

    /**
     * Conveniently compare the current moodle version to a provided version in branch format. This function will
     * inflate version numbers to a three digit number before comparing them. This way moodle minor versions greater
     * than 9 can be correctly and easily compared.
     *
     * Examples:
     *   utils::moodle_version_is("<", "39");
     *   utils::moodle_version_is("<=", "310");
     *   utils::moodle_version_is(">", "39");
     *   utils::moodle_version_is(">=", "38");
     *   utils::moodle_version_is("=", "41");
     *
     * CFG reference:
     * $CFG->branch = "311", "310", "39", "38", ...
     * $CFG->release = "3.11+ (Build: 20210604)", ...
     * $CFG->version = "2021051700.04", ...
     *
     * @param string $operator for the comparison
     * @param string $version to compare to
     * @return boolean
     */
    public static function moodle_version_is(string $operator, string $version): bool {
        global $CFG;

        if (strlen($version) == 2) {
            $version = $version[0]."0".$version[1];
        }

        $current = $CFG->branch;
        if (strlen($current) == 2) {
            $current = $current[0]."0".$current[1];
        }

        $from = intval($current);
        $to = intval($version);
        $ops = str_split($operator);

        foreach ($ops as $op) {
            switch ($op) {
                case self::OP_LT:
                    if ($from < $to) {
                        return true;
                    }
                    break;
                case self::OP_E:
                    if ($from == $to) {
                        return true;
                    }
                    break;
                case self::OP_GT:
                    if ($from > $to) {
                        return true;
                    }
                    break;
                default:
                    throw new \coding_exception('invalid operator '.$op);
            }
        }

        return false;
    }
}
