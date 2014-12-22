<?php

// Send Emails from CollabPress
function cp_send_email( $to, $subject, $message ) {

    // Load plugin options
    $cp_options = cp_get_options();

    // Check if email notifications are enabled - default to enabled
    $cp_email_notify = ( $cp_options['email_notifications'] == 'disabled' ) ? false : true;
    // If email notifications are enabled proceed
    if ( $cp_email_notify ) {

		// Check thru emails, either array of string.
		if ( is_array( $to ) ) {
			$new_to = array();
			foreach ( $to as $to_email ) {
				if ( is_email( $to_email ) )
					$new_to[] = $to_email;
			}
			$to = $new_to;
		} else if ( is_string( $to ) ) {
			$to = ( is_email( $to ) ) ? $to : null;
		}

		$cp_subject = ( isset( $subject ) ) ? $subject : '';
		$cp_message = $message;

		// Send Away
		wp_mail( $to, $cp_subject, $cp_message );
    }
}

// User Notice
function cp_user_notice($data) {

	// Project Added
	if ( isset( $data['cp-add-project'] ) )
		echo '<div class="updated fade"><p><strong>'.__('Project Added', 'collabpress').'</strong></p></div>';

	// Project Updated
	if ( isset( $data['cp-edit-project'] ) )
		echo '<div class="updated fade"><p><strong>'.__('Project Updated', 'collabpress').'</strong></p></div>';

	// Project Deleted
	if ( isset( $data['cp-delete-project-id'] ) )
		echo '<div class="error fade"><p><strong>'.__('Project Deleted', 'collabpress').'</strong></p></div>';

	// Task List Added
	if ( isset( $data['cp-add-task-list'] ) )
		echo '<div class="updated fade"><p><strong>'.__('Task List Added', 'collabpress').'</strong></p></div>';

	// Task List Added
	if ( isset( $data['cp-edit-task-list'] ) )
		echo '<div class="updated fade"><p><strong>'.__('Task List Updated', 'collabpress').'</strong></p></div>';

	// Task List Deleted
	if ( isset( $data['cp-delete-task-list-id'] ) )
		echo '<div class="error fade"><p><strong>'.__('Task List Deleted', 'collabpress').'</strong></p></div>';

	// Task Added
	if ( isset( $data['cp-add-task'] ) )
		echo '<div class="updated fade"><p><strong>'.__('Task Added', 'collabpress').'</strong></p></div>';

	// Task Updated
	if ( isset( $data['cp-edit-task-id'] ) )
		echo '<div class="updated fade"><p><strong>'.__('Task Updated', 'collabpress').'</strong></p></div>';

	// Task Deleted
	if ( isset( $data['cp-delete-task-id'] ) )
		echo '<div class="error fade"><p><strong>'.__('Task Deleted', 'collabpress').'</strong></p></div>';

	// Comment Added
	if ( isset( $data['cp-add-comment'] ) )
		echo '<div class="updated fade"><p><strong>'.__('Comment Added', 'collabpress').'</strong></p></div>';

	// Activity log cleared
	if ( isset( $data['cp_clear_activity'] ) )
		echo '<div class="updated fade"><p><strong>' .__( 'Acitivity Log Has Been Cleared', 'collabpress' ) .'</strong></p></div>';

}

/**
 * Create a new CollabPress activity post.
 *
 */
function cp_add_activity( $action = NULL, $type = NULL, $author = NULL, $ID = NULL ) {
	$add_activity = array(
		'post_title' => __( 'Activity', 'collabpress' ),
		'post_status' => 'publish',
		'post_type' => 'cp-meta-data'
	);

	$activity_id = wp_insert_post( $add_activity );
	update_post_meta( $activity_id, '_cp-meta-type', 'activity' );

	// Action
	if ( $action )
		update_post_meta( $activity_id, '_cp-activity-action', $action );
	// Type
	if ( $type )
		update_post_meta( $activity_id, '_cp-activity-type', $type );
	// Author
	if ( $author )
		update_post_meta( $activity_id, '_cp-activity-author', $author );
	// ID of the related CollabPress item
	if ( $ID )
		update_post_meta( $activity_id, '_cp-activity-ID', $ID );


	do_action( 'cp_add_activity', $action, $type, $author, $ID, $activity_id );
}

/**
 * Outputs all comments on displayed task.
 */
