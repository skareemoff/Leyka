<?php /** The default procedure to create Donors users from old Donations. */

require_once 'procedures-common.php';

if( !defined('WPINC') ) die;

ini_set('max_execution_time', 0);
set_time_limit(0);
ini_set('memory_limit', 268435456); // 256 Mb, just in case

if( !leyka_options()->opt('donor_management_available') ) {
    die;
}

//$_REQUEST['number_from'] = empty($_REQUEST['number_from']) || !absint($_REQUEST['number_from']) ?
//    0 : absint($_REQUEST['number_from']);
$_REQUEST['number_to_process'] =  empty($_REQUEST['number_to_process']) || !absint($_REQUEST['number_to_process']) ?
    -1 : absint($_REQUEST['number_to_process']);

$donor_donations = get_posts(array( // Get donations by donor
    'post_type' => Leyka_Donation_Management::$post_type,
    'post_status' => 'funded',
    'posts_per_page' => $_REQUEST['number_to_process'],
    'author__in' => array(0), // Donations w/o Donors assigned
));

foreach($donor_donations as $donation) {

    $donation = new Leyka_Donation($donation);

    $donor_user_id = Leyka_Donor::create_donor_from_donation($donation, false);
    if(is_wp_error($donor_user_id)) {
        /** @todo Log the Donor creation error, then continue */
        continue;
    } else {

        try {
            Leyka_Donor::calculate_donor_metadata(new Leyka_Donor($donor_user_id));
        } catch(Exception $e) {
            /** @todo Log the Donor instancing error, then continue */
        	continue;
        }

    }

}