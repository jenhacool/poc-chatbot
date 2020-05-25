<div class="wrap">
    <h1>POC AhaChat Integration</h1>
    <form action="" method="POST">
        <?php
            wp_nonce_field( 'poc_chatbot_save_settings', 'poc_chatbot_save_settings' );
        ?>
        <h2>WinCode list</h2>
        <table id="wincode-list" class="wp-list-table widefat fixed striped">
            <thead>
                <th>WinCode</th>
                <th>Product ID</th>
                <th>Discount</th>
                <th>Link</th>
                <th>Action</th>
            </thead>
            <tbody>
                <?php if( ! empty( $settings['wincodes'] ) ) : ?>
                    <?php foreach( $settings['wincodes'] as $index => $wincode ) : ?>
                        <tr>
                            <td>
                                <input type="text" value="<?php echo $wincode['wincode']; ?>" name="settings[wincodes][<?php echo $index; ?>][wincode]">
                            </td>
                            <td>
                                <input type="number" value="<?php echo $wincode['product_id']; ?>" name="settings[wincodes][<?php echo $index; ?>][product_id]">

                            </td>
                            <td>
                                <input type="number" value="<?php echo $wincode['discount']; ?>" name="settings[wincodes][<?php echo $index; ?>][discount]">
                            </td>
                            <td>
                                <input type="text" value="<?php echo $wincode['link']; ?>" name="settings[wincodes][<?php echo $index; ?>][link]">
                            </td>
                            <td>
                                <a href="" class="button delete-wincode">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php
                        $new_index = ( ! empty( $settings['wincodes'] ) ) ? count( $settings['wincodes'] ) : 0;
                    ?>
                <?php else : ?>
                    <tr>
                        <td>
                            <input type="text" value="" name="settings[wincodes][0][wincode]">
                        </td>
                        <td>
                            <input type="number" value="" name="settings[wincodes][0][product_id]">

                        </td>
                        <td>
                            <input type="number" value="" name="settings[wincodes][0][discount]">
                        </td>
                        <td>
                            <input type="text" value="" name="settings[wincodes][0][link]">
                        </td>
                        <td>
                            <a href="" class="button delete-wincode">Delete</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5">
                        <a href="" class="button add-wincode">Add</a>
                        <span style="float: right"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save"></span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </form>
</div>

<script>
(function($) {
    $(document).ready(function () {
        $('.delete-wincode').click(function (e) {
            e.preventDefault();
            $(this).closest('tr').remove();
            $('#wincode-list tbody tr').each(function (index) {
                $(this).find('input').each(function () {
                    var name = $(this).attr('name');
                    name = name.replace(/\[(\d+)\]/, '[' + parseInt(index) + ']');
                    $(this).attr('name', name).attr('id', name);
                })
            });
        });

        $('.add-wincode').click(function(e) {
            e.preventDefault();
            var row = $('#wincode-list').find('tbody tr:last');
            var clone = row.clone();
            var count = $('#wincode-list').find('tbody tr').length;
            clone.find('input').each(function () {
                $(this).val('');
                var name = $(this).attr('name');
                name = name.replace(/\[(\d+)\]/, '[' + parseInt(count) + ']');
                $(this).attr('name', name).attr('id', name);
            });
            clone.insertAfter(row);
        });
    });
})(jQuery);
</script>