function cp_task_comments() {
	global $cp;
	global $cp_task;
	global $cp_project;

	// Get Current User
	global $current_user;
	get_currentuserinfo();

	$comments = get_comments( array(
		'post_id' => $cp->task->ID,
		'order' => 'ASC',
	) );

	echo '<div id="cp_task_comments_wrap">';

	if ($comments) :
		$commentCount = 1;
		// Display each comment
		foreach( $comments as $comment_key => $comm ) :
			$row = ($comment_key % 2) ?  'odd' : 'even';
		?>
			<div class="cp_task_comment <?php echo $row ?>">
				<a class="avatar" title="<?php echo $comm->comment_author ?>" href="<?php echo COLLABPRESS_DASHBOARD; ?>&user=<?php echo $comm->user_id ?>"><?php echo get_avatar($comm->user_id, 64) ?></a>
				<div class="cp_task_comment_content">
					<p class="cp_comment_author">
						<?php printf(
								__( '%1$s said on %2$s', 'collabpress' ),
								'<a title="' . $comm->comment_author . '" href="' . COLLABPRESS_DASHBOARD . '&user=' . $comm->user_id . '">' . $comm->comment_author . '</a>',
								'<span class="cp_comment_date">' . get_comment_date( cp_get_date_format(), $comm->comment_ID . '</span>' )
						); ?>
					<input type="hidden" id="delete_comment_nonce_<?php echo $comm->comment_ID ?>" value="<?php echo wp_create_nonce( 'delete-task-comment_' . $comm->comment_ID ); ?>" />
					<?php
					if ( $current_user->ID == $comm->user_id || current_user_can( 'manage_options' ) )
						echo ' - <a data-comment-id="' . $comm->comment_ID . '" class="delete-comment-link" href="javascript:void(0)" style="color:red;">'.__( 'delete', 'collabpress' ). '</a>';
					?>
					</p>
					<p class="cp_comment_content"><?php echo $comm->comment_content ?></p>
				</div>
			</div>
		<?php
		endforeach;
	// No Comments
	else:
		echo '<div class="cp_task_comment"><p>'.__('No comments...', 'collabpress').'</p></div>';
	endif;

	echo '</div>';

	//check if email option is enabled
	$cp_options = cp_get_options();
	$email_notifications = isset( $options['email_notifications'] ) && 'enabled' === $options['email_notifications'];

	echo '<form id="task-comment-form" action="'.cp_clean_querystring().'" method="post">';
		wp_nonce_field( 'add-task-comment', 'add_task_comment_nonce' );
		?>
		<p><label for="cp-comment-content"><?php _e('Leave a Comment: ', 'collabpress') ?></label></p>
		<?php wp_editor( '', 'cp-comment-content' ); ?>
		<p><?php _e('Notify via Email?', 'collabpress'); ?> <input type="checkbox" name="notify" <?php checked( $email_notifications ); ?> /></p>
		<?php
		echo '<p class="submit"><input class="button-primary" type="submit" name="cp-add-comment" value="'.__( 'Submit', 'collabpress' ).'"/></p>';

	echo '</form>';
}

// CollabPress Calendar
function cp_draw_calendar( $args = array() ) {
	global $cp;

	$defaults = array(
		'month' => NULL,
		'year' => NULL,
		'project' => NULL
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args );

	//TODO: If no project was given, only show projects user has access to

	echo '<div id="cp-calendar-wrap">';

	$month = ( ! empty( $_GET['month'] ) ) ? absint($_GET['month']) : $month = date( 'n' );
	$year = ( ! empty( $_GET['year'] ) ) ? absint($_GET['year']) : $year = date( 'Y' );

	$monthName= date("F",mktime(0,0,0,$month,1,2000));
	echo '<h3 style="clear:both; text-align: center">'.$monthName.' - '.$year.'</h3>';

	// Previous Link
	if ($month == 1) :
		$previousMonth = 12;
		$previousYear = $year - 1;
	else :
		$previousMonth = $month - 1;
		$previousYear = $year;
	endif;
	$previousmonthName= date("F",mktime(0,0,0,$previousMonth,1,2000)) . ', ' . $previousYear;
	$calendar_previous_month_link = cp_get_calendar_permalink(
		array(
			'project' => $project,
			'month' => $previousMonth,
			'year' => $previousYear,
		)
	);

	echo '<a title="" class="cp_previous_month" href="' . $calendar_previous_month_link . '">'.$previousmonthName.'</a>';

	// Next Link
	if ($month == 12) :
		$nextMonth = 1;
		$nextYear = $year + 1;
	else :
		$nextMonth = $month + 1;
		$nextYear = $year;
	endif;
	$nextmonthName= date("F",mktime(0,0,0,$nextMonth,1,2000)) . ', ' . $nextYear;
	$calendar_next_month_link = cp_get_calendar_permalink(
		array(
			'project' => $project,
			'month' => $nextMonth,
			'year' => $nextYear,
		)
	);
	echo '<a title="" class="cp_next_month" href="' . $calendar_next_month_link . '">'.$nextmonthName.'</a>';

	/* draw table */
	$calendar = '<table cellpadding="0" cellspacing="0" class="calendar">';

	/* table headings */
	$headings = array( __('Sunday', 'collabpress'),
		__('Monday', 'collabpress'),
		__('Tuesday', 'collabpress'),
		__('Wednesday', 'collabpress'),
		__('Thursday', 'collabpress'),
		__('Friday', 'collabpress'),
		__('Saturday', 'collabpress')
	);

	$calendar .= '<tr class="calendar-row" valign="top"><td class="calendar-day-head">'.implode('</td><td class="calendar-day-head">',$headings).'</td></tr>';

	/* days and weeks vars now ... */
	$running_day = date(
		'w',
		mktime( 0, 0, 0, $month, 1, $year )
	);
	$days_in_month = date('t',mktime(0,0,0,$month,1,$year));
	$days_in_this_week = 1;
	$day_counter = 0;
	$dates_array = array();

	/* row for week one */
	$calendar .= '<tr class="calendar-row" valign="top">';

	/* print "blank" days until the first of the current week */
	for($x = 0; $x < $running_day; $x++):
		$calendar .= '<td class="calendar-day-np">&nbsp;</td>';
		$days_in_this_week++;
	endfor;

	/* keep going with days.... */
	for ($list_day = 1; $list_day <= $days_in_month; $list_day++ ):
		$calendar .= '<td class="calendar-day">';
			/* add in the day number */
			$calendar .= '<div class="day-number">'.$list_day.'</div>';
			$formatDate = $year . '-' . str_pad( $month, 2, 0, STR_PAD_LEFT ) . '-' .
				str_pad( $list_day, 2, 0, STR_PAD_LEFT ) . ' 00:00:00';
			// Get Task Lists
			$tasks_args = apply_filters(
				'cp_calendar_tasks_args',
				array(
					'post_type' => 'cp-tasks',
					'meta_query' => array(
						array(
							 'key' => '_cp-task-due',
							 'value' => $formatDate,
						)
					),
					'showposts' => '-1'
				)
			);

			if ( $project ) {
				$tasks_args['meta_query'][] = array(
					'key' => '_cp-project-id',
				 	'value' => $project,
				);
			}

			$tasks_query = new WP_Query( $tasks_args );

			// WP_Query();
			if ( $tasks_query->have_posts() ) :
		    while( $tasks_query->have_posts() ) : $tasks_query->the_post();

				// Project ID
				$projectID = get_post_meta( get_the_ID(), '_cp-project-id', true );
				$task_user_id = get_post_meta( get_the_ID(), '_cp-task-assign', true );
				$task_status = get_post_meta( get_the_ID(), '_cp-task-status', true );

				if ($task_status == 'open') :
					$calendar .= '<p><a href="' . get_permalink( get_the_ID() ) .'">' . get_avatar( $task_user_id, 32 ) . ' ' . get_the_title() . '</a></p>';
				endif;

		    endwhile;
			wp_reset_query();
			else :
			endif;

			$calendar .= str_repeat('<p>&nbsp;</p>',2);

		$calendar .= '</td>';
		if($running_day == 6):
			$calendar .= '</tr>';
			if(($day_counter+1) != $days_in_month):
				$calendar .= '<tr class="calendar-row" valign="top">';
			endif;
			$running_day = -1;
			$days_in_this_week = 0;
		endif;
		$days_in_this_week++; $running_day++; $day_counter++;
	endfor;

	/* finish the rest of the days in the week */
	if($days_in_this_week < 8):
		for($x = 1; $x <= (8 - $days_in_this_week); $x++):
			$calendar .= '<td class="calendar-day-np">&nbsp;</td>';
		endfor;
	endif;

	/* final row */
	$calendar .= '</tr>';

	/* end the table */
	$calendar .= '</table>';

	/* all done, return result */
	echo $calendar;

	echo '</div>';
}


