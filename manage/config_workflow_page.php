<?php
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Workflow Configuration Page
 *
 * @package MantisBT
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright 2002 - 2013  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 *
 * @uses core.php
 * @uses authentication_api.php
 * @uses config_api.php
 * @uses constant_inc.php
 * @uses current_user_api.php
 * @uses form_api.php
 * @uses helper_api.php
 * @uses html_api.php

 * @uses print_api.php
 * @uses project_api.php
 * @uses string_api.php
 * @uses workflow_api.php
 */

require_once( '../core.php' );
require_api( 'authentication_api.php' );
require_api( 'config_api.php' );
require_api( 'constant_inc.php' );
require_api( 'current_user_api.php' );
require_api( 'form_api.php' );
require_api( 'helper_api.php' );
require_api( 'html_api.php' );
require_api( 'print_api.php' );
require_api( 'project_api.php' );
require_api( 'string_api.php' );
require_api( 'workflow_api.php' );

auth_reauthenticate();

html_page_top( _( 'Workflow Transitions' ) );

print_manage_menu( 'adm_permissions_report.php' );
print_manage_config_menu( 'manage_config_workflow_page.php' );

$t_access = current_user_get_access_level();
$t_project = helper_get_current_project();
$t_can_change_workflow = $t_access >= config_get_access( 'status_enum_workflow' );
$t_can_change_flags = $t_can_change_workflow;
$t_overrides = array();

/**
 * Set overrides
 * @param string $p_config config value
 */
function set_overrides( $p_config ) {
   global $t_overrides;
   if ( !in_array( $p_config, $t_overrides ) ) {
	   $t_overrides[] = $p_config;
   }
}

/**
 * Get the value associated with the specific action and flag.
 * @param int $p_from_status_id from status id
 * @param int $p_to_status_id to status id
 * @return string
 */
function show_flag( $p_from_status_id, $p_to_status_id ) {
	global $t_can_change_workflow, $t_file_workflow, $t_global_workflow, $t_project_workflow;
	if ( $p_from_status_id <> $p_to_status_id ) {
		$t_file = isset( $t_file_workflow['exit'][$p_from_status_id][$p_to_status_id] ) ? 1 : 0 ;
		$t_global = isset( $t_global_workflow['exit'][$p_from_status_id][$p_to_status_id] ) ? 1 : 0 ;
		$t_project = isset( $t_project_workflow['exit'][$p_from_status_id][$p_to_status_id] ) ? 1 : 0;

		$t_colour = '';
		if ( $t_global != $t_file ) {
			$t_colour = ' class="colour-global" '; # all projects override
			if ( $t_can_change_workflow ) {
				set_overrides( 'status_enum_workflow' );
			}
		}
		if ( $t_project != $t_global ) {
			$t_colour = ' class="colour-project" '; # project overrides
			if ( $t_can_change_workflow ) {
				set_overrides( 'status_enum_workflow' );
			}
		}
		$t_value = '<td class="center"' . $t_colour . '>';

		$t_flag = ( 1 == $t_project );
		$t_label = $t_flag ? $t_project_workflow['exit'][$p_from_status_id][$p_to_status_id] : '';

		if ( $t_can_change_workflow ) {
			$t_flag_name = $p_from_status_id . ':' . $p_to_status_id;
			$t_set = $t_flag ? "checked=\"checked\"" : "";
			$t_value .= "<input type=\"checkbox\" name=\"flag[]\" value=\"$t_flag_name\" $t_set />";
		} else {
			$t_value .= $t_flag ? '<img src="'.helper_mantis_url( 'themes/' . config_get( 'theme' ) . '/images/ok.png' ).'" width="20" height="15" title="X" alt="X" />' : '&#160;';
		}

		if ( $t_flag && ( '' != $t_label ) ) {
			$t_value .= '<br />(' . $t_label . ')';
		}
	} else {
		$t_value = '<td>&#160;';
	}

	$t_value .= '</td>';

	return $t_value;
}

/**
 * section header
 * @param string $p_section_name section name
 */
