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
 * @package    qtype_combined
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/combined/combiner/restore.php');

/**
 * restore plugin class that provides the necessary information
 * needed to restore one combined qtype plugin.
 *
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_qtype_combined_plugin extends restore_qtype_plugin {

    /**
     * Returns the paths to be handled by the plugin at question level.
     */
    protected function define_question_plugin_structure() {

        $paths = array();

        // Add own qtype stuff.
        $elename = 'combined';
        // We used get_recommended_name() so this works.
        $elepath = $this->get_pathfor('/combined');
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths; // And we return the interesting paths.
    }

    /**
     * Process the qtype/combined element.
     */
    public function process_combined($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Detect if the question is created or mapped.
        $oldquestionid   = $this->get_old_parentid('question');
        $newquestionid   = $this->get_new_parentid('question');
        $questioncreated = (bool) $this->get_mappingid('question_created', $oldquestionid);

        // If the question has been created by restore, we need to create its
        // qtype_combined too.
        if ($questioncreated) {
            // Adjust some columns.
            $data->questionid = $newquestionid;
            // Insert record.
            $newitemid = $DB->insert_record('qtype_combined', $data);
            // Create mapping (needed for decoding links).
            $this->set_mapping('qtype_combined', $oldid, $newitemid);
        }
    }

    /**
     * Return the contents of this qtype to be processed by the links decoder.
     */
    public static function define_decode_contents() {

        $contents = array();

        $fields = array('correctfeedback', 'partiallycorrectfeedback', 'incorrectfeedback');
        $contents[] = new restore_decode_content('qtype_combined', $fields, 'qtype_combined');

        return $contents;
    }

    public function recode_response($questionid, $sequencenumber, array $response) {
        $combiner = new qtype_combined_combiner_for_restore();

        $combiner->load_subq_data_from_db($questionid);

        $recodedresponses = array();
        $subqresponses = $combiner->get_subq_responses($response);
        foreach ($subqresponses as $subqno => $subqresponse) {
            $subqtype = $combiner->get_subq_type($subqno);
            $subqid = $combiner->get_subq_id($subqno);
            $recodedresponses[$subqno] =
                $this->step->questions_recode_response_data($subqtype, $subqid, $sequencenumber, $subqresponse);
        }
        $subqresponsesrecoded = $combiner->aggregate_response_arrays($recodedresponses);

        // Remove responses recoded by sub questions to leave just
        // parts of response array for the main question e.g. '-finish' => 1.
        // I don't want to assume that the response recoding will not change the keys,
        // just in case.
        $subqresponseswithprefixes = $combiner->aggregate_response_arrays($subqresponses);
        foreach (array_keys($subqresponseswithprefixes) as $recodedkey) {
            unset($response[$recodedkey]);
        }

        return $subqresponsesrecoded + $response;
    }
}