function cp_get_calendar_permalink( $args = array() ) {
	$defaults = array(
		'project' => NULL,
		'month' => NULL,
		'year' => NULL
	);

	$args = wp_parse_args( $args, $defaults );

	extract( $args );

	$link = COLLABPRESS_DASHBOARD;
	$link = add_query_arg( array( 'view' => 'calendar' ), $link );

	if ( $project )
		$link = add_query_arg( array( 'project' => $project ), $link );

	if ( $month )
		$link = add_query_arg( array( 'month' => $month ), $link );

	if ( $year )
		$link = add_query_arg( array( 'year' => $year ), $link );

	return apply_filters( 'cp_calendar_permalink', $link, $project, $year, $month );
}

	function cp_calendar_permalink( $args = array() ) {
		echo cp_get_calendar_permalink( $args );
	}

function cp_get_activity_permalink() {
	$link = COLLABPRESS_DASHBOARD;
	$link = add_query_arg( array( 'view' => 'activity' ), $link );

	return $link;
}

	function cp_activity_permalink( $args = array() ) {
		echo cp_get_activity_permalink( $args );
	}

// Display Icon
function cp_screen_icon($screen = '') {
	global $current_screen, $typenow;

	if ( empty($screen) )
		$screen = $current_screen;
	elseif ( is_string($screen) )
		$name = $screen;

	$class = 'icon32';

	if ( empty($name) ) {
		if ( !empty($screen->parent_base) )
			$name = $screen->parent_base;
		else
			$name = $screen->base;

		if ( 'edit' == $name && isset($screen->post_type) && 'page' == $screen->post_type )
			$name = 'edit-pages';

		$post_type = '';
		if ( isset( $screen->post_type ) )
			$post_type = $screen->post_type;
		elseif ( $current_screen == $screen )
			$post_type = $typenow;
		if ( $post_type )
			$class .= ' ' . sanitize_html_class( 'icon32-posts-' . $post_type );
	}
	return '<span id="icon-'.$name.'" class="'.$class.'"></span>';
}

/**
 * Return the ID of the displayed task.
 */
function cp_get_the_task_ID() {
	global $cp;
	if ( ! empty( $cp->task->ID ) )
		return $cp->task->ID;
	else
		return false;
}

/**
 * Return the description of the displayed task.
 */
function cp_get_the_task_description() {
	global $cp;
	if ( ! empty( $cp->task->post_title ) )
		return $cp->task->post_title;
	else
		return false;
}

function cp_get_the_task_due_date() {
	global $cp;
	if ( ! empty( $cp->task->ID ) )
		$task_id = $cp->task->ID;
	else
		return false;
	return cp_get_task_due_date( $task_id );

}

function cp_get_task_due_date( $task_id ) {
	$due_date_mysql = cp_get_task_due_date_mysql( $task_id );
	$unix_timestamp = strtotime( $due_date_mysql );
	$cp_options = cp_get_options();
	return date( cp_get_date_format(), $unix_timestamp );
}

function cp_get_task_due_date_mysql( $task_id ) {
	return get_post_meta( $task_id, '_cp-task-due', true );
}