function section_begin( $p_section_name ) {
	$t_enum_statuses = MantisEnum::getValues( config_get( 'status_enum_string' ) );
	echo '<table class="width100">';
	echo '<tr><td class="form-title-caps" colspan="' . ( count( $t_enum_statuses ) + 2 ) . '">'
		. $p_section_name . '</td></tr>' . "\n";
	echo '<tr><td class="form-title" width="30%" rowspan="2">' . _( 'Current Status' ) . '</td>';
	echo '<td class="form-title" style="text-align:center" colspan="' . ( count( $t_enum_statuses ) + 1 ) . '">'
		. _( 'Next Status' ) . '</td></tr>';
	echo "\n<tr>";

	foreach( $t_enum_statuses as $t_status ) {
		echo '<td class="form-title" style="text-align:center">&#160;' . string_no_break( MantisEnum::getLabel( lang_get( 'status_enum_string' ), $t_status ) ) . '&#160;</td>';
	}

	echo '<td class="form-title" style="text-align:center">' . _( 'Default Value' ) . '</td>';
	echo '</tr>' . "\n";
}

/**
 * Print row
 * @param int $p_from_status from status
 */
function capability_row( $p_from_status ) {
	global $t_file_workflow, $t_global_workflow, $t_project_workflow, $t_can_change_workflow;
	$t_enum_status = MantisEnum::getAssocArrayIndexedByValues( config_get( 'status_enum_string' ) );
	echo '<tr><td>' . string_no_break( get_enum_element( 'status', $p_from_status ) ) . '</td>';
	foreach ( $t_enum_status as $t_to_status_id => $t_to_status_label ) {
		echo show_flag( $p_from_status, $t_to_status_id );
	}

	$t_file = isset( $t_file_workflow['default'][$p_from_status] ) ? $t_file_workflow['default'][$p_from_status] : 0 ;
	$t_global = isset( $t_global_workflow['default'][$p_from_status] ) ? $t_global_workflow['default'][$p_from_status] : 0 ;
	$t_project = isset( $t_project_workflow['default'][$p_from_status] ) ? $t_project_workflow['default'][$p_from_status] : 0;

	$t_colour = '';
	if ( $t_global != $t_file ) {
		$t_colour = ' class="colour-global" '; # all projects override
		if ( $t_can_change_workflow ) {
			set_overrides( 'status_enum_workflow' );
		}
	}
	if ( $t_project != $t_global ) {
		$t_colour = ' class="colour-project" '; # project overrides
		if ( $t_can_change_workflow ) {
			set_overrides( 'status_enum_workflow' );
		}
	}
	echo '<td class="center"' . $t_colour . '>';
	if ( $t_can_change_workflow ) {
		echo '<select name="default_' . $p_from_status . '">';
		print_enum_string_option_list( 'status', $t_project );
		echo '</select>';
	} else {
		echo MantisEnum::getLabel( lang_get( 'status_enum_string' ), $t_project );
	}
	echo ' </td>';
	echo '</tr>' . "\n";
}

/**
 * section footer
 */
function section_end() {
	echo '</table><br />' . "\n";
}

/**
 * threshold section begin
 * @param string $p_section_name section name
 */
function threshold_begin( $p_section_name ) {
	echo '<table class="width100">';
	echo '<tr><td class="form-title" colspan="3">' . $p_section_name . '</td></tr>' . "\n";
	echo '<tr><td class="form-title" width="30%">' . _( 'Threshold' ) . '</td>';
	echo '<td class="form-title" >' . _( 'Status' ) . '</td>';
	echo '<td class="form-title" >' . _( 'Who can alter this value' ) . '</td></tr>';
	echo "\n";
}

/**
 * threshold section row
 * @param string $p_threshold threshold
 */
