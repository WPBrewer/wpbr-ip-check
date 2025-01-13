jQuery(function($) {
    $('#wpbr_check_ip_button').on('click', function() {
        var $button = $(this);
        var $result = $('#wpbr-ip-check-result');
        var nonce = $button.data('nonce');

        $button.prop('disabled', true);
        $result.html('Checking IP...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpbr_check_ip',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    var date = new Date(data.checked_time);
                    $result.html(
                        '<strong>IP Address:</strong> ' + data.ip + 
                        ' <span class="ip-version">(' + data.version.toUpperCase() + ')</span><br>' +
                        '<strong>Checked Time:</strong> ' + date.toLocaleString()
                    );
                } else {
                    $result.html('<span style="color: red;">' + response.data.message + '</span>');
                }
            },
            error: function() {
                $result.html('<span style="color: red;">Error checking IP address</span>');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });
}); 