// Get URL
function cp_get_url( $ID = NULL, $type = NULL ) {
    if ( $type == 'task' || $type == 'comment' ) :
		$cp_project_id = get_post_meta( $ID, '_cp-project-id', true );
		$cp_url = COLLABPRESS_DASHBOARD .'&project=' .$cp_project_id .'&task=' .absint( $ID );
	elseif( $type =="task list" ) :
		$cp_project_id = get_post_meta( $ID, '_cp-project-id', true );
		$cp_url = COLLABPRESS_DASHBOARD .'&project=' .$cp_project_id .'&task-list=' .absint( $ID );
    elseif ( $type == 'project' ) :
		$cp_url = COLLABPRESS_DASHBOARD.'&project=' .absint( $ID );
    endif;

    // Constructs a custom filter for each type. Annoying, but this is how the CP-BP filters work
    $filter_name = 'task list' == $type ? 'cp_task_list_link' : 'cp_' . $type . '_link';

    return apply_filters( $filter_name, $cp_url, $ID );
}

// Validate Date
function cp_validate_date( $value = NULL ) {
	return preg_match( '`^\d{1,2}/\d{1,2}/\d{4}$`' , $value );
}

/**
 * Check user permissions
 */
function cp_check_permissions( $type = NULL ) {
    $cp_options = cp_get_options();
    $cp_settings_user_role = ( isset( $options[$type] ) ) ? esc_attr( $options[$type] ) : 'manage_options';

    // Filter so that BP-compatibility (and other plugins) can modify
    $cp_settings_user_role = apply_filters( 'cp_settings_user_role', $cp_settings_user_role, $type );
    if ( $cp_settings_user_role == 'all' )
        return true;
	else if ( current_user_can( $cp_settings_user_role ) )
        return true;
    return false;
}

// Clean Querystring
function cp_clean_querystring() {
	$cp_cleaned_querystring = $_SERVER['REQUEST_URI'];
	parse_str($_SERVER['QUERY_STRING'], $cp_querystring);
	foreach ($cp_querystring as $key => $cp_cleaned_query_key) {
		$cp_clean_query_array = array(
			'cp-delete-project-id',
			'cp-delete-task-list-id',
			'cp-delete-task-id',
			'cp-delete-comment-id',
			'_wpnonce'
		);
		if (in_array($key, $cp_clean_query_array))
			$cp_cleaned_querystring = remove_query_arg($key, $cp_cleaned_querystring);
	}
	return $cp_cleaned_querystring;
}

//verify user has access to view a project
function cp_check_project_permissions( $user_id = 1, $project_id = 1 ) {

    $cp_project_users = get_post_meta( $project_id, '_cp-project-users', true );
    $has_access = false;

    if ( is_array( $cp_project_users ) ) {
	if ( in_array( $user_id, $cp_project_users ) ) {
	    $has_access = true;
	}
    }else{
	//old projects don't have users set so allow access
	$has_access = true;
    }

    return apply_filters( 'cp_check_project_permissions', $has_access, $user_id, $project_id, $cp_project_users );
}

/**
 * Get a task's project ID
 *
 * @since 1.3
 * @todo If and when task lists are made into optional taxonomies, this'll have
 *   to be reworked
 *
 * @param int $task_id
 * @return int|bool Project id if one is found, otherwise false
 */
function cp_get_task_project_id( $task_id = 0 ) {
	return get_post_meta( $task_id, '_cp-project-id', true );
}

/**
 * Get a task's task list ID
 *
 * @since 1.3
 * @todo If and when task lists are made into optional taxonomies, this'll have
 *   to be reworked
 *
 * @param int $task_id
 * @return int|bool Task list id if one is found, otherwise false
 */
function cp_get_tasklist_project_id( $tasklist_id = 0 ) {
	$tasklist_id = absint( $tasklist_id );
	$project_id = get_post_meta( $tasklist_id, '_cp-project-id', true );

	if ( $project_id ) {
		$project_id = absint( $project_id );
	} else {
		$project_id = false;
	}

	return $project_id;
}

/**
 * Get a task list's project ID
 *
 * @since 1.3
 * @todo If and when task lists are made into optional taxonomies, this'll have
 *   to be reworked
 *
 * @param int $task_list_id
 * @return int|bool Project id if one is found, otherwise false
 */
function cp_get_task_tasklist_id( $task_id = 0 ) {
	$task_id = absint( $task_id );
	$tasklist_id = get_post_meta( $task_id, '_cp-task-list-id', true );

	if ( $tasklist_id ) {
		$tasklist_id = absint( $tasklist_id );
	} else {
		$tasklist_id = false;
	}

	return $tasklist_id;
}

/**
 * Utility function for calling up CP's options array, providing any necessary defaults
 *
 * @package CollabPress
 * @since 1.2
 *
 * @return array $options
 */
function cp_get_options() {
	$options = get_option('cp_options');

	$saved_options = $options;

	// BP settings
	if ( function_exists( 'bp_is_active' ) ) {
		// Set some defaults if necessary
		if ( empty( $options['bp'] ) ) {
			$options['bp'] = array();
		}

		// Default group settings
		if ( bp_is_active( 'groups' ) ) {
			$groups_defaults = array(
				'groups_enabled' 		=> 'enabled',
				'groups_admins_can_disable' 	=> 'allow',
				'groups_admins_can_customize' 	=> 'allow',
				'groups_default_tab_name' 	=> __( 'Projects', 'collabpress' ),
				'groups_default_tab_slug' 	=> sanitize_title( __( 'Projects', 'collabpress' ) )
			);

			foreach ( $groups_defaults as $key => $value ) {
				if ( !isset( $options['bp'][$key] ) )
					$options['bp'][$key] = $value;
			}
		}
	}

	return apply_filters( 'cp_get_options', $options, $saved_options );
}

