<?php
/**
 * Plugin Name: WPS Limit Login - Quick Blacklist
 * Description: Adds a "Blacklist" button column to the WPS Limit Login log table for one-click IP blacklisting
 * Version: 1.0.0
 * Author: Ray Hollister
 * Author URI: https://rayhollister.com
 * Requires Plugins: wps-limit-login
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Direct access not permitted.' );
}

class WPS_Limit_Login_Quick_Blacklist {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Only load on the log page
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        
        // Add AJAX handlers
        add_action( 'wp_ajax_wps_quick_blacklist', array( $this, 'ajax_quick_blacklist' ) );
        
        // Add filter to modify the log table output
        add_action( 'admin_footer', array( $this, 'inject_custom_column' ) );
        
        // Add admin notices hook
        add_action( 'admin_notices', array( $this, 'show_admin_notice' ) );
        
        // Add checkbox to blacklist and whitelist pages
        add_action( 'admin_footer', array( $this, 'inject_auto_sort_checkbox' ) );
        
        // Intercept POST data BEFORE WPS processes it and sort if needed
        add_action( 'admin_init', array( $this, 'sort_post_data_before_save' ), 5 );
        
        // Add link to log page in failed login email
        add_filter( 'wp_mail', array( $this, 'add_log_link_to_email' ), 10, 1 );
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts( $hook ) {
        // Only load on WPS Limit Login settings page
        if ( 'settings_page_wps-limit-login' !== $hook ) {
            return;
        }

        // Only load on the log tab
        if ( ! isset( $_GET['tab'] ) || $_GET['tab'] !== 'log' ) {
            return;
        }

        wp_enqueue_script(
            'wps-quick-blacklist',
            plugin_dir_url( __FILE__ ) . 'wps-limit-login-blacklist-addon.js',
            array( 'jquery' ),
            '1.0.0',
            true
        );

        wp_localize_script(
            'wps-quick-blacklist',
            'wpsQuickBlacklist',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'wps-quick-blacklist-nonce' ),
            )
        );

        // Add inline CSS
        wp_add_inline_style( 'wp-admin', $this->get_inline_css() );
    }

    /**
     * Get inline CSS for the blacklist button
     */
    private function get_inline_css() {
        return '
            /* Match the Unlock cell structure */
            .wps-limit-login-log table tr {
                height: auto;
            }
            .wps_blacklist {
                padding: 0 !important;
                margin: 0 !important;
                line-height: 1 !important;
                vertical-align: middle !important;
            }
            .wps-quick-blacklist-btn {
                background: #000 !important;
                color: #fff !important;
                border: none !important;
                border-radius: 0 !important;
                width: 100%;
                display: block;
                text-align: center;
                box-shadow: none !important;
                margin: 0 !important;
                padding: 11px 0 !important;
                line-height: normal;
                cursor: pointer;
            }
            .wps-quick-blacklist-btn:hover {
                background: #333 !important;
                color: #fff !important;
                border: none !important;
            }
            .wps-quick-blacklist-btn.disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
            .wps_blacklisted {
                padding: 11px 0 !important;
                margin: 0 !important;
                line-height: normal;
                vertical-align: middle !important;
                text-align: center !important;
            }
            .wps_blacklisted span {
                color: #999;
            }
        ';
    }

    /**
     * Inject custom column using JavaScript
     * (Since we can't directly hook into the table rendering)
     */
    public function inject_custom_column() {
        // Only on the log page
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'wps-limit-login' ) {
            return;
        }
        if ( ! isset( $_GET['tab'] ) || $_GET['tab'] !== 'log' ) {
            return;
        }

        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Add header
            $('.wps-limit-login-log table tr.hide-mobile').each(function() {
                var lastTh = $(this).find('th').last();
                $('<th>Blacklist</th>').insertAfter(lastTh.prev());
            });

            // Add button to each row
            $('.wps-limit-login-log table tr').not('.hide-mobile').each(function() {
                var row = $(this);
                var ipCell = row.find('.limit-login-ip');
                if (ipCell.length === 0) return;

                // Extract IP address (remove the "IP : " prefix if present)
                var ipText = ipCell.text().trim();
                var ip = ipText.replace(/^IP\s*:\s*/i, '').trim();
                
                // Find the gateway cell
                var gatewayCell = row.find('.limit-login-gateway');
                if (gatewayCell.length === 0) return;

                // Create the blacklist button cell
                var blacklistCell = $('<td class="wps_blacklist"></td>');
                var blacklistBtn = $('<button type="button" class="button wps-quick-blacklist-btn" data-ip="' + ip + '">Blacklist</button>');
                
                blacklistCell.append(blacklistBtn);
                
                // Insert after gateway cell
                gatewayCell.after(blacklistCell);
            });
        });
        </script>
        <?php
    }

    /**
     * Show admin notice
     */
    public function show_admin_notice() {
        // Only show on the log page
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'wps-limit-login' ) {
            return;
        }
        if ( ! isset( $_GET['tab'] ) || $_GET['tab'] !== 'log' ) {
            return;
        }
        
        // Check for our notice transient
        $notice = get_transient( 'wps_quick_blacklist_notice' );
        if ( $notice ) {
            delete_transient( 'wps_quick_blacklist_notice' );
            ?>
            <div id="wps-blacklist-notice" class="updated fade" style="display:none;">
                <p><?php echo esc_html( $notice ); ?></p>
            </div>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Move notice after h1.wps-title
                var notice = $('#wps-blacklist-notice');
                var title = $('.wps-title');
                if (title.length > 0) {
                    notice.insertAfter(title);
                    notice.show();
                    
                    // Fade out after 5 seconds
                    setTimeout(function() {
                        notice.fadeOut();
                    }, 5000);
                }
            });
            </script>
            <?php
        }
    }

    /**
     * Inject auto-sort checkbox on blacklist and whitelist pages
     */
    public function inject_auto_sort_checkbox() {
        // Only on WPS Limit Login pages
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'wps-limit-login' ) {
            return;
        }
        
        // Check if we're on blacklist or whitelist tab
        $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : '';
        if ( $tab !== 'blacklist' && $tab !== 'whitelist' ) {
            return;
        }

        // Get the appropriate option based on tab
        $option_name = ( $tab === 'blacklist' ) ? 'wps_limit_login_auto_sort_blacklist' : 'wps_limit_login_auto_sort_whitelist';
        $field_name = ( $tab === 'blacklist' ) ? 'wps_auto_sort_blacklist' : 'wps_auto_sort_whitelist';
        $auto_sort = get_option( $option_name, false );
        
        // Pass the checked state as a JavaScript variable
        $is_checked = $auto_sort ? 'true' : 'false';
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Find the submit paragraph
            var submitPara = $('p.submit');
            if (submitPara.length === 0) return;
            
            // Check if checkbox should be checked
            var isChecked = <?php echo $is_checked; ?>;
            var checkedAttr = isChecked ? ' checked="checked"' : '';
            
            // Create checkbox container
            var checkboxHtml = '<p class="wps-auto-sort-option">' +
                '<label>' +
                '<input type="checkbox" name="<?php echo esc_attr( $field_name ); ?>" value="1"' + checkedAttr + ' /> ' +
                '<?php _e( 'Automatically sort IP addresses?', 'wps-limit-login' ); ?>' +
                '</label>' +
                '</p>';
            
            // Insert before the submit button
            submitPara.before(checkboxHtml);
        });
        </script>
        <style>
        .wps-auto-sort-option {
            margin-bottom: 15px;
        }
        .wps-auto-sort-option label {
            font-size: 14px;
            cursor: pointer;
        }
        </style>
        <?php
    }

    /**
     * Add log link to failed login email
     */
    public function add_log_link_to_email( $args ) {
        // Only modify WPS Limit Login emails (check subject)
        if ( strpos( $args['subject'], 'WPS Limit Login' ) === false && strpos( $args['subject'], 'failed login attempts' ) === false ) {
            return $args;
        }
        
        // Add link to the log page at the end of the email
        $log_url = admin_url( 'options-general.php?page=wps-limit-login&tab=log' );
        $args['message'] .= "\r\n\r\n" . __( 'View lockout log:', 'wps-limit-login' ) . "\r\n" . $log_url;
        
        return $args;
    }

    /**
     * Sort POST data BEFORE WPS plugin processes it
     * Runs at priority 5 (before WPS at priority 10)
     */
    public function sort_post_data_before_save() {
        // Only process on WPS Limit Login pages
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'wps-limit-login' ) {
            return;
        }
        
        // Only process when saving
        if ( ! isset( $_POST['update_options'] ) ) {
            return;
        }
        
        // Verify nonce
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wps-limit-login-settings' ) ) {
            return;
        }
        
        // Check which tab we're on via the referer
        $referer = isset( $_POST['_wp_http_referer'] ) ? $_POST['_wp_http_referer'] : '';
        
        // Handle blacklist tab
        if ( strpos( $referer, 'tab=blacklist' ) !== false ) {
            // Save auto-sort preference
            $auto_sort = isset( $_POST['wps_auto_sort_blacklist'] ) ? true : false;
            update_option( 'wps_limit_login_auto_sort_blacklist', $auto_sort );
            
            // If auto-sort is enabled, sort the POST data BEFORE WPS processes it
            if ( $auto_sort && ! empty( $_POST['wps_limit_login_blacklist_ips'] ) ) {
                // Parse the textarea content (same way WPS does it)
                $ips = explode( "\n", str_replace( "\r", "", stripslashes( $_POST['wps_limit_login_blacklist_ips'] ) ) );
                
                // Remove empty entries
                $ips = array_filter( $ips, function( $ip ) {
                    return trim( $ip ) !== '';
                });
                
                // Sort IPs
                $sorted = $this->sort_ips( $ips );
                
                // Put sorted IPs back into POST data as newline-separated string
                $_POST['wps_limit_login_blacklist_ips'] = implode( "\n", $sorted );
            }
        }
        
        // Handle whitelist tab
        elseif ( strpos( $referer, 'tab=whitelist' ) !== false ) {
            // Save auto-sort preference
            $auto_sort = isset( $_POST['wps_auto_sort_whitelist'] ) ? true : false;
            update_option( 'wps_limit_login_auto_sort_whitelist', $auto_sort );
            
            // If auto-sort is enabled, sort the POST data BEFORE WPS processes it
            if ( $auto_sort && ! empty( $_POST['wps_limit_login_whitelist_ips'] ) ) {
                // Parse the textarea content (same way WPS does it)
                $ips = explode( "\n", str_replace( "\r", "", stripslashes( $_POST['wps_limit_login_whitelist_ips'] ) ) );
                
                // Remove empty entries
                $ips = array_filter( $ips, function( $ip ) {
                    return trim( $ip ) !== '';
                });
                
                // Sort IPs
                $sorted = $this->sort_ips( $ips );
                
                // Put sorted IPs back into POST data as newline-separated string
                $_POST['wps_limit_login_whitelist_ips'] = implode( "\n", $sorted );
            }
        }
    }

    /**
     * Sort IP addresses in ascending order
     * Handles both IPv4 and IPv6
     * 
     * Sorting logic:
     * 1. IPv4 addresses: Sorted numerically using ip2long() for proper ordering
     *    (e.g., 10.0.0.1 before 192.168.1.1, not alphabetically)
     * 2. IPv6 addresses: Sorted alphabetically (standard lexical sort)
     * 3. CIDR ranges: Sorted alphabetically
     * 
     * Final order: All IPv4, then all IPv6, then all ranges
     * 
     * Best practice rationale:
     * - IPv4 numeric sorting prevents counterintuitive alphabetical ordering
     * - Grouping by type (IPv4/IPv6/ranges) makes the list more scannable
     * - This matches common network administration tools and practices
     */
    private function sort_ips( $ips ) {
        if ( empty( $ips ) || ! is_array( $ips ) ) {
            return $ips;
        }
        
        // Separate IPv4 and IPv6
        $ipv4 = array();
        $ipv6 = array();
        $ranges = array();
        
        foreach ( $ips as $ip ) {
            // Check if it's a range (contains /)
            if ( strpos( $ip, '/' ) !== false ) {
                $ranges[] = $ip;
            } elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
                $ipv6[] = $ip;
            } else {
                $ipv4[] = $ip;
            }
        }
        
        // Sort IPv4 using ip2long for proper numeric sorting
        usort( $ipv4, function( $a, $b ) {
            $a_long = ip2long( $a );
            $b_long = ip2long( $b );
            
            if ( $a_long === false || $b_long === false ) {
                return strcmp( $a, $b );
            }
            
            return $a_long - $b_long;
        });
        
        // Sort IPv6 using string comparison (good enough for most cases)
        sort( $ipv6 );
        
        // Sort ranges
        sort( $ranges );
        
        // Combine: IPv4, then IPv6, then ranges
        return array_merge( $ipv4, $ipv6, $ranges );
    }

    /**
     * Insert IP into blacklist in sorted order
     */
    private function insert_ip_sorted( $ip, $blacklist ) {
        // If auto-sort is not enabled, just append
        if ( ! get_option( 'wps_limit_login_auto_sort_blacklist', false ) ) {
            $blacklist[] = $ip;
            return $blacklist;
        }
        
        // Add the new IP
        $blacklist[] = $ip;
        
        // Sort and return
        return $this->sort_ips( $blacklist );
    }

    /**
     * AJAX handler for quick blacklist
     */
    public function ajax_quick_blacklist() {
        // Verify nonce
        check_ajax_referer( 'wps-quick-blacklist-nonce', 'nonce' );

        // Check user permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
        }

        // Get IP address
        $ip = isset( $_POST['ip'] ) ? sanitize_text_field( $_POST['ip'] ) : '';
        
        if ( empty( $ip ) ) {
            wp_send_json_error( array( 'message' => 'Invalid IP address' ) );
        }

        // Validate IP format
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            // Check if it's an IPv6
            if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
                wp_send_json_error( array( 'message' => 'Invalid IP format' ) );
            }
        }

        // Get current blacklist
        $blacklist = get_option( 'wps_limit_login_blacklist', array() );
        if ( ! is_array( $blacklist ) ) {
            $blacklist = array();
        }

        // Check if IP is already blacklisted
        $already_blacklisted = in_array( $ip, $blacklist, true );

        $message = '';
        
        if ( ! $already_blacklisted ) {
            // Add IP to blacklist (sorted if auto-sort is enabled)
            $blacklist = $this->insert_ip_sorted( $ip, $blacklist );
            update_option( 'wps_limit_login_blacklist', $blacklist );
            $message = 'IP address added to blacklist.';
        } else {
            $message = 'IP address already in the Blacklist.';
        }

        // Clear log entries for this IP
        $this->clear_log_for_ip( $ip );

        // Set transient for admin notice (will show on next page load)
        set_transient( 'wps_quick_blacklist_notice', $message, 30 );

        wp_send_json_success( array(
            'message' => $message,
            'already_blacklisted' => $already_blacklisted
        ) );
    }

    /**
     * Clear log entries for a specific IP
     */
    private function clear_log_for_ip( $ip ) {
        $log = get_option( 'wps_limit_login_logged', array() );
        
        if ( ! is_array( $log ) || empty( $log ) ) {
            return;
        }

        // The log structure is: $log[$ip][$username] = array(...)
        // Remove all entries for this specific IP
        if ( isset( $log[ $ip ] ) ) {
            unset( $log[ $ip ] );
            update_option( 'wps_limit_login_logged', $log );
        }
    }
}

// Initialize the plugin
add_action( 'plugins_loaded', array( 'WPS_Limit_Login_Quick_Blacklist', 'get_instance' ) );
