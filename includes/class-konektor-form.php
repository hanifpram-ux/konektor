<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Form {

    public static function render( $campaign ) {
        $config      = Konektor_Campaign::get_form_config( $campaign );
        $fields      = $config['fields']      ?? [];
        $template    = $config['template']    ?? 'modern';
        $submit_label = $config['submit_label'] ?? 'Kirim Sekarang';

        ob_start();
        ?>
        <div class="konektor-form-wrap konektor-tpl-<?php echo esc_attr( $template ); ?>" data-campaign="<?php echo esc_attr( $campaign->id ); ?>">
            <?php if ( $campaign->store_name || $campaign->product_name ) : ?>
            <div class="konektor-form-header">
                <?php if ( $campaign->store_name ) : ?>
                    <p class="konektor-store"><?php echo esc_html( $campaign->store_name ); ?></p>
                <?php endif; ?>
                <?php if ( $campaign->product_name ) : ?>
                    <h2 class="konektor-product"><?php echo esc_html( $campaign->product_name ); ?></h2>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="konektor-alert konektor-hidden"></div>

            <form id="konektor-form-<?php echo esc_attr( $campaign->id ); ?>" class="konektor-form" novalidate>
                <?php wp_nonce_field( 'konektor_nonce', 'nonce' ); ?>
                <input type="hidden" name="campaign_id" value="<?php echo esc_attr( $campaign->id ); ?>">
                <input type="hidden" name="source_url" value="<?php echo esc_url( ( is_ssl() ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ); ?>">
                <input type="hidden" name="action" value="konektor_submit_form">

                <?php foreach ( $fields as $field ) : ?>
                    <?php if ( empty( $field['enabled'] ) ) continue; ?>
                    <div class="konektor-field">
                        <label for="konektor-<?php echo esc_attr( $field['name'] ); ?>-<?php echo esc_attr( $campaign->id ); ?>">
                            <?php echo esc_html( $field['label'] ); ?>
                            <?php if ( ! empty( $field['required'] ) ) : ?><span class="req">*</span><?php endif; ?>
                        </label>
                        <?php self::render_field( $field, $campaign->id ); ?>
                    </div>
                <?php endforeach; ?>

                <?php
                // Custom / extra fields
                $extra_fields = $config['extra_fields'] ?? [];
                foreach ( $extra_fields as $ef ) :
                    if ( empty( $ef['enabled'] ) ) continue;
                ?>
                <div class="konektor-field">
                    <label><?php echo esc_html( $ef['label'] ); ?><?php if ( ! empty( $ef['required'] ) ) echo '<span class="req">*</span>'; ?></label>
                    <?php self::render_field( $ef, $campaign->id ); ?>
                </div>
                <?php endforeach; ?>

                <div class="konektor-field konektor-submit-wrap">
                    <button type="submit" class="konektor-btn-submit">
                        <span class="konektor-btn-text"><?php echo esc_html( $submit_label ); ?></span>
                        <span class="konektor-btn-loading konektor-hidden">Mengirim...</span>
                    </button>
                </div>
            </form>

            <div class="konektor-thanks konektor-hidden"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function render_field( $field, $campaign_id ) {
        $name      = esc_attr( $field['name'] );
        $id        = "konektor-{$name}-{$campaign_id}";
        $label     = esc_attr( $field['label'] );
        $required  = ! empty( $field['required'] ) ? 'required' : '';
        $type      = esc_attr( $field['type'] ?? 'text' );
        $options   = $field['options'] ?? [];
        $placeholder = esc_attr( $field['placeholder'] ?? $field['label'] );

        switch ( $type ) {
            case 'textarea':
                echo "<textarea name='{$name}' id='{$id}' placeholder='{$placeholder}' {$required}></textarea>";
                break;
            case 'select':
                echo "<select name='{$name}' id='{$id}' {$required}><option value=''>-- Pilih --</option>";
                foreach ( $options as $opt ) {
                    echo "<option value='" . esc_attr( $opt ) . "'>" . esc_html( $opt ) . "</option>";
                }
                echo "</select>";
                break;
            case 'radio':
                foreach ( $options as $opt ) {
                    $esc_opt = esc_attr( $opt );
                    echo "<label class='konektor-radio'><input type='radio' name='{$name}' value='{$esc_opt}' {$required}> " . esc_html( $opt ) . "</label>";
                }
                break;
            case 'checkbox':
                foreach ( $options as $opt ) {
                    $esc_opt = esc_attr( $opt );
                    echo "<label class='konektor-checkbox'><input type='checkbox' name='{$name}[]' value='{$esc_opt}'> " . esc_html( $opt ) . "</label>";
                }
                break;
            default:
                echo "<input type='{$type}' name='{$name}' id='{$id}' placeholder='{$placeholder}' {$required}>";
        }
    }
}