//limit string length function
function cp_limit_length( $strtolimit=null, $limit=50 ) {

	if ( strlen( $strtolimit ) > $limit ) {
		$strtolimit = substr( $strtolimit, 0, $limit ) .'...';
	}

	return $strtolimit;
}

require_once( COLLABPRESS_PLUGIN_DIR . 'includes/template-tags.php' );

add_action( 'wp', 'cp_enqueue_styles_and_scripts' );
add_action( 'admin_init', 'cp_enqueue_styles_and_scripts' );

/**
 * Load CollabPress styles and scripts.
 *
 */
function cp_enqueue_styles_and_scripts() {
	if ( ! is_collabpress_page() )
		return;

	wp_enqueue_script( 'jquery-ui' );
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_style( 'cp_jquery-ui',
	                  COLLABPRESS_PLUGIN_URL . 'includes/css/jquery-ui/jquery-ui-1.8.16.custom.css' );
	wp_enqueue_style( 'collabpress-new', COLLABPRESS_PLUGIN_URL . 'includes/css/collabpress.css' );
	wp_enqueue_style( 'collabpress-fonts', 'http://fonts.googleapis.com/css?family=Roboto+Condensed:300italic,400italic,700italic,300,400,700' );
	wp_enqueue_style( 'cp_admin', COLLABPRESS_PLUGIN_URL . 'includes/css/admin.css' );

	wp_enqueue_style( 'colorbox-css', COLLABPRESS_PLUGIN_URL . 'includes/css/colorbox.css' );
	wp_enqueue_script( 'colorbox', COLLABPRESS_PLUGIN_URL . 'includes/js/jquery.colorbox-min.js', array( 'jquery') );

	wp_enqueue_script( 'cp-task-list', COLLABPRESS_PLUGIN_URL . 'includes/js/task_list.js', array( 'jquery', 'jquery-ui-sortable' ) );

	wp_enqueue_style( 'jquery-ui', COLLABPRESS_PLUGIN_URL . 'includes/css/jquery-ui/jquery-ui-1.8.16.custom.css' );

}

/**
 * Check if we're on a CollabPress page.
 *
 * If a slug is included, check if we're on a specific CollabPress page.
 */
function is_collabpress_page( $slug = '' ) {

	global $post;
	// If the page is not set
	if (
		( empty( $_REQUEST['page'] ) || $_REQUEST['page'] != 'collabpress-dashboard' )
		&&
		(
			( ! empty( $post->post_content ) && strpos( $post->post_content, '[collabpress]' ) === FALSE )
			|| empty( $post->post_content )
		)
	   )
		return apply_filters( 'is_collabpress_page', false );

	// Default, if we're just checking that we're on a CollabPress page
	if ( ! $slug )
		return apply_filters( 'is_collabpress_page', true );
	$return = false;
	if ( ! empty( $_REQUEST['project'] ) ) {
		if ( ! empty( $_REQUEST['view'] ) ) {
			if ( $slug == 'project-calendar'
			  && $_REQUEST['view'] == 'calendar' )
				$return = true;
			if ( $slug == 'project-tasks'
			  && $_REQUEST['view'] == 'tasks' )
				$return = true;
			if ( $slug == 'project-files'
			  && $_REQUEST['view'] == 'files' )
				$return = true;
			if ( $slug == 'project-users'
			  && $_REQUEST['view'] == 'users' )
				$return = true;
		} else {
			if ( $slug == 'project-overview' )
				$return = true;
		}
	} else if ( ! empty( $_REQUEST['task'] ) ) {
		if ( $slug == 'task' )
			$return = true;
	} else {
		if ( ! empty( $_REQUEST['view'] ) ) {
			if ( $slug == 'calendar' && $_REQUEST['view'] == 'calendar' )
				$return = true;
			if ( $slug == 'activity' && $_REQUEST['view'] == 'activity' )
				$return = true;
		} else {
			if ( $slug == 'dashboard' )
				$return = true;
		}
	}

	if ( ! isset( $return ) )
		$return = false;
	return apply_filters( 'is_collabpress_page', $return );
}

function cp_get_the_task_priority() {
	global $cp;
	if ( ! empty( $cp->task->ID ) )
		$task_id = $cp->task->ID;
	else
		return false;
	return cp_get_task_priority( $task_id );
}

function cp_get_task_priority( $task_id ) {
	return get_post_meta( $task_id, '_cp-task-priority', true );
}

function cp_set_project_description( $project_id, $description ) {
	if ( current_user_can( 'unfiltered_html' ) == false )
		$description = wp_filter_kses( $description );

	update_post_meta( $project_id, '_cp-project-description', $description );
}

function cp_get_project_description( $project_id ) {
	return get_post_meta( $project_id, '_cp-project-description', true );
}

function cp_add_user_to_project( $project_id, $user_id ) {
	global $wpdb, $cp;
	$row_exists = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*)
			FROM {$cp->tables->project_users}
			WHERE project_id = %d
			AND user_id = %d",
			$project_id,
			$user_id
		)
	);
	if ( $row_exists )
		return true;

	return $wpdb->insert(
		$cp->tables->project_users,
		array(
			'project_id' => $project_id,
			'user_id' => $user_id,
		),
		array(
			'%d',
			'%d',
		)
	);
}

function cp_remove_user_from_project( $project_id, $user_id ) {
	global $wpdb, $cp;
	$row_exists = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*)
			FROM {$cp->tables->project_users}
			WHERE project_id = %d
			AND user_id = %d",
			$project_id,
			$user_id
		)
	);
	if ( ! $row_exists )
		return true;

	return $wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$cp->tables->project_users}
			WHERE project_id = %d
			AND user_id = %d",
			$project_id,
			$user_id
		)
	);
}

