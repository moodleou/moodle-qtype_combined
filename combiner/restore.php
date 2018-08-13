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
 * Code that allows access to subq internals for response recoding during course restore.
 *
 * @package    qtype_combined
 * @copyright  2013 The Open University
 * @author     James Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/combined/combiner/base.php');


/**
 * Class qtype_combined_combiner_for_restore
 */
class qtype_combined_combiner_for_restore extends qtype_combined_combiner_base {
    /**
     * @param array $response main question response
     * @return array[] array of response arrays indexed by subqno
     */
    public function get_subq_responses(array $response) {
        $subqresponses = array();
        foreach ($this->subqs as $subqno => $subq) {
            $subqresponses[$subqno] = $subq->get_substep(null)->filter_array($response);
        }
        return $subqresponses;
    }

    /**
     * @param $subqno integer
     * @return string Moodle question type name
     */
    public function get_subq_type($subqno) {
        return $this->subqs[$subqno]->type->get_qtype_name();
    }

    /**
     * @param $subqno integer
     * @return integer sub question id field from db
     */
    public function get_subq_id($subqno) {
        return $this->subqs[$subqno]->get_id();
    }
}
