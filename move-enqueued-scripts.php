<?php
/*
Plugin Name: Move Enqueued Scripts
Version: 1.0
Description: Allow users to move enqueued scripts to the footer.
Author: Kyle Brumm
Author URI: http://kylebrumm.com
Plugin URI: http://kylebrumm.com/move-enqueued-scripts
Text Domain: move-enqueued-scripts
Domain Path: /languages
*/

if ( ! class_exists( 'MoveEnqueuedScripts' ) ) :

class MoveEnqueuedScripts {
    var $settings;

    /**
     *  Construct our class
     */
    public function __construct() {
        $this->settings = array(
            'url'       => plugin_dir_url( __FILE__ ),
            'path'      => plugin_dir_path( __FILE__ )
        );

        // Create our plugin page
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );

        // Get the front end scripts
        add_action('wp_footer', array( $this, 'set_front_end_scripts'), 999 );

        // Move the selected scripts
        if( !is_admin() ) {
            add_action('init', array( $this, 'move_selected_scripts'), 999 );
        }
    }

    /**
     * Add plugin page under "Tools"
     *
     *  @return  void
     */
    public function add_plugin_page() {
        add_management_page(
            'Move Enqueued Scripts',
            'Move Enqueued Scripts',
            'manage_options',
            'mes-general',
            array( $this, 'display_plugin_page' )
        );
    }

    /**
     * Setup the front end script list
     *
     *  @return  void
     */
    public function set_front_end_scripts( $handles = array() ) {
        global $wp_scripts;

        $registered = $wp_scripts->registered;
        $footer = $wp_scripts->in_footer;
        $queue = $wp_scripts->queue;

        $not_in_footer = array_diff( $queue, $footer );

        foreach( $not_in_footer as $handle ) {
            if( $handle != 'jquery' ) {
                $front_end_handles[$handle] = $registered[$handle]->src;
            }
        }

        update_option( 'mes_front_end_scripts', $front_end_handles );
    }

    /**
     * Management page callback
     *
     *  @return  void
     */
    public function display_plugin_page() { ?>
        <div class="wrap">
            <h2>Move Enqueued Scripts</h2>

            <?php
                // Check if the form has been submitted
                $this->handle_form_submission();

                // Get all the scripts
                $scripts = get_option( 'mes_front_end_scripts' );

                // Get the excluded scripts
                $excluded_scripts = get_option( 'mes_front_end_scripts_excluded' );

                echo '<pre>'; var_dump($scripts, $excluded_scripts); echo '</pre>';

                if( empty( $excluded_scripts ) )
                    $excluded_scripts = array();
            ?>

            <p>This allows you to choose scripts that you don't want to be moved to the footer.<br><strong>Note:</strong> Some scripts may be missing.</p>
            <form  method="post" enctype="multipart/form-data">
                <?php wp_nonce_field( 'mes-form-submission' ); ?>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="svg_file">Exclude Scripts:</label></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><span>Default article settings</span></legend>
                                    <?php foreach($scripts as $key => $val) : ?>
                                        <label for="<?php echo $key; ?>">
                                        <input name="handles[<?php echo $key; ?>]" type="checkbox" id="<?php echo $key; ?>" value="<?php echo $val; ?>" <?php echo ( array_key_exists( $key, $excluded_scripts ) )?'checked="checked"':''; ?>>
                                        <strong><?php echo ucwords(str_replace('-', ' ', $key)); ?></strong> <?php echo ($val)?'('.$val.')':''; ?></label>
                                        <br>
                                    <?php endforeach; ?>
                                    <p class="description">Check the scripts that you don't want to be moved to the footer.</p>
                                </fieldset>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="svg_file">jQuery:</label></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><span>Default article settings</span></legend>
                                        <label for="<?php echo $key; ?>">
                                        <input name="handles[jquery]" type="checkbox" id="jquery" value="<?php echo home_url().'/wp-includes/js/jquery/jquery.js'; ?>">
                                        Move jQuery to the footer</label>
                                </fieldset>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button( 'Submit' ) ?>
            </form>
        </div>
    <?php
    }

    /**
     *  Handle the form submission
     *
     *  @return  void
     */
    public function handle_form_submission() {
        $scripts = get_option( 'mes_front_end_scripts_excluded' );

        if( !empty( $_POST ) && !isset( $_POST['handles'] ) ) {
            if( $scripts ) {
                update_option( 'mes_front_end_scripts_excluded', '' );

                echo '<div id="message" class="updated notice is-dismissible">
                    <p>Settings have been updated.</p>
                    <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
                </div>';

                return;
            } else {
                return;
            }
        }

        // Check if there are any handles
        if( isset( $_POST['handles'] ) && ( $scripts != $_POST['handles'] ) ) {

            // Check for nonce
            check_admin_referer( 'mes-form-submission' );

            // Update the options
            $updated = update_option( 'mes_front_end_scripts_excluded', $_POST['handles'] );

            // Check for saving errors
            if( $updated ) {
                echo '<div id="message" class="updated notice is-dismissible">
                    <p>Settings have been updated.</p>
                    <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
                </div>';
            } else {
                echo '<div id="message" class="error notice is-dismissible">
                    <p>There was an error while updating the settings.</p>
                    <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
                </div>';

                return;
            }
        }

    }

    /**
     *  Handle moving the scripts to the footer
     *
     *  @return  void
     */
    public function move_selected_scripts() {
        // if( $scripts = get_option( 'mes_front_end_scripts_excluded' ) ) {
        //     foreach( $scripts as $key => $val ) {
        //         if($key == 'jquery') {
        //             wp_deregister_script( $key );
        //             wp_register_script( 'jquery', home_url().'/wp-includes/js/jquery/jquery.js', '', '', true );
        //         } else {
        //             wp_deregister_script( $key );
        //             wp_register_script( $key, $val, '', '', true );
        //         }
        //     }
        // }
    }
}

function mes() {
    global $mes;

    if ( ! isset( $mes ) ) {
        $mes = new MoveEnqueuedScripts();
    }

    return $mes;
}

// Initialize
// if( is_admin() ) {
    mes();
// }

endif;