function cp_get_project_users( $project_id = 0 ) {
	global $wpdb, $cp;
	if ( ! $project_id )
		$project_id = $cp->project->ID;

	$users = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->users} as users
			LEFT JOIN {$cp->tables->project_users} cp_project_users
				ON users.ID = cp_project_users.user_id
			WHERE cp_project_users.project_id = %d",
			$project_id
		)
	);
	return $users;
}

function cp_user_is_in_project( $project_id, $user_id ) {
	global $wpdb, $cp;
	return $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*)
			FROM {$cp->tables->project_users}
			WHERE project_id = %d
			AND user_id = %d",
			$project_id,
			$user_id
		)
	);
}


function cp_get_user_assigned_to_task( $task_id = 0 ) {
	if ( ! $task_id )
		$task_id = cp_get_the_task_ID();
	$user_id = get_post_meta( $task_id, '_cp-task-assign', true );
	return new WP_User( $user_id );
}


function cp_add_task_to_task_list( $task_id, $task_list_id ) {
	return update_post_meta( $task_id, '_cp-task-list-id', $task_list_id );
}

add_action( 'wp_head', 'cp_define_ajaxurl' );

function cp_define_ajaxurl() {
	if ( is_collabpress_page() ) {
		?><script>var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';</script><?php
	}
}

function cp_update_task_status( $task_id, $status = 'open' ) {
	return update_post_meta( $task_id, '_cp-task-status', $status );
}

function cp_get_task_status( $task_id ) {
	return get_post_meta( $task_id, '_cp-task-status', true );
}


/**
 * Make a CollabPress permalink.
 *
 */
function cp_get_permalink( $args = array() ) {
	return apply_filters( 'cp_permalink', add_query_arg( $args, COLLABPRESS_DASHBOARD ), $args );
}

add_filter( 'post_type_link', 'cp_filter_permalinks', 10, 4 );

/**
 * Filter the permalink for CollabPress post types.
 */
function cp_filter_permalinks( $link, $post, $leavename, $sample ) {
	switch ( $post->post_type ) {
		case 'cp-projects' :
			$link = cp_get_project_permalink( $post->ID, $post );
			break;

		case 'cp-tasks' :
			$link = cp_get_task_permalink( $post->ID, $post );
			break;
	}

	return $link;
}

function cp_get_project_permalink( $project_id = false, $project = false ) {
	global $post;

	// Only run another query if we have to
	if ( $project_id && !$project && ( ( isset( $post->ID ) && $project_id != $post->ID ) || !isset( $post->ID ) ) ) {
		$project = get_post( $project_id );
	} else if ( $project_id && !$project && isset( $post->ID ) && $project_id == $post->ID ) {
		$project = $post;
	}

	if ( empty( $project ) )
		return false;

	// Check the post type
	if ( 'cp-projects' != $project->post_type )
		return false;

	// Assemble the permalink
	$link = cp_get_permalink( array( 'project' => $project_id ) );

	return $link;
}

function cp_get_task_permalink( $task_id = false, $task = false ) {
	global $post;

	// Only run another query if we have to
	if ( $task_id && !$task && ( ( isset( $post->ID ) && $task_id != $post->ID ) || !isset( $post->ID ) ) ) {
		$task = get_post( $task_id );
	} else if ( $task_id && !$task && isset( $post->ID ) && $task_id == $post->ID ) {
		$task = $post;
	}

	if ( empty( $task ) )
		return false;

	// Check the post type
	if ( 'cp-tasks' != $task->post_type )
		return false;

	// Assemble the permalink
	$link = cp_get_permalink( array( 'task' => $task_id ) );

	return $link;
}


/**
 * Custom sorting function for task lists and tasks.
 */
function cp_compare_tasks_and_task_lists( $a, $b ) {
	if ( $a->menu_order == $b->menu_order )
		return 0;
	else
		return ( $a->menu_order < $b->menu_order ) ? 0 : 1;
}
/**
 * Returns the menu formatted to edit.
 *
 * @since 1.3
 *
 * @param string $menu_id The ID of the menu to format.
 * @return string|WP_Error $output The menu formatted to edit or error object on failure.
 */