function threshold_row( $p_threshold ) {
	global $t_access, $t_can_change_flags;

	$t_file = config_get_global( $p_threshold );
	$t_global = config_get( $p_threshold, null, null, ALL_PROJECTS );
	$t_project = config_get( $p_threshold );
	$t_can_change_threshold = $t_access >= config_get_access( $p_threshold );
	$t_colour = '';
	if ( $t_global != $t_file ) {
		$t_colour = ' class="colour-global" '; # all projects override
		if ( $t_can_change_threshold ) {
			set_overrides( $p_threshold );
		}
	}
	if ( $t_project != $t_global ) {
		$t_colour = ' class="colour-project" '; # project overrides
		if ( $t_can_change_threshold ) {
			set_overrides( $p_threshold );
		}
	}

	switch ($p_threshold) {
		case 'bug_submit_status':
			$t_threshold_label = _('Status to which a new issue is set');
			break;
		case 'bug_reopen_status':
			$t_threshold_label = _('Status to which reopened issues are set');
			break;
		case 'bug_resolved_status_threshold':
			$t_threshold_label = _('Status where an issue is considered resolved');
			break;
		case 'bug_closed_status_threshold':
			$t_threshold_label = _('Status where an issue is considered closed');
			break;
	}

	echo '<tr><td>' . $t_threshold_label . '</td>';
	if ( $t_can_change_threshold ) {
		echo '<td' . $t_colour . '><select name="threshold_' . $p_threshold . '">';
		print_enum_string_option_list( 'status', $t_project );
		echo '</select> </td>';
		echo '<td><select name="access_' . $p_threshold . '">';
		print_enum_string_option_list( 'access_levels', config_get_access( $p_threshold ) );
		echo '</select> </td>';
		$t_can_change_flags = true;
	} else {
		echo '<td' . $t_colour . '>' . MantisEnum::getLabel( lang_get( 'status_enum_string' ), $t_project ) . '&#160;</td>';
		echo '<td>' . MantisEnum::getLabel( lang_get( 'access_levels_enum_string' ), config_get_access( $p_threshold ) ) . '&#160;</td>';
	}

	echo '</tr>' . "\n";
}

/**
 * threshold section end
 */
function threshold_end() {
	echo '</table><br />' . "\n";
}

/**
 * access begin
 * @param string $p_section_name section name
 */
function access_begin( $p_section_name ) {
	echo '<table class="width100">';
	echo '<tr><td class="form-title" colspan="2">'
		. $p_section_name . '</td></tr>' . "\n";
	echo '<tr><td class="form-title" colspan="2">' . _( 'Minimum Access Level to Change to this Status' ) . '</td></tr>';
}

/**
 * access row
 */
function access_row() {
	global $t_access, $t_can_change_flags;

	$t_enum_status = MantisEnum::getAssocArrayIndexedByValues( config_get( 'status_enum_string' ) );

	$t_file_new = config_get_global( 'report_bug_threshold' );
	$t_global_new = config_get( 'report_bug_threshold', null, null, ALL_PROJECTS );
	$t_project_new = config_get( 'report_bug_threshold' );

	$t_file_set = config_get_global( 'set_status_threshold' );
	foreach ( $t_enum_status as $t_status => $t_status_label) {
		if ( !isset( $t_file_set[$t_status] ) ) {
			$t_file_set[$t_status] = config_get_global('update_bug_status_threshold');
		}
	}

	$t_global_set = config_get( 'set_status_threshold', null, null, ALL_PROJECTS );
	foreach ( $t_enum_status as $t_status => $t_status_label) {
		if ( !isset( $t_file_set[$t_status] ) ) {
			$t_file_set[$t_status] = config_get('update_bug_status_threshold', null, null, ALL_PROJECTS );
		}
	}

	$t_project_set = config_get( 'set_status_threshold' );
	foreach ( $t_enum_status as $t_status => $t_status_label) {
		if ( !isset( $t_file_set[$t_status] ) ) {
			$t_file_set[$t_status] = config_get('update_bug_status_threshold' );
		}
	}

	foreach ( $t_enum_status as $t_status => $t_status_label) {
		echo '<tr><td width="30%">' . string_no_break( MantisEnum::getLabel( lang_get( 'status_enum_string' ), $t_status ) ) . '</td>';
		if ( config_get( 'bug_submit_status' ) == $t_status ) {
			$t_level = $t_project_new;
			$t_can_change = ( $t_access >= config_get_access( 'report_bug_threshold' ) );
			$t_colour = '';
			if ( $t_global_new != $t_file_new ) {
				$t_colour = ' class="colour-global" '; # all projects override
				if ( $t_can_change ) {
					set_overrides( 'report_bug_threshold' );
				}
			}
			if ( $t_project_new != $t_global_new ) {
				$t_colour = ' class="colour-project" '; # project overrides
				if ( $t_can_change ) {
					set_overrides( 'report_bug_threshold' );
				}
			}
		} else {
			$t_level = ( isset( $t_project_set[$t_status] ) ? $t_project_set[$t_status] : false );
			$t_level_global = ( isset( $t_global_set[$t_status] ) ? $t_global_set[$t_status] : false );
			$t_level_file = ( isset( $t_file_set[$t_status] ) ? $t_file_set[$t_status] : false );
			$t_can_change = ( $t_access >= config_get_access( 'set_status_threshold' ) );
			$t_colour = '';
			if ( $t_level_global != $t_level_file ) {
				$t_colour = ' class="colour-global" '; # all projects override
				if ( $t_can_change ) {
					set_overrides( 'set_status_threshold' );
				}
			}
			if ( $t_level != $t_level_global ) {
				$t_colour = ' class="colour-project" '; # project overrides
				if ( $t_can_change ) {
					set_overrides( 'set_status_threshold' );
				}
			}
		}
		if ( $t_can_change ) {
			echo '<td' . $t_colour . '><select name="access_change_' . $t_status . '">';
			print_enum_string_option_list( 'access_levels', $t_level );
			echo '</select> </td>';
			$t_can_change_flags = true;
		} else {
			echo '<td class="center"' . $t_colour . '>' . MantisEnum::getLabel( lang_get( 'access_levels_enum_string' ), $t_level ) . '</td>';
		}
		echo '</tr>' . "\n";
	}
}

