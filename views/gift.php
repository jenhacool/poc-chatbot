<!DOCTYPE html>

<html class="no-js" <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" >
    <title>Đặt hàng</title>
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <link rel="stylesheet" href="<?php echo POC_CHATBOT_PLUGIN_URL . 'assets/gift.css'; ?>">
</head>
<body id="poc-chatbot-gift">
    <?php
        $data = get_transient( get_query_var( 'customer_key' ) );

        if( ! $data ) {
            wp_redirect( get_home_url() );
            die;
        }

        $product = wc_get_product( $data['product_id'] );
    ?>
    <div class="container">
        <form>
            <input type="hidden" name="action" value="poc_chatbot_get_gift">
            <input type="hidden" name="transient_key" value="<?php echo get_query_var( 'customer_key' ); ?>">
            <input type="hidden" name="client_id" value="<?php echo $data['client_id']; ?>">
            <input type="hidden" name="ref_by" value="<?php echo $data['gift_code']; ?>">
            <?php wp_nonce_field( 'poc-chatbot-ajax-nonce', 'ajax_nonce' );?>
            <input type="hidden" name="product_id" value="<?php echo $data['product_id']; ?>">
            <div id="customer-info" class="section">
                <div class="form-section">
                    <h2>Thông tin giao hàng</h2>
                    <div class="form-fields">
                        <div class="form-field">
                            <?php
                            $json = file_get_contents( POC_CHATBOT_PLUGIN_DIR . 'assets/json/cities.json' );
                            $cities = json_decode( $json, true );
                            ?>
                            <label for="">Tỉnh/Thành phố</label>
                            <select name="city" id="city">
                                <?php foreach( $cities as $id => $city ) : ?>
                                    <option value="<?php echo $id; ?>"><?php echo $city; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="">Quận/Huyện</label>
                            <select name="district" id="districts"></select>
                        </div>
                        <div class="form-field">
                            <label for="">Xã/Phường</label>
                            <select name="ward" id="wards"></select>
                        </div>
                        <div class="form-field">
                            <label for="address">Địa chỉ</label>
                            <input name="address" type="text">
                        </div>
                    </div>
                </div>
                <div class="form-section">
                    <h2>Thông tin khách hàng</h2>
                    <div class="form-fields">
                        <div class="form-field">
                            <label for="">Họ</label>
                            <input type="text" name="first_name" value="<?php echo $data['first_name']; ?>">
                        </div>
                        <div class="form-field">
                            <label for="">Tên</label>
                            <input type="text" name="last_name" value="<?php echo $data['last_name']; ?>">
                        </div>
                        <div class="form-field">
                            <label for="">Số điện thoại</label>
                            <input type="text" name="phone_number" value="<?php echo $data['phone_number']; ?>">
                        </div>
                        <div class="form-field">
                            <label for="">Email</label>
                            <input type="text" name="email" value="<?php echo $data['email']; ?>">
                        </div>
                    </div>
                </div>
            </div>
            <div id="product-info" class="section">
                <h2>Sản phẩm</h2>
                <div id="product-info-row">
                    <div class="thumbnail">
                        <?php echo $product->get_image(); ?>
                    </div>
                    <div class="info">
                        <h3><?php echo $product->get_title(); ?></h3>
                        <p><strong>Giá:</strong> <span class="free">Miễn phí</span></p>
                        <p><strong>Phí ship:</strong> 30.000 VNĐ</p>
                        <ul class="attributes">

                        </ul>
                    </div>
                </div>
            </div>
            <div id="result" style="display: none" class="section"></div>
            <button id="new_order">Đặt hàng</button>
        </form>
    </div>
    <script>
        var poc_chatbot = {
            'districts': "<?php echo POC_CHATBOT_PLUGIN_URL . 'assets/json/districts.json';?>",
            'wards': "<?php echo POC_CHATBOT_PLUGIN_URL . 'assets/json/wards.json'; ?>",
            'ajax_url': "<?php echo admin_url( 'admin-ajax.php' ); ?>",
            'ajax_nonce': "<?php echo wp_create_nonce( 'poc-chatbot-ajax-nonce' ); ?>"
        };
    </script>
    <script src="<?php echo POC_CHATBOT_PLUGIN_URL . 'assets/jquery.js'; ?>"></script>
    <script src="<?php echo POC_CHATBOT_PLUGIN_URL . 'assets/jquery.validate.js'; ?>"></script>
    <script src="<?php echo POC_CHATBOT_PLUGIN_URL . 'assets/gift.js'; ?>"></script>
</body>
</html>