function cp_output_project_nested_task_lists_and_tasks_html_for_sort( $project_id = 0 ) {
	$tasks_without_task_lists = get_posts( array(
		'posts_per_page' => -1,
		'post_type' => 'cp-tasks',
		'meta_query' => array(
			array(
				'key' => '_cp-project-id',
				'value' => $project_id,
			),
			array(
				'key' => '_cp-task-list-id',
				'value' => 0,
			),
		)
	) );
	$task_lists =  get_posts( array(
		'posts_per_page' => -1,
		'post_type' => array( 'cp-task-lists' ),
		'meta_query' => array(
			array(
				'key' => '_cp-project-id',
				'value' => $project_id,
			),
		)
	) );

	$tasks_and_task_lists = array_merge( $tasks_without_task_lists, $task_lists );
	uasort( $tasks_and_task_lists, 'cp_compare_tasks_and_task_lists' );
	$tasks_and_task_lists = array_values( $tasks_and_task_lists );

	$result = '<div id="menu-instructions" class="post-body-plain';
	$result .= ( ! empty($menu_items) ) ? ' menu-instructions-inactive">' : '">';
	if ( empty( $tasks_and_task_lists ) )
		$result .= '<p>' . __('Next, add your first task in this project.') . '</p>';
	$result .= '</div>';
	$result .= '<ul class="menu" id="menu-to-edit"> ';


	$hide_completed_tasks_style = get_user_option( 'display_completed_tasks' ) ? 'style="display:none"' : '';

	// Output the HTML for each item.
	// Hacked from Walker_Nav_Menu_Edit::start_el()
	foreach ( $tasks_and_task_lists as $item ) {
		ob_start();
		$item_id = $item->ID;
		$title = $item->post_title;
		$task_status = cp_get_task_status( $item->ID );
		?>
		<li id="menu-item-<?php echo $item_id; ?>" class="menu-item menu-item-depth-0 <?php echo $task_status; ?> <?php if ( $task_status == 'complete' ) echo $hide_completed_tasks_style; ?>">
			<dl class="menu-item-bar">
				<dt class="menu-item-handle">
					<?php if ( $item->post_type == 'cp-tasks' ) : ?>
					<input type="hidden" id="item-complete-status-change-nonce_<?php echo $item_id; ?>" value="<?php echo wp_create_nonce( 'item-complete-status-change_' . $item_id ) ?>" />
					<input class="item-completed" type="checkbox" <?php checked( 'complete', $task_status ); ?> />
					<?php endif; ?>
					<span class="item-title">
						<?php if ( $item->post_type == 'cp-tasks' ) : // for now, only display a link for tasks. ?>
						<a href="<?php echo get_permalink( $item_id ); ?>"><?php echo esc_html( $title ); ?></a>
						<?php else: // add a link to task lists if we make a template for them. ?>
						<?php echo esc_html( $title ); ?>
						<?php endif; ?>
					</span>
					<span class="item-controls">
						<a href="javascript:void(0);" class="delete-task" data-id="<?php echo $item_id; ?>">delete</a>
						<input type="hidden" id="delete_task_nonce_<?php echo $item_id ?>" value="<?php echo wp_create_nonce( 'delete-task_' . $item_id ) ?>" />
					</span>
				</dt>
			</dl>

			<div class="menu-item-settings" id="menu-item-settings-<?php echo $item_id; ?>">

				<input class="menu-item-data-db-id" type="hidden" name="menu-item-db-id[<?php echo $item_id; ?>]" value="<?php echo $item_id; ?>" />
				<input class="menu-item-data-object-id" type="hidden" name="menu-item-object-id[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->object_id ); ?>" />
				<input class="menu-item-data-object" type="hidden" name="menu-item-object[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->object ); ?>" />
				<input class="menu-item-data-parent-id" type="hidden" name="menu-item-parent-id[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->menu_item_parent ); ?>" />
				<input class="menu-item-data-position" type="hidden" name="menu-item-position[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->menu_order ); ?>" />
				<input class="menu-item-data-type" type="hidden" name="menu-item-type[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->post_type ); ?>" />
			</div><!-- .menu-item-settings-->
			<ul class="menu-item-transport"></ul>
		<?php
		$task_list_tasks = get_posts( array(
			'posts_per_page' => -1,
			'post_type' => 'cp-tasks',
			'meta_query' => array(
				array(
					'key' => '_cp-project-id',
					'value' => $project_id,
				),
				array(
					'key' => '_cp-task-list-id',
					'value' => $item_id,
				),
			),
			'orderby' => 'menu_order',
			'order' => 'ASC',
		) );
		if ( ! empty( $task_list_tasks ) ) {
			foreach ( $task_list_tasks as $task ) {
				$title = $task->post_title;
				$task_status = cp_get_task_status( $task->ID );
				 ?>
				<li id="menu-item-<?php echo $task->ID; ?>" class="menu-item menu-item-depth-1 <?php echo $task_status; ?>">
					<dl class="menu-item-bar">
						<dt class="menu-item-handle">
							<input type="hidden" id="item-complete-status-change-nonce_<?php echo $task->ID; ?>" value="<?php echo wp_create_nonce( 'item-complete-status-change_' . $task->ID ) ?>" />
							<input class="item-completed" type="checkbox" <?php checked( 'complete', $task_status ); ?>>
							<span class="item-title"><a href="<?php echo get_permalink( $task->ID ); ?>"><?php echo esc_html( $title ); ?></a><span>
							<span class="item-controls">
								<a href="javascript:void(0);" class="delete-task" data-id="<?php echo $task->ID; ?>">delete</a>
								<input type="hidden" id="delete_task_nonce_<?php echo $task->ID ?>" value="<?php echo wp_create_nonce( 'delete-task_' . $task->ID ) ?>" />
							</span>
						</dt>
					</dl>

					<div class="menu-item-settings" id="menu-item-settings-<?php echo $task->ID; ?>">

						<input class="menu-item-data-db-id" type="hidden" name="menu-item-db-id[<?php echo $task->ID; ?>]" value="<?php echo $task->ID; ?>" />
						<input class="menu-item-data-object-id" type="hidden" name="menu-item-object-id[<?php echo $task->ID; ?>]" value="<?php echo esc_attr( $task->object_id ); ?>" />
						<input class="menu-item-data-object" type="hidden" name="menu-item-object[<?php echo $task->ID; ?>]" value="<?php echo esc_attr( $task->object ); ?>" />
						<input class="menu-item-data-parent-id" type="hidden" name="menu-item-parent-id[<?php echo $task->ID; ?>]" value="<?php echo esc_attr( $task->menu_item_parent ); ?>" />
						<input class="menu-item-data-position" type="hidden" name="menu-item-position[<?php echo $task->ID; ?>]" value="<?php echo esc_attr( $task->menu_order ); ?>" />
						<input class="menu-item-data-type" type="hidden" name="menu-item-type[<?php echo $task->ID; ?>]" value="<?php echo esc_attr( $task->post_type ); ?>" />
					</div><!-- .menu-item-settings-->
					<ul class="menu-item-transport"></ul>
			<?php
			}
		}
		$result .= ob_get_clean();
	}

	$result .= ' </ul> ';
	echo $result;
}