echo '<br /><br />';

# count arcs in and out of each status
$t_enum_status = config_get( 'status_enum_string' );
$t_status_arr  = MantisEnum::getAssocArrayIndexedByValues( $t_enum_status );

$t_extra_enum_status = '0:non-existent,' . $t_enum_status;
$t_lang_enum_status = '0:' . _( 'non-existent' ) . ',' . lang_get( 'status_enum_string' );
$t_all_status = explode( ',', $t_extra_enum_status);

# gather all versions of the workflow
$t_file_workflow = workflow_parse( config_get_global( 'status_enum_workflow' ) );
$t_global_workflow = workflow_parse( config_get( 'status_enum_workflow', null, null, ALL_PROJECTS ) );
$t_project_workflow = workflow_parse( config_get( 'status_enum_workflow' ) );

# validate the project workflow
$t_validation_result = '';
foreach ( $t_status_arr as $t_status => $t_label ) {
	if ( isset( $t_project_workflow['exit'][$t_status][$t_status] ) ) {
		$t_validation_result .= '<tr><td>'
						. MantisEnum::getLabel( $t_lang_enum_status, $t_status )
						. '</td><td bgcolor="#FFED4F">' . _( 'Arc from status to itself is implied, and need not be given explicitly' ) . '</td>';
	}
}

# check for entry == 0 without exit == 0, unreachable state
foreach ( $t_status_arr as $t_status => $t_status_label) {
	if ( ( 0 == count( $t_project_workflow['entry'][$t_status] ) ) && ( 0 < count( $t_project_workflow['exit'][$t_status] ) ) ){
		$t_validation_result .= '<tr><td>'
						. MantisEnum::getLabel( $t_lang_enum_status, $t_status )
						. '</td><td bgcolor="#FF0088">' . _( 'You cannot move an issue into this status' ) . '</td>';
	}
}

# check for exit == 0 without entry == 0, unleaveable state
foreach ( $t_status_arr as $t_status => $t_status_label ) {
	if ( ( 0 == count( $t_project_workflow['exit'][$t_status] ) ) && ( 0 < count( $t_project_workflow['entry'][$t_status] ) ) ){
		$t_validation_result .= '<tr><td>'
						. MantisEnum::getLabel( $t_lang_enum_status, $t_status )
						. '</td><td bgcolor="#FF0088">' . _( 'You cannot move an issue out of this status' ) . '</td>';
	}
}

