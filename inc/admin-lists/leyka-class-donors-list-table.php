<?php if( !defined('WPINC') ) die;
/** Donors list table class */

if( !class_exists('WP_List_Table') ) {
    require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
}

class Leyka_Admin_Donors_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct(array('singular' => __('Donor', 'leyka'), 'plural' => __('Donors', 'leyka'), 'ajax' => true,));
    }

    /**
     * Retrieve donor’s data from the DB.
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public static function get_donors($per_page = 10, $page_number = 1) {

//        global $wpdb;
//
//        $sql = "SELECT * FROM {$wpdb->prefix}customers";
//
//        if ( ! empty( $_REQUEST['orderby'] ) ) {
//            $sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
//            $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
//        }
//
//        $sql .= " LIMIT $per_page";
//
//        $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

        $donors = array();
        $donors_users = get_users(array(
            'role__in' => array('donor'),
            'number' => absint($per_page),
            'paged' => absint($page_number),
            'fields' => array('ID', 'user_email', 'display_name',),
        ));

        $donor_donations_params = array(
            'post_type' => Leyka_Donation_Management::$post_type,
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'desc',
        );

        foreach($donors_users as $donor_user) {

            $donor_data = array(
                'id' => $donor_user->ID,
                'donor_name' => $donor_user->display_name,
                'donor_email' => $donor_user->user_email,
            );

            $donor_donations_params['meta_key'] = 'leyka_donor_email';
            $donor_donations_params['meta_value'] = $donor_data['donor_email'];

            $donor_donations = get_posts($donor_donations_params); // Get donations by donor, ordered by date desc

            $donations_count = count($donor_donations);
            for($i=0; $i < count($donor_donations); $i++) {

                $donation = new Leyka_Donation($donor_donations[$i]);

                if($i === 0) {
                    $donor_data['first_donation'] = $donation;
                } else if ($i === $donations_count) {
                    $donor_data['last_donation'] = $donation;
                }

                if(empty($donor_data['campaigns']) || !in_array($donation->campaign_title, $donor_data['campaigns'])) {
                    $donor_data['campaigns'][$donation->campaign_id] = $donation->campaign_title;
                }

                /** @todo Tmp, until donors tags will be added */
                $donor_data = $donor_data + array(
                    'donors_tags' => array(),
                );

                if(empty($donor_data['gateways']) || !in_array($donation->gateway, $donor_data['gateways'])) {
                    $donor_data['gateways'][$donation->gateway] = $donation->gateway_label;
                }

                if($donation->status === 'funded') {
                    $donor_data['amount_donated'] = empty($donor_data['amount_donated']) ?
                        $donation->amount : $donor_data['amount_donated'] + $donation->amount;
                }

            }

            $donors[] = $donor_data;

        }

        echo '<pre>'.print_r($donors, 1).'</pre>';

        return $donors;

    }

    /**
     * Delete a donor record.
     *
     * @param int $donor_id Donor ID
     */
    public static function delete_donor($donor_id) {
        wp_delete_user(absint($donor_id));
    }

    /**
     * @return null|string
     */
    public static function record_count() {

        $donors = new WP_User_Query(array(
            'role__in' => array('donor'),
            'count_total' => true,
            /** @todo Apply donor table filters here! */
        ));

        return $donors->get_total();

    }

    /** Text displayed when no donors data is available. */
    public function no_items() {
        _e('No donors avaliable.', 'leyka');
    }

    /**
     * @param array $item An array of DB data.
     * @return string
     */
    function column_donor_name($item) {

        $actions = array(
            'delete' => sprintf(
                '<a href="?page=%s&action=%s&donor=%s&_wpnonce=%s">'.__('Delete', 'leyka').'</a>',
                esc_attr($_REQUEST['page']),
                'delete',
                absint($item['id']),
                wp_create_nonce('leyka_delete_donor')
            )
        );

        return '<div class="donor-name">'.$item['donor_name'].'</div>'
            .'<div class="donor-email">'.$item['donor_email'].'</div>'
            .$this->row_actions($actions);

    }

    /**
     * Render a column when no column specific method exists.
     *
     * @param array $item
     * @param string $column_name
     * @return mixed
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'donor_type':
            case 'donor_name':
            case 'first_donation':
            case 'campaigns':
            case 'last_donation':
            case 'donors_tags':
            case 'gateways':
            case 'amount_donated':
                return $item[$column_name];
            default:
                return print_r($item, true); // Show the whole array for troubleshooting purposes
        }
    }

    /**
     * Render the bulk edit checkbox.
     *
     * @param array $item
     * @return string
     */
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="bulk-delete[]" value="%s">', $item['id']);
    }

    /**
     *  Associative array of columns.
     *
     * @return array
     */
    function get_columns() {
        return array(
            'cb' => '<input type="checkbox">',
            'donor_type' => _x('Type', "Donor's type", 'leyka'),
            'donor_name' => __("Donor's name", 'leyka'),
            'first_donation' => __('First donation', 'leyka'),
            'campaigns' => __('Campaigns list', 'leyka'),
            'last_donation' => __('Last donation', 'leyka'),
            'donors_tags' => __("Donors' tags", 'leyka'),
            'gateways' => __('Gateway', 'leyka'),
            'amount_donated' => __('Amount donated', 'leyka'),
        );
    }

    /**
     * @return array
     */
    public function get_sortable_columns() {
        return array(
            'donor_type' => array('donor_type', true),
            'donor_name' => array('donor_name', false),
            'first_donation' => array('first_donation', true),
            'amount_donated' => array('amount_donated', true),
        );
    }

    /**
     * @return array
     */
    public function get_bulk_actions() {
        return array('bulk-delete' => __('Delete'));
    }

    /**
     * Data query, filtering, sorting & pagination handler.
     */
    public function prepare_items() {

        $this->_column_headers = $this->get_column_info();

        $this->process_bulk_action();

        $per_page = 10;
        $current_page = $this->get_pagenum();
        $total_lines = self::record_count();

        $this->set_pagination_args(array('total_items' => $total_lines, 'per_page' => $per_page,));
        $this->items = self::get_donors($per_page, $current_page);

    }

    public function process_bulk_action() {

        // Single donor deletion:
        if('delete' === $this->current_action()) {

            if ( !wp_verify_nonce(esc_attr($_REQUEST['_wpnonce']), 'leyka_delete_donor') ) {
                die(__("You don't have permissions for this operation.", 'leyka'));
            } else {

                $this->delete_donor(absint($_GET['donor']));

                wp_redirect( esc_url(add_query_arg()) );
                exit;

            }

        }

        // Bulk donors deletion:
        if(
            (isset($_POST['action']) && $_POST['action'] === 'bulk-delete')
            || (isset($_POST['action2']) && $_POST['action2'] === 'bulk-delete')
        ) {

            foreach(esc_sql($_POST['bulk-delete']) as $id) {
                $this->delete_donor($id);
            }

            wp_redirect( esc_url(add_query_arg()) );
            exit;

        }

    }

}