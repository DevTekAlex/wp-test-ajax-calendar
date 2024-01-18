<?php

function enqueue_custom_script() {
    wp_enqueue_script('fullcalendar-main', get_template_directory_uri() . '/js/index.global.min.js', null, null, true);
    wp_enqueue_script('logic-calendar', get_template_directory_uri() . '/js/logic-calendar.js', array(), false, true);

    wp_localize_script('logic-calendar', 'scriptParams', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('add_lead_nonce_action')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_custom_script');

function register_events_post_type() {
    $labels = [
        'name'               => 'Events',
        'singular_name'      => 'Event',
        'menu_name'          => 'Events',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Event',
        'edit_item'          => 'Edit Event',
        'new_item'           => 'New Event',
        'view_item'          => 'View Event',
        'search_items'       => 'Search Events',
        'not_found'          => 'No events found',
        'not_found_in_trash' => 'No events found in Trash',
    ];

    $args = [
        'labels'             => $labels,
        'public'             => true,
        'menu_icon'          => 'dashicons-calendar-alt',
        'supports'           => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'has_archive'        => true,
        'rewrite'            => ['slug' => 'events'],
        'show_in_rest'       => true,
    ];

    register_post_type('events', $args);
}
add_action('init', 'register_events_post_type');

function register_events_metabox() {
    add_meta_box(
        'events_details',
        'Event Details',
        'events_details_callback',
        'events',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'register_events_metabox' );

function events_details_callback( $post ) {
    $start_date = get_post_meta( $post->ID, 'events_start_date', true );
    $end_date = get_post_meta( $post->ID, 'events_end_date', true );
    $image = get_post_meta( $post->ID, 'events_image', true );

    wp_nonce_field( 'events_details_nonce', 'events_details_nonce_field' );

    ?>
    <p>
        <label for="events_start_date">Start Date:</label>
        <input type="date" id="events_start_date" name="events_start_date" value="<?php echo esc_attr( $start_date ); ?>">
    </p>
    <p>
        <label for="events_end_date">End Date:</label>
        <input type="date" id="events_end_date" name="events_end_date" value="<?php echo esc_attr( $end_date ); ?>">
    </p>
    <p>
        <label for="events_image">Image:</label>
        <input type="hidden" id="events_image" name="events_image" value="<?php echo esc_url( $image ); ?>">
        <input type="button" id="events_image_upload" class="button" value="Upload Image">
        <div id="events_image_preview"><?php if ( $image ) { echo '<img src="' . esc_url( $image ) . '" alt="Event Image">'; } ?></div>
    </p>
    <?php
}

function save_events_details( $post_id ) {
    if ( ! isset( $_POST['events_details_nonce_field'] ) || ! wp_verify_nonce( $_POST['events_details_nonce_field'], 'events_details_nonce' ) ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( get_post_type( $post_id ) != 'events' ) {
        return;
    }

    if ( isset( $_POST['events_start_date'] ) ) {
        $start_date = sanitize_text_field( $_POST['events_start_date'] );
    }

    if ( isset( $_POST['events_end_date'] ) ) {
        $end_date = sanitize_text_field( $_POST['events_end_date'] );
    }

    if ( isset( $_POST['events_image'] ) ) {
        $image = esc_url_raw( $_POST['events_image'] );
    }

    update_post_meta( $post_id, 'events_start_date', $start_date );
    update_post_meta( $post_id, 'events_end_date', $end_date );
    update_post_meta( $post_id, 'events_image', $image );
}
add_action( 'save_post', 'save_events_details' );

function enqueue_events_media_script() {
    $screen = get_current_screen();
    if ( $screen->post_type == 'events' ) {
        wp_enqueue_media();
        wp_enqueue_script( 'events-media-script', get_template_directory_uri() . '/js/events-media-script.js', array( 'jquery' ), false, true );
    }
}
add_action( 'admin_enqueue_scripts', 'enqueue_events_media_script' );

function crop_events_image( $post_id ) {
    if ( get_post_type( $post_id ) == 'events' ) {
        $image_url = get_post_meta( $post_id, 'events_image', true );

        $image_path = get_attached_file( attachment_url_to_postid( $image_url ) );

        $editor = wp_get_image_editor( $image_path );

        if ( ! is_wp_error( $editor ) ) {
            $editor->resize( 400, 250, true );
            $resized_image = $editor->save( $image_path );
        }
    }
}

add_action('save_post', 'crop_events_image', 10, 1);

function get_events_ajax() {
    $query_args = [
        'post_type' => 'events',
        'posts_per_page' => -1,
    ];

    $query = new WP_Query($query_args);
    $events = [];

    while ($query->have_posts()) {
        $query->the_post();
        $start_date = get_post_meta(get_the_ID(), 'events_start_date', true);
        $end_date = get_post_meta(get_the_ID(), 'events_end_date', true);

        $events[] = [
            'id' => get_the_ID(),
            'title' => get_the_title(),
            'start' => $start_date,
            // 'end' => $end_date,
        ];
    }

    wp_reset_postdata();

    wp_send_json($events);
}

add_action('wp_ajax_get_events', 'get_events_ajax');
add_action('wp_ajax_nopriv_get_events', 'get_events_ajax');

function get_event_details_ajax() {
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

    $event_data = get_post($event_id);

    $start_date = get_post_meta($event_id, 'events_start_date', true);
    $end_date = get_post_meta($event_id, 'events_end_date', true);
    $image = get_post_meta($event_id, 'events_image', true);


    $response = [
        'title' => $event_data->post_title,
        'img' =>  $image,
        'start' => $start_date,
        'end' => $end_date
    ];

    wp_send_json($response);
}

add_action('wp_ajax_get_event_details', 'get_event_details_ajax');
add_action('wp_ajax_nopriv_get_event_details', 'get_event_details_ajax');
?>