/**
 * Get the CollabPress setting for date format
 *
 * @uses cp_get_options()
 * @since 1.4
 */
function cp_get_date_format() {
	$cp_options = cp_get_options();
	if ( $cp_options['date_format'] == '\c\u\s\t\o\m' )
		$date_format = $cp_options['date_format_custom'];
	else
		$date_format = $cp_options['date_format'];
	return $date_format;
}

/**
 * Translate the CollabPress setting for date format
 * into the format required by jQuery datepicker.
 *
 * @uses cp_get_date_format()
 * @since 1.4
 */
function cp_translate_date_format_for_js_datepicker() {
	$date_format = cp_get_date_format();

	// clear out format characters that don't exist in the datepicker format dictionary
	$js_datepicker_format = preg_replace( '/[a-ce-iko-wA-CEG-LN-WXZ]/', '', $date_format );

	// replace character from PHP date format to the datepicker's format
	$js_datepicker_format = preg_replace(
		array( '/j/', '/d/', '/z/', '/z/', '/D/', '/l/', '/n/', '/m/', '/M/', '/F/', '/y/', '/Y/' ),
		array( 'd', 'dd', 'o', 'oo', 'D', 'DD', 'm', 'mm', 'M', 'MM', 'y', 'yy', ),
		$date_format
	);
	return $js_datepicker_format;
}

// Show Recent Activity
function cp_recent_activity($data = NULL) {

	// Get Current User
	global $current_user;
	get_currentuserinfo();

	// Get Activities
	$paged = (isset($_GET['paged'])) ? esc_html($_GET['paged']) : 1;

	// Load plugin options
	$cp_options = cp_get_options();

	// Check number of recent items to display
	$cp_num_recent = ( isset( $cp_options['num_recent_activity'] ) ) ? absint( $cp_options['num_recent_activity'] ) : 4;

	$activities_args = array( 'post_type' => 'cp-meta-data', 'showposts' => $cp_num_recent, 'paged' => $paged );
	$activities_query = new WP_Query( $activities_args );

	echo '<div class="cp-activity-list">';

	// WP_Query();
	if ( $activities_query->have_posts() ) :
	$activityCount = 1;
	while( $activities_query->have_posts() ) : $activities_query->the_post();
		    global $post;

		    if ( ($activityCount % 2) == 0 ) {
			    $row = " even";
		    } else {
			    $row = " odd";
		    }

		    // Avatar
		    $activityUser = get_post_meta($post->ID, '_cp-activity-author', true);
		    $activityUser = get_userdata($activityUser);
		    $activityAction = get_post_meta($post->ID, '_cp-activity-action', true);
		    $activityType = get_post_meta($post->ID, '_cp-activity-type', true);
		    $activityID = get_post_meta($post->ID, '_cp-activity-ID', true);

		    if ( $activityUser ) :
		    ?>

		    <div class="cp-activity-row <?php echo $row ?>">
			    <a class="cp-activity-author" title="<?php $activityUser->display_name ?>" href="<?php echo COLLABPRESS_DASHBOARD; ?>&user=<?php echo $activityUser->ID ?>"><?php echo get_avatar($activityUser->ID, 32) ?></a>
			    <div class="cp-activity-wrap">
			    <p class="cp-activity-description"><?php echo $activityUser->display_name . ' ' . $activityAction . ' ' . __('a', 'collabpress') . ' '. $activityType ?>: <a href="<?php echo cp_get_url( $activityID, $activityType ); ?>"><?php echo get_the_title( $activityID ); ?></a></p>
			    </div>
		    </div>

		    <?php
		    endif;
		    $activityCount++;
	endwhile;
	wp_reset_query();
	else :
		echo '<p>'.__( 'No Activities...', 'collabpress' ).'</p>';
	endif;

	// Pagination
	if ( $activities_query->max_num_pages > 1 ) {
		echo '<p class="cp_pagination">';
	    for ( $i = 1; $i <= $activities_query->max_num_pages; $i++ ) {
	        echo '<a href="'.COLLABPRESS_DASHBOARD.'&paged='.$i.'" '.(($paged == $i) ? 'class="active"' : '' ).'>'.$i.'</a> ';
	    }
	    echo '</p>';
	} ?>

	<style type="text/css">
		.cp-activity-list {
		    position: relative;
		}
		.cp-activity-row {
		    margin: 0;
		    overflow: hidden;
		    padding: 2px 10px;
		}
		.cp-activity-list .even {
		    background-color: #FFFFE0;
		}
		.cp-activity-list .cp-activity-author {
		    float: left;
		    margin: 5px 0;
		}
		.cp-activity-list .cp-activity-wrap {
		    margin: 6px 0;
		    overflow: hidden;
		    word-wrap: break-word;
		}
		.cp-activity-list p {
		    font-size: 11px;
		    margin: 6px 6px 8px;
		}
	</style>

	<?php echo '</div>';
}


add_action( 'init', 'cp_filters_init' );

function cp_filters_init() {
	if ( current_user_can( 'unfiltered_html' ) )
		add_filter( 'cp_comment_content', 'wp_kses_post' );
}