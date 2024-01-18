<?php

function register_lead_post_type() {
    $labels = [
        'name'               => 'Leads',
        'singular_name'      => 'Lead',
        'menu_name'          => 'Leads',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Lead',
        'edit_item'          => 'Edit Lead',
        'new_item'           => 'New Lead',
        'view_item'          => 'View Lead',
        'search_items'       => 'Search Leads',
        'not_found'          => 'No leads found',
        'not_found_in_trash' => 'No leads found in Trash',
    ];

    $args = [
        'labels'             => $labels,
        'public'             => true,
        'menu_icon'          => 'dashicons-calendar-alt',
        'supports'           => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'has_archive'        => true,
        'rewrite'            => ['slug' => 'leads'],
        'show_in_rest'       => true,
    ];

    register_post_type('lead', $args);
}
add_action('init', 'register_lead_post_type');

function register_lead_metabox() {
    add_meta_box(
        'lead_details',
        'Lead Details',
        'lead_details_callback',
        'lead',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'register_lead_metabox');

function lead_details_callback($post) {
    $email = get_post_meta($post->ID, 'email', true);
    $first_name = get_post_meta($post->ID, 'first_name', true);
    $last_name = get_post_meta($post->ID, 'last_name', true);
    $phone = get_post_meta($post->ID, 'phone', true);

    ?>
    <p>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo esc_attr($email); ?>"><br/>
    </p>
    <p>
        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($first_name); ?>"><br/>
    </p>
    <p>
        <label for="last_name">Last Name:</label>
        <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($last_name); ?>"><br/>
    </p>
    <p>
        <label for="phone">Phone:</label>
        <input type="text" id="phone" name="phone" value="<?php echo esc_attr($phone); ?>"><br/>
    </p>
    <?php
}

function save_lead_details($post_id) {
    if (isset($_POST['email'])) {
        update_post_meta($post_id, 'email', sanitize_email($_POST['email']));
    }
    if (isset($_POST['first_name'])) {
        update_post_meta($post_id, 'first_name', sanitize_text_field($_POST['first_name']));
    }
    if (isset($_POST['last_name'])) {
        update_post_meta($post_id, 'last_name', sanitize_text_field($_POST['last_name']));
    }
    if (isset($_POST['phone'])) {
        update_post_meta($post_id, 'phone', sanitize_text_field($_POST['phone']));
    }
}
add_action('save_post', 'save_lead_details');

function add_leads_columns($columns) {
    $columns['name'] = __('Name');
    $columns['phone'] = __('Phone');
    return $columns;
}
add_filter('manage_lead_posts_columns', 'add_leads_columns');

function lead_custom_columns($column, $post_id) {
    switch ($column) {
        case 'name':
            $first_name = get_post_meta($post_id, 'first_name', true);
            $last_name = get_post_meta($post_id, 'last_name', true);
            echo esc_html($first_name . ' ' . $last_name);
            break;
        case 'phone':
            $phone = get_post_meta($post_id, 'phone', true);
            echo esc_html($phone);
            break;
    }
}
add_action('manage_lead_posts_custom_column', 'lead_custom_columns', 10, 2);

function add_lead_from_form() {
    check_ajax_referer('add_lead_nonce_action', 'add_lead_nonce');

    $lead_id = wp_insert_post(array(
        'post_type' => 'lead',
        'post_title' => sanitize_text_field($_POST['event_title'] . ' - ' . $_POST['first_name']),
        'post_status' => 'publish',
    ));

    global $wpdb;

    if ($lead_id) {
        $meta_values = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone'])
        );

        foreach ($meta_values as $key => $value) {
            $wpdb->replace(
                $wpdb->postmeta,
                array(
                    'post_id' => $lead_id,
                    'meta_key' => $key,
                    'meta_value' => $value
                ),
                array(
                    '%d',
                    '%s',
                    '%s'
                )
            );
        }
    }

    wp_send_json(array('success' => true, 'message' => 'Lead added successfully'));
}
add_action('wp_ajax_add_lead_from_form', 'add_lead_from_form');
add_action('wp_ajax_nopriv_add_lead_from_form', 'add_lead_from_form');

?>