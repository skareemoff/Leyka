// CP payment tryout custom setting:
jQuery(document).ready(function($){

    var $cp_payment_tryout_field = $('.settings-block.custom_cp_payment_tryout'),
        $step_submit = $('.step-submit input.step-next'),
        $cp_error_message = $cp_payment_tryout_field.find('.error-message');

    if( !$cp_payment_tryout_field.length ) {
        return;
    }

    $('.do-payment').on('click.leyka', function(e){

        e.preventDefault();

        var $payment_tryout_button = $(this);

        if($payment_tryout_button.data('submit-in-process')) {
            return;
        } else {
            $payment_tryout_button.data('submit-in-process', 1);
        }

        // Do a test donation:
        $payment_tryout_button.data('submit-in-process', 0);

        if( !leyka_wizard_cp.cp_public_id ) {

            $cp_error_message.html(leyka_wizard_cp.cp_not_set_up).show();
            return false;

        }

        var widget = new cp.CloudPayments();
        widget.charge({
            language: 'ru-RU',
            publicId: leyka_wizard_cp.cp_public_id,
            description: 'Leyka - test payment',
            amount: 1.0,
            currency: 'RUB',
            accountId: 'test-donor-email@test.ru',
            invoiceId: 'leyka-test-donation'
        }, function(options){ // success callback

            $cp_error_message.html('').hide();
            $payment_tryout_button.hide()
                .siblings('.result.ok').show();

        }, function(reason, options){ // fail callback
            $cp_error_message.html(leyka_wizard_cp.cp_donation_failure_reasons[reason] || reason).show();
        });

    });

});
// CP payment tryout custom setting - END