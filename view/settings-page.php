<h3>Option settings</h3>

<div>
    <form method="post">
    <?php
    foreach ( $picanova_changeable_options as $slug => $name ) {
        ?>
        <div>
            <p>Option: <b><?php echo esc_html( $name ); ?></b></p>
            <label for="<?php echo esc_attr($slug); ?>-input">Increase by </label>
            <input type="number" step="0.01" id="<?php echo esc_attr($slug); ?>-input" name="<?php echo esc_attr($slug); ?>" value="<?php echo (float)get_option( PICANOVA_OPTION_PREFIX . $slug );?>">
            <span> percents.</span>
        </div>
        <?php
    }
    ?>
    <?php wp_nonce_field( 'picanova_settings_action', 'picanova_settings_nonce' ); ?>
    <button type="submit" style="margin-top: 30px;">Save settings</button>
    </form>
</div>
