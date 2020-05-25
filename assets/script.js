(function($) {
    function getParameterByName(name, url) {
        if (!url) url = window.location.href;
        name = name.replace(/[\[\]]/g, '\\$&');
        var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }

    $(document).ready(function() {
        $('#poc_chatbot_buy_button').click(function(e) {
            e.preventDefault();
            var self = this;
            jQuery.ajax({
                type: 'POST',
                url: poc_chatbot_data.ajax_url,
                data: {
                    action: 'poc_chatbot_match_order',
                    customer_key: getParameterByName('customer_key')
                },
                beforeSend: function() {
                    $(self).find('a').css({
                        'cursor': 'no-drop',
                        'opacity': '.5'
                    });
                },
                success: function(response) {
                    console.log(response);
                }
            });
        });
    });
})(jQuery);