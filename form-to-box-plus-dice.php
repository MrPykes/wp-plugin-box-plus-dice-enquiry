<?php

/**
 * Plugin Name: Form to Box plus Dice
 * Description: Get the details of the form and insert it to box plus dice.
 * Author: dc dev
 * Version: 1.0.0
 *
 * Text Domain: box-plus-dice
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('Box_Plus_Dice_Path', plugin_dir_url(__FILE__));


/**
 * Register a custom menu page.
 */
function wpdocs_register_my_custom_menu_page()
{
    add_menu_page(
        __('Box plus Dice', 'box-plus-dice'),
        'Box plus Dice',
        'manage_options',
        'box-plus-dice',
        'box_plus_dice_callback',
        'dashicons-insert-after',
        6
    );

    add_submenu_page(
        'box-plus-dice',
        __('Settings', 'box-plus-dice'),
        'Settings',
        'manage_options',
        'box-plus-dice-settings',
        'box_plus_dice_settings_callback'
    );
}
add_action('admin_menu', 'wpdocs_register_my_custom_menu_page');

require_once dirname(__FILE__) . '/includes/class-box-plus-dice-activator.php';
require_once dirname(__FILE__) . '/includes/classes/class-box-plus-dice-form-table.php';

/**
 * Display a custom menu page
 */

function box_plus_dice_callback()
{
    $title = '';
    $id = '';
    if (isset($_GET['p'])) {
        $id = $_GET['p'];
        $form = get_post($id);
        $title = $form->post_title;
        $id = $form->ID;
    }
?>
    <div class="box-plus-dice-forms">
        <div class="box-plus-dice-form-insert">
            <h2>Add New Form</h2>
            <form action="<?= esc_url(admin_url('admin-post.php')); ?>" method="POST">
                <div class="box-plus-dice-form-name">
                    <label for="box-plus-dice-form-name"><?= esc_html_e('Form Name: ', 'box-plus-dice') ?></label>
                    <input name="box-plus-dice-form-name" id="box-plus-dice-form-name" value="<?= $title ?>">
                    <input type="hidden" name="box-plus-dice-form-id" value="<?= $id ?>">
                </div>

                <input type="hidden" name="action" value="box_plus_dice_add_new_form">
                <input type="submit" value="Submit" class="box-plus-dice-form-submit">
            </form>
        </div>
        <div class=" box-plus-dice-form-table">
            <form method="POST">
                <?php
                $events_obj = new Box_Plus_Dice_FOrm_Table();
                $events_obj->prepare_items();
                $events_obj->display();
                ?>
            </form>
        </div>
    </div>

<?php
}

add_action("admin_action_delete_ld_event", 'admin_action_delete_ld_events_func');
function admin_action_delete_ld_events_func()
{
    $nonce = esc_attr($_REQUEST['_wpnonce']);
    $event_id = absint($_GET['event_id']);
    if (!wp_verify_nonce($nonce, 'ld_delete_events')) {
        die();
    } else {
        Box_Plus_Dice_FOrm_Table::delete_events(absint($event_id));
        // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
        // add_query_arg() return the current url
        wp_safe_redirect(home_url() . '/wp-admin/admin.php?page=box-plus-dice');
        exit;
    }
}
function box_plus_dice_settings_callback()
{
?>
    <h1>Settings</h1>
    <div class="box-plus-dice-api-settings">
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST" class="box-plus-dice-api-settings-form">
            <div class=" box-plus-dice-api-key">
                <label for="box-plus-dice-api-key"><?php esc_html_e('API Key: ', 'box-plus-dice') ?></label>
                <input name="box-plus-dice-api-key" id="box-plus-dice-api-key" value="<?php echo esc_attr(get_option('box_plus_dice_api')) ?>" style="width: 100%;" placeholder="API KEY">
            </div>
            <div class="box-plus-dice-domain-name">
                <label for="box-plus-dice-domain-name"><?php esc_html_e('Domain Name: ', 'box-plus-dice') ?></label>
                <input name="box-plus-dice-domain-name" id="box-plus-dice-domain-name" value="<?php echo esc_attr(get_option('box_plus_dice_domain_name')) ?>" style="width: 100%;" placeholder="https://your-domain.boxdice.com.au/website_api/enquiries">
            </div>

            <input type="hidden" name="action" value="box_plus_dice_api_form">
            <input type="submit" value="Submit" class="btn-submit">
        </form>
    </div>
<?php
}

function box_plus_dice_api_form_submit()
{

    if ($_POST['action'] == 'box_plus_dice_api_form') {
        update_option('box_plus_dice_api', $_POST['box-plus-dice-api-key']);
        update_option('box_plus_dice_domain_name', $_POST['box-plus-dice-domain-name']);
    }
    wp_safe_redirect(home_url() . '/wp-admin/admin.php?page=box-plus-dice-settings');
}

add_action('admin_post_nopriv_box_plus_dice_api_form', 'box_plus_dice_api_form_submit');
add_action('admin_post_box_plus_dice_api_form', 'box_plus_dice_api_form_submit');

function box_plus_dice_add_new_form_submit()
{
    if ($_POST['action'] == 'box_plus_dice_add_new_form') {
        $args = array(
            'post_title'    => $_POST['box-plus-dice-form-name'],
            'post_type'     => 'box-plus-dice-forms',
            'post_status'   => 'publish',
            'post_author'   => get_current_user_id(),
        );

        if ($_POST['box-plus-dice-form-id']) {
            $args['ID'] = $_POST['box-plus-dice-form-id'];
            wp_update_post($args);
        } else {
            wp_insert_post($args);
        }
    }
    wp_safe_redirect(home_url() . '/wp-admin/admin.php?page=box-plus-dice');
}

add_action('admin_post_nopriv_box_plus_dice_add_new_form', 'box_plus_dice_add_new_form_submit');
add_action('admin_post_box_plus_dice_add_new_form', 'box_plus_dice_add_new_form_submit');


function insert_enquiry($record, $handler)
{
    $form_name = $record->get_form_settings('form_name');

    if (in_array($form_name, get_option('box-plus-dice-form-name'))) {

        $raw_fields = $record->get('fields');
        $fields = [];
        $body = [];
        foreach ($raw_fields as $id => $field) {
            $fields[$id] = $field['value'];
        }

        $body = [
            "enquiry" => [
                "listing_id" => 1052,
                "consultant_id" => 15,
                "contact_name" => $fields['name'],
                "contact_email" => $fields['email'],
                "contact_phone" => $fields['phone'],
                "message" => $fields['message']
            ]
        ];

        $endpoint = 'https://' . esc_attr(get_option('box_plus_dice_domain_name')) . '.boxdice.com.au/website_api/enquiries';

        $body = wp_json_encode($body);

        $options = [
            'body'        => $body,
            'headers'     => [
                'Content-Type' => 'application/json',
                'Authorization'  => 'Api-Key token=' . get_option('box_plus_dice_api'),
            ],
        ];

        // wp_remote_post($endpoint, $options);
    }
}
add_action('elementor_pro/forms/new_record', 'insert_enquiry', 10, 2);

function box_plus_dice_load_scripts()
{
    wp_enqueue_style('box-plus-dice-css', Box_Plus_Dice_Path . 'style.css');
}
add_action('init', 'box_plus_dice_load_scripts');
