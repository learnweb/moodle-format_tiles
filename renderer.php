<?php
/** Tiles format
 * This file is part of Moodle - http://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Renderer for outputting the tiles course format.
 *
 * @package format_tiles
 * @copyright 2018 David Watson
 * @copyright Based partly on previous topics format renderer and general course format renderer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.7
 */


defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/format/renderer.php');
require_once($CFG->dirroot . '/course/format/tiles/locallib.php');

/**
 * Basic renderer for tiles format.
 *
 * @copyright 2016 David Watson
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_tiles_renderer extends format_section_renderer_base
{
    /**
     * Constructor method, calls the parent constructor
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target)
    {
        parent::__construct($page, $target);
        // Since format_tiles_renderer::section_edit_controls() only displays the 'Set current section' control when editing mode is on
        // we need to be sure that the link 'Turn editing mode on' is available for a user who does not have any other managing capability.
        $page->set_other_editing_capability('moodle/course:setcurrentsection');
    }

    /**
     * Generate the starting container html for a list of sections as <ul class="tiles">
     * @param boolean $is_single_sec true if rendering a single section
     * so that can add this to id and then use in css
     * @return string HTML to output.
     * @throws coding_exception
     */
    protected function start_section_list($is_single_sec = false)
    {
        $class = 'tiles';
        if (optional_param('expanded', 0, PARAM_INT) == 1) {
            $class .= ' expanded';
        }
        if($is_single_sec) {
            $id = 'single_section_tiles';
        } else {
            $id = 'multi_section_tiles';
        }
        return html_writer::start_tag('ul', array('class' => $class, 'id' => $id));
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_list()
    {
        return html_writer::end_tag('ul');
    }

    /**
     * Generate the title for this section page
     * @return string the page title
     * @throws coding_exception
     */
    protected function page_title()
    {
        return get_string('topicoutline');
    }

    /**
     * Generate the edit control items of a section
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of edit control items
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function section_edit_control_items($course, $section, $onsectionpage = false)
    {
        global $PAGE;

        if (!$PAGE->user_is_editing()) {
            return array();
        }

        $coursecontext = context_course::instance($course->id);

        if ($onsectionpage) {
            $url = course_get_url($course, $section->section);
        } else {
            $url = course_get_url($course);
        }
        $url->param('sesskey', sesskey());

        $controls = array();
        if ($section->section && has_capability('moodle/course:setcurrentsection', $coursecontext)) {
            if ($course->marker == $section->section) {  // Show the "light globe" on/off.
                $url->param('marker', 0);
                $markedthistopic = get_string('markedthistopic');
                $highlightoff = get_string('highlightoff');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marked',
                    'name' => $highlightoff,
                    'pixattr' => array('class' => '', 'alt' => $markedthistopic),
                    'attr' => array('class' => 'editing_highlight', 'title' => $markedthistopic,
                        'data-action' => 'removemarker'));
            } else {
                $url->param('marker', $section->section);
                $markthistopic = get_string('markthistopic');
                $highlight = get_string('highlight');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marker',
                    'name' => $highlight,
                    'pixattr' => array('class' => '', 'alt' => $markthistopic),
                    'attr' => array('class' => 'editing_highlight', 'title' => $markthistopic,
                        'data-action' => 'setmarker'));
            }
        }

        if (!$onsectionpage && $section->section && has_capability('moodle/course:update', $coursecontext)) {
            // add controls to drop down menu on each editing tile for teacher to enter section, expand section etc
            $url = new moodle_url('/course/view.php', array('id' => $course->id, 'section' => $section->section));
            $controls['entersection'] = array('url' => $url, "icon" => 'a/view_list_active',
                'name' => get_string('entersection', 'format_tiles'),
                'attr' => array('class' => 'editing_activities', 'title' => get_string('entersection', 'format_tiles')));

            if(optional_param('expand', 0, PARAM_INT) == $section->section){
                // this section is already expanded, so display a collapse link
                $url = new moodle_url('/course/view.php', array('id' => $course->id), 'section-' . $section->section);
                $controls['collapseactivities'] = array('url' => $url, "icon" => 'i/up',
                    'name' => get_string('collapse', 'format_tiles'),
                    'attr' => array('class' => 'editing_activities', 'title' => get_string('collapse', 'format_tiles')));
            } else {
                // this section is collapsed, so display an expand link
                $url = new moodle_url('/course/view.php', array('id' => $course->id, 'expand'=> $section->section), 'section-' . $section->section);
                $controls['expandactivities'] = array('url' => $url, "icon" => 'e/resize',
                    'name' => get_string('revealcontents', 'format_tiles'),
                    'attr' => array('class' => 'editing_activities', 'title' => get_string('revealcontents', 'format_tiles')));
            }

        }

        $parentcontrols = parent::section_edit_control_items($course, $section, $onsectionpage);

        // If the edit key exists, we are going to insert our controls after it.
        if (array_key_exists("edit", $parentcontrols)) {
            $merged = array();
            // We can't use splice because we are using associative arrays.
            // Step through the array and merge the arrays.
            foreach ($parentcontrols as $key => $action) {
                $merged[$key] = $action;
                if ($key == "edit") {
                    // If we have come to the edit key, merge these controls here.
                    $merged = array_merge($merged, $controls);
                }
            }

            return $merged;
        } else {
            return array_merge($controls, $parentcontrols);
        }
    }

    /**
     * Generate the edit control action menu
     *
     * @param array $controls The edit control items from section_edit_control_items
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @return string HTML to output.
     */
    public function section_edit_control_menu($controls, $course, $section) {
        return parent::section_edit_control_menu($controls, $course, $section);
    }

    /**
     * Generate the html for the single section page
     * i.e. what students see when they are "in" a tile and see activities
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     * @param int $displaysection The section number in the course which is being displayed
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection)
    {
        $templateable = new \format_tiles\output\course_output($course, False, $displaysection, $this->courserenderer);
        $data = $templateable->export_for_template($this);
        echo $this->render_from_template('format_tiles/single_section_page', $data);
    }

    /**
     * Output the html for a multiple section page
     * i.e. what the users see when they first enter a course with all tiles shown
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        $templateable = new \format_tiles\output\course_output($course, False, 0, $this->courserenderer);
        $data = $templateable->export_for_template($this);
        echo $this->render_from_template('format_tiles/multi_section_page', $data);
    }

    /**
     * When we are on the course edit settings page, we need to be able to render an icon picker
     * modal window to add to the footer.  This satisfies that
     * @see \format_tiles::course_footer()
     * @see \format_tiles_icon_picker_icons
     * @return string the rendered HTML
     * @throws moodle_exception
     */
    protected function render_format_tiles_icon_picker_icons() {
        global $PAGE;
        $format = course_get_format($PAGE->course);
        $templateable = new \format_tiles\output\edit_course_settings_output($format->format_tiles_available_icons());
        $data = $templateable->export_for_template($this);
        return $this->render_from_template('format_tiles/icon_picker_modal_body', $data);
    }

    /**
     * Generate the display of the footer part of a section
     * @see section_header() for more explanation of this
     * @return string HTML to output.
     */
    protected function section_footer() {
        return html_writer::end_tag('li');
    }

    /**
     * Generate the section title, wraps it in a link to the section page if page is to be displayed on a separate page
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title($section, $course)
    {
        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section));
    }

    public function section_title_without_link($section, $course)
    {
        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section, false));
    }

    /**
     * Generate html for a section summary text
     * Override this here so we have access from the output class,
     * but just call the parent (protected)
     * @param stdClass $section
     * @return string
     */
    public function format_summary_text($section){
        return parent::format_summary_text($section);
    }

    /**
     * Generate a summary of the activites in a section
     *
     * Very similar to its parent except that it does not include
     * progress data, and is reformatted
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course the course record from DB
     * @param array $mods (argument not used)
     * @return string HTML to output.
     * @throws coding_exception
     * @throws moodle_exception
     * @see format_section_renderer_base::section_activity_summary()
     */
    public function section_activity_summary($section, $course, $mods) {
        global $PAGE;
        $modinfo = get_fast_modinfo($course);
        if (empty($modinfo->sections[$section->section])) {
            return '';
        }

        // Generate array with count of activities in this section:
        $sectionmods = array();
        $total = 0;
        $complete = 0;
        $cancomplete = isloggedin() && !isguestuser();
        $completioninfo = new completion_info($course);
        foreach ($modinfo->sections[$section->section] as $cmid) {
            $thismod = $modinfo->cms[$cmid];

            if ($thismod->modname == 'label') {
                // Labels are special (not interesting for students)!
                continue;
            }

            if ($thismod->uservisible) {
                if (isset($sectionmods[$thismod->modname])) {
                    $sectionmods[$thismod->modname]['name'] = $thismod->modplural;
                    $sectionmods[$thismod->modname]['count']++;
                } else {
                    $sectionmods[$thismod->modname]['name'] = $thismod->modfullname;
                    $sectionmods[$thismod->modname]['count'] = 1;
                }
                if ($cancomplete && $completioninfo->is_enabled($thismod) != COMPLETION_TRACKING_NONE) {
                    $total++;
                    $completiondata = $completioninfo->get_data($thismod, true);
                    if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                        $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                        $complete++;
                    }
                }
            }
        }

        if (empty($sectionmods)) {
            // No sections
            return '';
        }

        // Output section activities summary:
        $o = '';
        if(!$PAGE->user_is_editing()){
            //added for tiles
            $contents = '<b>' . get_string('contents', 'format_tiles') . ':</b><br>';
            $extraclass = '';
        } else {
            $contents = '';
            $extraclass = ' pull-right';
        }
        $o.= html_writer::start_tag('div', array('class' => 'section-summary-activities' . $extraclass));  // for tiles removed mdl-right clas
        $o .= $contents;
        foreach ($sectionmods as $mod) {
            $o.= html_writer::start_tag('span', array('class' => 'activity-count'));
            $o.= $mod['name'].': '.$mod['count'];
            $o.= html_writer::end_tag('span');
        }
        $o.= html_writer::end_tag('div');

        return $o;
    }

    /**
     * If section is not visible, display the message about that ('Not available
     * until...', that sort of thing). Otherwise, returns blank.
     *
     * Override this here so we have access from the output class,
     * but just call the parent (protected)
     *
     * @param section_info $section The course_section entry from DB
     * @param bool $canviewhidden True if user can view hidden sections
     * @return string HTML to output
     */
    public function section_availability_message($section, $canviewhidden) {
        return parent::section_availability_message($section, $canviewhidden);
    }

    /**
     * Show if something is on on the course clipboard (moving around)
     *
     * Override this here so we have access from the output class,
     * but just call the parent (protected)
     *
     * @param stdClass $course The course entry from DB
     * @param int $sectionno The section number in the coruse which is being dsiplayed
     * @return string HTML to output.
     */
    public function course_activity_clipboard($course, $sectionno = null) {
        return parent::course_activity_clipboard($course, $sectionno);
    }

    public function section_left_content($section, $course, $onsectionpage) {
        return parent::section_left_content($section, $course, $onsectionpage);
    }

    public function section_right_content($section, $course, $onsectionpage) {
        return parent::section_right_content($section, $course, $onsectionpage);
    }

    /**
     * Returns controls in the bottom of the page to increase/decrease number of sections
     *
     * @param stdClass $course
     * @param int|null $sectionreturn
     * @return string
     */
    public function change_number_sections($course, $sectionreturn = null) {
        return parent::change_number_sections($course, $sectionreturn);
    }

    /**
     * Generate html for course module content
     * (i.e. for the time being, the content of a page
     * Necessary to ensure that references to src="@@PLUGINFILE@@..." in $record->content
     * are re-written to the correct URL
     *
     * @param cm_info $mod the course module
     * @param stdClass $record the database record from the module table (e.g. the page table if it's a page)
     * @return string HTML to output.
     */
    public function format_cm_content_text($mod, $record) {
        $context = context_module::instance($mod->id);
        $text = file_rewrite_pluginfile_urls($record->content, 'pluginfile.php', $context->id, 'mod_' . $mod->modname, 'content', $record->revision);
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $formatoptions->overflowdiv = true;
        $formatoptions->context = $context;
        return format_text($text, $record->contentformat, $formatoptions);
    }
}
