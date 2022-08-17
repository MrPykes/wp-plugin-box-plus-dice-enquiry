<?php


if (!defined('ABSPATH')) {
    exit;
}
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
if (!class_exists('Box_Plus_Dice_FOrm_Table', false)) {
    class Box_Plus_Dice_FOrm_Table extends WP_List_Table
    {

        /**
         * Render a column when no column specific method exist.
         *
         * @param array $item
         * @param string $column_name
         *
         * @return mixed
         */
        public function column_default($item, $column_name)
        {
            switch ($column_name) {
                case 'title':
                    return $item[$column_name];
                default:
                    return "No Value"; //Show the whole array for troubleshooting purposes
            }
        }

        /**
         * Render the bulk edit checkbox
         *
         * @param array $item
         *
         * @return string
         */
        function column_cb($item)
        {
            return sprintf(
                '<input type="checkbox" name="bulk-delete[]" value="%s" />',
                $item['id']
            );
        }

        /**
         *  Associative array of columns
         *
         * @return array
         */
        function get_columns()
        {
            $columns = [
                'cb'      => '<input type="checkbox" />',
                'title'    => __('Form Title', 'box-plus-dice'),
            ];

            return $columns;
        }


        /**
         * Method for name column
         *
         * @param array $item an array of DB data
         *
         * @return string
         */
        function column_title($item)
        {

            $delete_nonce = wp_create_nonce('ld_delete_events');

            $title = '<strong>' . $item['title'] . '</strong>';

            $actions = [
                'edit' => '<a href="' . admin_url() . 'admin.php?page=box-plus-dice&p=' . $item['id'] . '" aria-label="Edit “' . $item['title'] . '”">Edit</a>',
                'delete' => sprintf('<a onclick="return confirm(' . "'Are you sure?'" . ')" href="' . admin_url() . 'admin.php?action=%s&event_id=%s&_wpnonce=%s">Delete</a>', 'delete_ld_event', absint($item['id']), $delete_nonce)
            ];

            return $title . $this->row_actions($actions);
        }

        /**
         * Handles data query and filter, sorting, and pagination.
         */
        public function prepare_items()
        {
            $this->_column_headers = [$this->get_columns()];

            $this->process_bulk_action();

            $per_page     = $this->get_items_per_page('events_per_page', 5);
            $current_page = $this->get_pagenum();
            $total_items  = self::record_count();

            $this->set_pagination_args([
                'total_items' => $total_items, //WE have to calculate the total number of items
                'per_page'    => $per_page //WE have to determine how many items to show on a page
            ]);


            $this->items = self::get_events($per_page, $current_page);
        }


        /**
         * Retrieve events data from the database
         *
         * @param int $per_page
         * @param int $page_number
         *
         * @return mixed
         */
        public static function get_events($per_page = 5, $page_number = 1)
        {
            $data_handler = [];
            $args = array(
                'post_type'     => 'box-plus-dice-forms',
                'post_status'   => 'publish',
            );
            $query = new WP_Query($args);
            if ($query->have_posts()) :
                while ($query->have_posts()) : $query->the_post();
                    $data_handler[] = [
                        'id' => get_the_id(),
                        'title' => get_the_title()
                    ];
                endwhile;
            endif;

            return $data_handler;
        }

        /**
         * Delete a event record.
         *
         * @param int $id event ID
         */
        public static function delete_events($id)
        {
            wp_delete_post($id);
        }

        public function process_bulk_action()
        {

            //Detect when a bulk action is being triggered...

            if ('delete' === $this->current_action()) {
                // In our file that handles the request, verify the nonce.
                $nonce = esc_attr($_REQUEST['_wpnonce']);

                if (!wp_verify_nonce($nonce, 'ld_delete_events')) {
                    die('Go get a life script kiddies');
                } else {
                    self::delete_events(absint($_GET['event_id']));
                    // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
                    // add_query_arg() return the current url
                    // wp_redirect('esc_url_raw(add_query_arg())');
                    wp_safe_redirect(home_url() . '/wp-admin/admin.php?page=box-plus-dice');
                    exit;
                }
            }

            // If the delete bulk action is triggered
            if ((isset($_POST['action']) && $_POST['action'] == 'bulk-delete')
                || (isset($_POST['action2']) && $_POST['action2'] == 'bulk-delete')
            ) {

                $delete_ids = esc_sql($_POST['bulk-delete']);

                // loop over the array of record IDs and delete them
                if ($delete_ids) {
                    foreach ($delete_ids as $id) {
                        self::delete_events($id);
                    }
                }
                // wp_safe_redirect('esc_url_raw(add_query_arg())');
                // wp_safe_redirect(home_url() . '/wp-admin/admin.php?page=box-plus-dice');
                // exit;
            }
        }


        /**
         * Returns the count of records in the database.
         *
         * @return null|string
         */
        public static function record_count()
        {
            $args = array(
                'post_type'     => 'box-plus-dice-forms',
                'post_status'   => 'publish',
            );

            $query = new WP_Query($args);

            return $query->post_count;
        }


        /** Text displayed when no event data is available */
        public function no_items()
        {
            _e('No events avaliable.', 'calendar-for-learndash');
        }

        /**
         * Columns to make sortable.
         *
         * @return array
         */
        public function get_sortable_columns()
        {
            $sortable_columns = array(
                'title' => array('title', true)
            );

            return $sortable_columns;
        }

        /**
         * Returns an associative array containing the bulk action
         *
         * @return array
         */
        public function get_bulk_actions()
        {
            $actions = [
                'bulk-delete' => 'Delete'
            ];

            return $actions;
        }
    }
}
