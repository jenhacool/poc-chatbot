jQuery(document).ready(function() {
    jQuery('#poc-chatbot-gift form').validate({
        rules: {
            city: 'required',
            district: 'required',
            ward: 'required',
            address: 'required',
            first_name: 'required',
            last_name: 'required',
            phone_number: 'required',
        }
    });

    jQuery.extend(jQuery.validator.messages, {
        required: "Vui lòng điền thông tin.",
    });

    jQuery('#city').on('change', function () {
        var city = jQuery(this).val();
        jQuery('#districts').empty();
        jQuery('#wards').empty();
        jQuery.getJSON(poc_chatbot.districts).done(function (response) {
            var districts = response[city];
            jQuery.each(districts, function (i, val) {
                jQuery('#districts').append('<option value="' + i + '">' + val + '</option>')
            });
        });
    });

    jQuery('#districts').on('change', function () {
        var disctrict = jQuery(this).val();
        jQuery('#wards').empty();
        jQuery.getJSON(poc_chatbot.wards).done(function (response) {
            var wards = response[disctrict];
            jQuery.each(wards, function (i, val) {
                jQuery('#wards').append('<option value="' + i + '">' + val + '</option>')
            });
        });
    });

    jQuery('form').submit(function(e) {
        e.preventDefault();

        if(!jQuery('#poc-chatbot-gift form').valid()) {
            return false;
        }

        jQuery.ajax({
            type: 'POST',
            url: poc_chatbot.ajax_url,
            data: jQuery('form').serialize(),
            beforeSend: function() {
                jQuery('form button').prop('disabled', true);
            },
            success: function(response) {
                jQuery('form button').remove();
                jQuery('#customer-info, #product-info').remove();
                jQuery('#result').show();
                if(response.success) {
                    jQuery('#result').append('<h2>Thành công !!!</h2><p>Vui lòng kiểm tra email để xem thông tin đơn hàng.</p><p>Chúng tôi đang có chương trình vòng quay may mắn với nhiều phần quà hấp dẫn. Nhấn <a href="'+poc_chatbot.messenger_url+'">vào đây</a> để tham gia</p>');
                } else {
                    jQuery('#result').append('<h2>Lỗi</h2><p>Vui lòng liên hệ abc@gmail.com để được hỗ trợ</p>');
                }
            }
        });
    });
});