# check for exit == 0 and entry == 0, isolated state
foreach ( $t_status_arr as $t_status => $t_status_label ) {
	if ( ( 0 == count( $t_project_workflow['exit'][$t_status] ) ) && ( 0 == count( $t_project_workflow['entry'][$t_status] ) ) ){
		$t_validation_result .= '<tr><td>'
						. MantisEnum::getLabel( $t_lang_enum_status, $t_status )
						. '</td><td bgcolor="#FF0088">' . _( 'You cannot move an issue into this status' ) . '<br />' . _( 'You cannot move an issue out of this status' ) . '</td>';
	}
}

echo "<form id=\"workflow_config_action\" method=\"post\" action=\"config_workflow_set.php\">\n";
echo '<fieldset>';
echo form_security_field( 'manage_config_workflow_set' );
echo '</fieldset>';

if ( ALL_PROJECTS == $t_project ) {
	$t_project_title = _( 'Note: These configurations affect all projects, unless overridden at the project level.' );
} else {
	$t_project_title = sprintf( _( 'Note: These configurations affect only the %1 project.' ) , string_display( project_get_name( $t_project ) ) );
}
echo '<p class="bold">' . $t_project_title . '</p>' . "\n";
echo '<p>' . _( 'In the table below, the following color code applies:' ) . '<br />';
if ( ALL_PROJECTS <> $t_project ) {
	echo '<span class="colour-project">' . _( 'Project setting overrides others.' ) .'</span><br />';
}
echo '<span class="colour-global">' . _( 'All Project settings override default configuration.' ) . '</span></p>';

# show the settings used to derive the table
threshold_begin( _( 'Thresholds that Affect Workflow' ) );
if ( !is_array( config_get( 'bug_submit_status' ) ) ) {
	threshold_row( 'bug_submit_status' );
}
threshold_row( 'bug_resolved_status_threshold' );
threshold_row( 'bug_reopen_status' );
threshold_end();
echo '<br />';

if ( '' <> $t_validation_result ) {
	echo '<table class="width100">';
	echo '<tr><td class="form-title" colspan="3">' . _( 'Workflow Validation' ) . '</td></tr>' . "\n";
	echo '<tr><td class="form-title" width="30%">' . _( 'Status' ) . '</td>';
	echo '<td class="form-title" >' . _( 'Validation Comment' ) . '</td></tr>';
	echo "\n";
	echo $t_validation_result;
	echo '</table><br /><br />';
}

# display the graph as a matrix
section_begin( _( 'Workflow' ) );
foreach ( $t_status_arr as $t_from_status => $t_from_label) {
	capability_row( $t_from_status );
}
section_end();

if ( $t_can_change_workflow ) {
	echo '<p>' . _( 'Who can change workflow:' );
	echo '<select name="workflow_access">';
	print_enum_string_option_list( 'access_levels', config_get_access( 'status_enum_workflow' ) );
	echo '</select> </p><br />';
}

# display the access levels required to move an issue
access_begin( _( 'Access Levels' ) );
access_row();
section_end();

if ( $t_access >= config_get_access( 'set_status_threshold' ) ) {
	echo '<p>' . _( 'Who can change access levels:' );
	echo '<select name="status_access">';
	print_enum_string_option_list( 'access_levels', config_get_access( 'set_status_threshold' ) );
	echo '</select> </p><br />';
}

if ( $t_can_change_flags ) {
	echo "<input type=\"submit\" class=\"button\" value=\"" . _( 'Update Configuration' ) . "\" />\n";
	echo "</form>\n";

	if ( 0 < count( $t_overrides ) ) {
		echo "<div class=\"right\"><form id=\"mail_config_action\" method=\"post\" action=\"config_revert.php\">\n";
		echo '<fieldset>';
		echo form_security_field( 'manage_config_revert' );
		echo "<input name=\"revert\" type=\"hidden\" value=\"" . implode( ',', $t_overrides ) . "\" />";
		echo "<input name=\"project\" type=\"hidden\" value=\"$t_project\" />";
		echo "<input name=\"return\" type=\"hidden\" value=\"\" />";
		echo "<input type=\"submit\" class=\"button\" value=\"";
		if ( ALL_PROJECTS == $t_project ) {
			echo _( 'Delete All Projects Settings' );
		} else {
			echo _( 'Delete Project Specific Settings' );
		}
		echo "\" />\n";
		echo '</fieldset>';
		echo "</form></div>\n";
	}

} else {
	echo "</form>\n";
}

html_page_bottom();