<?php

namespace App;

class POC_Chatbot_AJAX
{
    /**
     * Get gift. Create order with price zero
     *
     * @throws \WC_Data_Exception
     */
    public function get_gift()
    {
        if( ! isset( $_POST['product_id'] ) || ! isset( $_POST['ajax_nonce'] ) ) {
            wp_send_json_error();
        }

        if( ! wp_verify_nonce( $_POST['ajax_nonce'], 'poc-chatbot-ajax-nonce' ) ) {
            wp_send_json_error( 'wrong nonce' );
        }

        $product = wc_get_product( $_POST['product_id'] );

        if( ! $product ) {
            wp_send_json_error();
        }

        $address = array(
            'first_name' => ( $_POST['first_name'] ) ? $_POST['first_name'] : '',
            'last_name'  => ( $_POST['last_name'] ) ? $_POST['last_name'] : '',
            'email' => ( $_POST['email'] ) ? $_POST['email'] : '',
            'phone' => ( $_POST['phone_number'] ) ? $_POST['phone_number'] : '',
            'address_1' => ( $_POST['address'] ) ? $_POST['address'] : '',
            'address_2' => $this->get_ward_code( $this->get_ward_name( $_POST['district'], $_POST['ward'] ) ),
            'city' => $this->get_district_code( $this->get_district_name( $_POST['city'], $_POST['district'] ) ),
            'state' => $this->get_city_code( $this->get_city_name( $_POST['city'] ) ),
        );

        $order = wc_create_order();

        $order->add_product( $product, 1 );
        $order->set_address( $address, 'billing' );
        $order->set_address( $address, 'shipping' );
        $order->add_meta_data( 'ref_by', $_POST['ref_by'] );
        $order->add_meta_data( 'fb_client_id', $_POST['client_id'] );

        $order->save();

        foreach($order->get_items() as $order_item){
            $order_item->set_subtotal(0);
            $order_item->set_total(0);
            $order_item->save();
        }

        $this->delete_transient( $_POST['transient_key'] );

        wp_send_json_success();
    }

    protected function get_full_address( $data )
    {
        $address = ( $data['address'] ) ? $data['address'] . ', ' : '';

        $address .= ( $data['district'] && $data['ward'] ) ? $this->get_ward_name( $data['district'], $data['ward'] ) . ', ' : '';

        $address .= ( $data['city'] && $data['district'] ) ? $this->get_district_name( $data['city'], $data['district'] ) . ', ' : '';

        $address .= ( $data['city'] ) ? $this->get_city_name( $data['city'] ) : '';

        return $address;
    }

    /**
     * Get city name
     *
     * @param $id
     *
     * @return string
     */
    protected function get_city_name( $id )
    {
        $json = file_get_contents( POC_CHATBOT_PLUGIN_DIR . 'assets/json/cities.json' );

        $cities = json_decode( $json, true );

        return ( isset( $cities[$id] ) ) ? $cities[$id] : '' ;
    }

    /**
     * Get district name
     *
     * @param $city
     * @param $district
     *
     * @return string
     */
    protected function get_district_name( $city, $district )
    {
        $json = file_get_contents( POC_CHATBOT_PLUGIN_DIR . 'assets/json/districts.json' );

        $districts = json_decode( $json, true );

        return ( isset( $districts[$city][$district] ) ) ? $districts[$city][$district] : '' ;
    }

    /**
     * Get ward name
     *
     * @param $district
     * @param $ward
     *
     * @return string
     */
    protected function get_ward_name( $district, $ward )
    {
        $json = file_get_contents( POC_CHATBOT_PLUGIN_DIR . 'assets/json/wards.json' );

        $wards = json_decode( $json, true );

        return ( isset( $wards[$district][$ward] ) ) ? $wards[$district][$ward] : '' ;
    }

    protected function get_city_code( $city_name )
    {
        include_once POC_CHATBOT_PLUGIN_DIR . 'assets/data/tinh_thanhpho.php';

        $city_code = '';

        foreach($tinh_thanhpho as $code => $name) {
            if( strtolower( $city_name ) === strtolower( $name ) ) {
                $city_code = $code;
                break;
            }
            continue;
        }

        return $city_code;
    }

    protected function get_district_code( $district_name )
    {
        include_once POC_CHATBOT_PLUGIN_DIR . 'assets/data/quan_huyen.php';

        $district_code = '';

        foreach( $quan_huyen as $item ) {
            if( strtolower( $item['name'] ) === strtolower( $district_name ) ) {
                $district_code = $item['maqh'];
                break;
            }
            continue;
        }

        return $district_code;
    }

    protected function get_ward_code( $ward_name )
    {
        include_once POC_CHATBOT_PLUGIN_DIR . 'assets/data/xa_phuong_thitran.php';

        $ward_code = '';

        foreach( $xa_phuong_thitran as $item ) {
            if( strtolower( $item['name'] ) === strtolower( $ward_name ) ) {
                $ward_code = $item['xaid'];
                break;
            }
            continue;
        }

        return $ward_code;
    }

    protected function delete_transient( $transient_key )
    {
        delete_transient( $transient_key );
    }
}
