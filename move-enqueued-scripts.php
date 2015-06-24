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

        // Get all the scripts
        foreach( $wp_scripts -> registered as $registered ) {
            $script_urls[ $registered -> handle ] = $registered -> src;
        }

        // If nothing, add the queue
        if( empty( $handles ) ) {
            $handles = array_merge( $wp_scripts -> queue );
            array_values( $handles );
        }

        // Build a string of the handles
        $front_end_handles = '';
        foreach( $handles as $handle ) {
            if( !empty( $script_urls[ $handle ] ) ) {
                $front_end_handles[] = array(
                    'handle'         => $handle,
                    'path'           => $script_urls[ $handle ]
                );
            }
        }

        // echo '<pre>'; var_dump($front_end_handles); echo '</pre>'; exit;

        update_option('mes_front_end_scripts', $front_end_handles );
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

                // Get the moved scripts
                $moved_scripts = get_option('mes_front_end_scripts_moved');
                if(empty($moved_scripts))
                    $moved_scripts = array();

                // Get all the scripts
                $scripts = get_option('mes_front_end_scripts');
            ?>

            <p>This allows you to move enqueued scripts that aren't already in the footer, to the footer.<br><strong>Note:</strong> Some scripts may be missing.</p>
            <form  method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('mes-form-submission'); ?>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="svg_file">Scripts:<sup>*</sup></label></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><span>Default article settings</span></legend>
                                    <?php foreach($scripts as $script) : ?>
                                        <label for="<?php echo $script['handle']; ?>">
                                        <input name="handles[<?php echo $script['handle']; ?>]" type="checkbox" id="<?php echo $script['handle']; ?>" value="<?php echo $script['path']; ?>" <?php echo (array_key_exists($script['handle'], $moved_scripts))?'checked="checked"':''; ?>>
                                        <strong><?php echo ucwords(str_replace('-', ' ', $script['handle'])); ?></strong> (<?php echo $script['path']; ?>)</label>
                                        <br>
                                    <?php endforeach; ?>
                                    <p class="description">Check the scripts you wish to move to the footer.</p>
                                </fieldset>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button('Submit') ?>
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
        $scripts = get_option('mes_front_end_scripts_moved' );

        if( !isset( $_POST['handles'] ) ) {
            if( !empty($scripts) ) {
                update_option('mes_front_end_scripts_moved', '' );

                echo '<div id="message" class="updated notice is-dismissible">
                    <p>Settings have been updated.</p>
                    <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
                </div>';

                return;
            }
        }

        // Check if there are any handles
        if( isset( $_POST['handles'] ) && ( $scripts != $_POST['handles'] ) ) {

            // Check for nonce
            check_admin_referer('mes-form-submission');

            // Update the options
            $updated = update_option('mes_front_end_scripts_moved', $_POST['handles'] );

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

