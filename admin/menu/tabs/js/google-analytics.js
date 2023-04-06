jQuery(function ($) {
  $(document).ready(function () {

    $(document).on('click', '.save-button', function (e) {
      handle_google_analytics_save_request();
    });

    $(document).on('click', '#private_key_encode_button', function () {
      handle_private_key_encode_decode_request(true);
    });

    $(document).on('click', '#private_key_decode_button', function () {
      handle_private_key_encode_decode_request(false);
    });

    function handle_google_analytics_save_request() {
      let payload = {
        'credentials': JSON.parse($('#ga_textarea_credentials').val()),
        'properties': JSON.parse($('#ga_textarea_properties').val()),
        'date_ranges': JSON.parse($('#ga_textarea_date_ranges').val())
      };

      console.log(payload);
      $('#ga_form_payload').val(JSON.stringify(payload));
      $('#ga_form').submit();
    }

    function handle_private_key_encode_decode_request(encode) {
      let textarea = $('#ga_textarea_credentials_private_key');
      let private_key = $(textarea).val();
      $(textarea).val(encode ? btoa(private_key):atob(private_key));
    }

  });
});
