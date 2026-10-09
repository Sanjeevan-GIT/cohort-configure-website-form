<?php
/**
 * Plugin Name: Configure Website Form
 * Description: Multi-step website configuration form with email submission and admin dashboard.
 * Version:     1.5
 * Author:      Cohort Creative
 * Author URI:  https://cohort-creative.com
 * Text Domain: configure-website-form
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin constants.
define( 'CWF_VERSION', '1.5' );
define( 'CWF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CWF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load plugin text domain for internationalization.
 */
function cwf_load_textdomain() {
    load_plugin_textdomain(
        'configure-website-form',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}
add_action( 'plugins_loaded', 'cwf_load_textdomain' );

/**
 * Configure PHPMailer to use Gmail SMTP when CWF SMTP is enabled.
 * Only applies when this plugin is sending emails (not Contact Form 7 or other plugins).
 */
function cwf_phpmailer_smtp( $phpmailer ) {
    global $cwf_sending_email;
    if ( empty( $cwf_sending_email ) ) {
        return;
    }
    if ( get_option( 'cwf_smtp_enabled', '' ) !== '1' ) {
        return;
    }
    $smtp_email = get_option( 'cwf_smtp_email', '' );
    $smtp_pass  = get_option( 'cwf_smtp_app_password', '' );
    if ( ! is_email( $smtp_email ) || empty( $smtp_pass ) ) {
        return;
    }
    $phpmailer->isSMTP();
    $phpmailer->Host       = 'smtp.gmail.com';
    $phpmailer->Port       = 587;
    $phpmailer->SMTPSecure = 'tls';
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Username   = $smtp_email;
    $phpmailer->Password   = $smtp_pass;
    $phpmailer->From       = $smtp_email;
    $phpmailer->FromName   = 'Cohort Creative';
}
add_action( 'phpmailer_init', 'cwf_phpmailer_smtp' );

// Create database tables on plugin activation.
function cwf_activate_plugin() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Table for quote submissions.
    $table_name = $wpdb->prefix . 'cwf_quotes';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        size varchar(50) NOT NULL,
        ecommerce varchar(100) NOT NULL,
        logo varchar(100) NOT NULL,
        copy varchar(100) NOT NULL,
        budget varchar(50) NOT NULL,
        ip_address varchar(45) NOT NULL,
        submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY submitted_at (submitted_at),
        KEY email (email)
    ) $charset_collate;";

    // Table for newsletter subscribers.
    $subscribers_table = $wpdb->prefix . 'cwf_subscribers';
    $sql2 = "CREATE TABLE IF NOT EXISTS $subscribers_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        email varchar(100) NOT NULL UNIQUE,
        ip_address varchar(45) NOT NULL,
        subscribed_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY subscribed_at (subscribed_at)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
    dbDelta( $sql2 );
}
register_activation_hook( __FILE__, 'cwf_activate_plugin' );

// Enqueue frontend assets.
function cwf_enqueue_assets() {
    if ( ! is_admin() ) {
        wp_enqueue_style(
            'cwf-style',
            CWF_PLUGIN_URL . 'assets/css/style.css',
            array(),
            CWF_VERSION
        );

        wp_enqueue_script(
            'cwf-script',
            CWF_PLUGIN_URL . 'assets/js/script.js',
            array( 'jquery' ),
            CWF_VERSION,
            true
        );

        wp_localize_script(
            'cwf-script',
            'cwf_ajax',
            array(
                'ajax_url'   => admin_url( 'admin-ajax.php' ),
                'plugin_url' => CWF_PLUGIN_URL,
                'nonce'      => wp_create_nonce( 'cwf_newsletter_action' ), // For newsletter.
            )
        );

        // Floating button and popup assets.
        wp_enqueue_style(
            'cwf-popup-button',
            CWF_PLUGIN_URL . 'assets/css/popup-button.css',
            array(),
            CWF_VERSION
        );
        wp_enqueue_script(
            'cwf-popup-button',
            CWF_PLUGIN_URL . 'assets/js/popup-button.js',
            array(),
            CWF_VERSION,
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'cwf_enqueue_assets' );

/**
 * Output floating "Build A Website" button and popup on frontend.
 */
function cwf_render_floating_button_popup() {
    if ( is_admin() ) {
        return;
    }
    ?>
    <div class="cwf-admin-side-button">
        <a href="#" id="cwf-open-admin-popup"><?php esc_html_e( 'Build A Website', 'configure-website-form' ); ?></a>
    </div>

    <div id="cwf-admin-popup" class="cwf-admin-popup">
        <div class="cwf-admin-popup-content">
            <span class="cwf-admin-popup-close" aria-label="<?php esc_attr_e( 'Close', 'configure-website-form' ); ?>">&times;</span>
            <?php echo do_shortcode( '[configure_website_form]' ); ?>
        </div>
    </div>
    <?php
}
add_action( 'wp_footer', 'cwf_render_floating_button_popup' );

// Shortcode to display the form.
function cwf_render_form() {
    ob_start();
    include CWF_PLUGIN_DIR . 'templates/form-layout.php';
    return ob_get_clean();
}
add_shortcode( 'configure_website_form', 'cwf_render_form' );

// AJAX handler for quote submission.
function cwf_send_email() {
    // 1. Nonce verification.
    if ( ! isset( $_POST['cwf_nonce'] ) || ! wp_verify_nonce( $_POST['cwf_nonce'], 'cwf_quote_submit' ) ) {
        wp_send_json_error( esc_html__( 'Security check failed. Please refresh the page and try again.', 'configure-website-form' ) );
    }

    // 2. Rate limiting.
    $ip = $_SERVER['REMOTE_ADDR'];
    $transient_key = 'cwf_submit_' . md5( $ip );
    $submissions = get_transient( $transient_key );
    if ( $submissions && $submissions >= 3 ) {
        wp_send_json_error( esc_html__( 'Too many submissions from your IP. Please try again later.', 'configure-website-form' ) );
    }

    // 3. Check required fields.
    if ( ! isset( $_POST['name'] ) || ! isset( $_POST['email'] ) ) {
        wp_send_json_error( esc_html__( 'Missing required fields', 'configure-website-form' ) );
    }

    // 4. Sanitize and validate.
    $name  = sanitize_text_field( $_POST['name'] );
    $email = sanitize_email( $_POST['email'] );
    if ( ! is_email( $email ) ) {
        wp_send_json_error( esc_html__( 'Invalid email address format', 'configure-website-form' ) );
    }

    $size      = isset( $_POST['size'] ) ? sanitize_text_field( $_POST['size'] ) : 'Not selected';
    $ecommerce = isset( $_POST['ecommerce'] ) ? sanitize_text_field( $_POST['ecommerce'] ) : 'Not selected';
    $logo      = isset( $_POST['logo'] ) ? sanitize_text_field( $_POST['logo'] ) : 'Not selected';
    $copy      = isset( $_POST['copy'] ) ? sanitize_text_field( $_POST['copy'] ) : 'Not selected';
    $budget    = isset( $_POST['budget'] ) ? sanitize_text_field( $_POST['budget'] ) : 'Not selected';

    // 5. Get recipients.
    $custom_emails = get_option( 'cwf_recipient_emails', '' );
    $admin_email   = get_option( 'admin_email' );
    $recipients    = array();

    if ( ! empty( $custom_emails ) ) {
        $emails = array_map( 'trim', explode( ',', $custom_emails ) );
        foreach ( $emails as $recipient ) {
            if ( is_email( $recipient ) ) {
                $recipients[] = $recipient;
            }
        }
    }
    if ( empty( $recipients ) ) {
        $recipients[] = $admin_email;
    }

    $subject = __( 'New Website Configuration Quote Request', 'configure-website-form' );

    // 6. Build email message (escaped).
    $message = '
    <html>
    <head><title>' . esc_html( $subject ) . '</title></head>
    <body>
        <h2 style="color: #9D4D22;">' . esc_html__( 'Website Configuration Quote Request', 'configure-website-form' ) . '</h2>
        <h3>' . esc_html__( 'Selected Options:', 'configure-website-form' ) . '</h3>
        <table border="1" cellpadding="10" style="border-collapse: collapse; border-color: #BCAC9F;">
            <tr style="background: #F7F5F0;"><th>' . esc_html__( 'Section', 'configure-website-form' ) . '</th><th>' . esc_html__( 'Selection', 'configure-website-form' ) . '</th></tr>
            <tr><td><strong>' . esc_html__( 'Size', 'configure-website-form' ) . '</strong></td><td>' . esc_html( $size ) . '</td></tr>
            <tr><td><strong>' . esc_html__( 'Ecommerce', 'configure-website-form' ) . '</strong></td><td>' . esc_html( $ecommerce ) . '</td></tr>
            <tr><td><strong>' . esc_html__( 'Logo Design', 'configure-website-form' ) . '</strong></td><td>' . esc_html( $logo ) . '</td></tr>
            <tr><td><strong>' . esc_html__( 'Copy Writing', 'configure-website-form' ) . '</strong></td><td>' . esc_html( $copy ) . '</td></tr>
            <tr><td><strong>' . esc_html__( 'Budget', 'configure-website-form' ) . '</strong></td><td>' . esc_html( $budget ) . '</td></tr>
        </table>
        <h3>' . esc_html__( 'Contact Information:', 'configure-website-form' ) . '</h3>
        <p><strong>' . esc_html__( 'Name:', 'configure-website-form' ) . '</strong> ' . esc_html( $name ) . '</p>
        <p><strong>' . esc_html__( 'Email:', 'configure-website-form' ) . '</strong> ' . esc_html( $email ) . '</p>
        <p><em>' . sprintf( esc_html__( 'Submitted: %s', 'configure-website-form' ), date( 'Y-m-d H:i:s' ) ) . '</em></p>
        <p><em>' . sprintf( esc_html__( 'IP Address: %s', 'configure-website-form' ), esc_html( $ip ) ) . '</em></p>
    </body>
    </html>
    ';

    // Sanitize name for email headers to prevent header injection (remove newlines/carriage returns).
    $name_for_headers = str_replace( array( "\r", "\n", "\0" ), '', $name );

    $domain     = parse_url( site_url(), PHP_URL_HOST );
    $from_email = get_option( 'cwf_from_email', '' );
    $smtp_email = get_option( 'cwf_smtp_email', '' );
    if ( is_email( $from_email ) ) {
        $from_addr = $from_email;
    } elseif ( get_option( 'cwf_smtp_enabled', '' ) === '1' && is_email( $smtp_email ) ) {
        $from_addr = $smtp_email;
    } else {
        $from_addr = 'noreply@' . $domain;
    }
    $headers    = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: Cohort Creative <' . $from_addr . '>',
        'Reply-To: ' . $name_for_headers . ' <' . $email . '>',
    );

    $mail_sent = false;
    global $cwf_sending_email;
    $cwf_sending_email = true;
    try {
        foreach ( $recipients as $recipient ) {
            if ( wp_mail( $recipient, $subject, $message, $headers ) ) {
                $mail_sent = true;
            }
        }
    } finally {
        $cwf_sending_email = false;
    }

    // 7. Rate limiting counter.
    set_transient( $transient_key, $submissions ? $submissions + 1 : 1, HOUR_IN_SECONDS );

    // 8. Save to database.
    global $wpdb;
    $table_name = $wpdb->prefix . 'cwf_quotes';
    $inserted = $wpdb->insert(
        $table_name,
        array(
            'name'       => $name,
            'email'      => $email,
            'size'       => $size,
            'ecommerce'  => $ecommerce,
            'logo'       => $logo,
            'copy'       => $copy,
            'budget'     => $budget,
            'ip_address' => $ip,
            'submitted_at' => current_time( 'mysql' ),
        )
    );

    if ( ! $inserted ) {
        error_log( '[CWF] Database insert failed for quote: ' . $wpdb->last_error );
    }

    if ( $mail_sent ) {
        // Send confirmation to user.
        $user_subject = __( 'Your Website Configuration Quote Request - Cohort Creative', 'configure-website-form' );
        $user_message = '
        <html>
        <head><title>' . esc_html( $user_subject ) . '</title></head>
        <body>
            <h2 style="color: #9D4D22;">' . esc_html__( 'Thank you for your quote request!', 'configure-website-form' ) . '</h2>
            <p>' . esc_html__( 'We have received your website configuration request and will be in touch within 24-48 hours.', 'configure-website-form' ) . '</p>
            <h3>' . esc_html__( 'Your Selections:', 'configure-website-form' ) . '</h3>
            <table border="1" cellpadding="10" style="border-collapse: collapse; border-color: #BCAC9F;">
                <tr style="background: #F7F5F0;"><th>' . esc_html__( 'Section', 'configure-website-form' ) . '</th><th>' . esc_html__( 'Selection', 'configure-website-form' ) . '</th></tr>
                <tr><td><strong>' . esc_html__( 'Size', 'configure-website-form' ) . '</strong></td><td>' . esc_html( $size ) . '</td></tr>
                <tr><td><strong>' . esc_html__( 'Ecommerce', 'configure-website-form' ) . '</strong></td><td>' . esc_html( $ecommerce ) . '</td></tr>
                <tr><td><strong>' . esc_html__( 'Logo Design', 'configure-website-form' ) . '</strong></td><td>' . esc_html( $logo ) . '</td></tr>
                <tr><td><strong>' . esc_html__( 'Copy Writing', 'configure-website-form' ) . '</strong></td><td>' . esc_html( $copy ) . '</td></tr>
                <tr><td><strong>' . esc_html__( 'Budget', 'configure-website-form' ) . '</strong></td><td>' . esc_html( $budget ) . '</td></tr>
            </table>
            <p>' . esc_html__( 'Best regards,', 'configure-website-form' ) . '<br><strong>Cohort Creative Team</strong></p>
            <p style="font-size: 12px; color: #666;">' . esc_html__( 'This is an automated confirmation. Please do not reply to this email.', 'configure-website-form' ) . '</p>
        </body>
        </html>
        ';

        $reply_to = ! empty( $recipients ) ? $recipients[0] : get_option( 'admin_email' );
        $user_headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Cohort Creative <' . $from_addr . '>',
            'Reply-To: Cohort Creative <' . $reply_to . '>',
        );
        $cwf_sending_email = true;
        try {
            wp_mail( $email, $user_subject, $user_message, $user_headers );
        } finally {
            $cwf_sending_email = false;
        }

        wp_send_json_success( esc_html__( 'Quote request sent successfully! Check your email for confirmation.', 'configure-website-form' ) );
    } else {
        error_log( '[CWF] Email sending failed to recipients: ' . implode( ', ', $recipients ) );
        global $phpmailer;
        if ( isset( $phpmailer ) && ! empty( $phpmailer->ErrorInfo ) ) {
            error_log( '[CWF] PHPMailer Error: ' . $phpmailer->ErrorInfo );
        }
        wp_send_json_error( esc_html__( 'Failed to send email. Please try again or contact us directly.', 'configure-website-form' ) );
    }
}
add_action( 'wp_ajax_cwf_submit', 'cwf_send_email' );
add_action( 'wp_ajax_nopriv_cwf_submit', 'cwf_send_email' );

/**
 * Extract Mailchimp data center suffix from API key (e.g. key ending in -us21).
 *
 * @param string $api_key Mailchimp API key.
 * @return string Data center subdomain or empty if invalid.
 */
function cwf_mailchimp_get_datacenter( $api_key ) {
    if ( ! is_string( $api_key ) || ! preg_match( '/-([a-z0-9]+)$/i', $api_key, $m ) ) {
        return '';
    }
    return strtolower( $m[1] );
}

/**
 * Add or update a Mailchimp list member (same audience as embedded forms when using the same list ID).
 *
 * @param string $email Subscriber email.
 * @return true|WP_Error True on success; WP_Error on failure when Mailchimp is enabled.
 */
function cwf_mailchimp_subscribe( $email ) {
    if ( get_option( 'cwf_mailchimp_enabled', '' ) !== '1' ) {
        return true;
    }

    $api_key = trim( (string) get_option( 'cwf_mailchimp_api_key', '' ) );
    $list_id = trim( (string) get_option( 'cwf_mailchimp_list_id', '' ) );

    if ( $api_key === '' || $list_id === '' ) {
        return new WP_Error(
            'cwf_mc_config',
            __( 'Mailchimp is enabled but the API key or audience ID is missing. Check CWF Form Settings.', 'configure-website-form' )
        );
    }

    $dc = cwf_mailchimp_get_datacenter( $api_key );
    if ( $dc === '' ) {
        return new WP_Error(
            'cwf_mc_key',
            __( 'Invalid Mailchimp API key. It should end with a data center code (e.g. -us21).', 'configure-website-form' )
        );
    }

    $status = get_option( 'cwf_mailchimp_status', 'pending' );
    if ( ! in_array( $status, array( 'subscribed', 'pending' ), true ) ) {
        $status = 'pending';
    }

    $hash = md5( strtolower( $email ) );
    $url  = sprintf(
        'https://%s.api.mailchimp.com/3.0/lists/%s/members/%s',
        rawurlencode( $dc ),
        rawurlencode( $list_id ),
        rawurlencode( $hash )
    );

    $body = array(
        'email_address' => $email,
        'status_if_new' => $status,
    );

    $merge_fields = apply_filters( 'cwf_mailchimp_merge_fields', array(), $email );
    if ( is_array( $merge_fields ) && ! empty( $merge_fields ) ) {
        $body['merge_fields'] = $merge_fields;
    }

    $response = wp_remote_request(
        $url,
        array(
            'method'  => 'PUT',
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( 'anystring:' . $api_key ),
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 15,
        )
    );

    if ( is_wp_error( $response ) ) {
        error_log( '[CWF] Mailchimp request error: ' . $response->get_error_message() );
        return new WP_Error(
            'cwf_mc_http',
            __( 'Could not reach Mailchimp. Please try again in a moment.', 'configure-website-form' )
        );
    }

    $code = wp_remote_retrieve_response_code( $response );
    $raw  = wp_remote_retrieve_body( $response );
    $json = is_string( $raw ) ? json_decode( $raw, true ) : null;

    if ( $code >= 200 && $code < 300 ) {
        return true;
    }

    $title = is_array( $json ) && isset( $json['title'] ) ? (string) $json['title'] : '';
    if ( $code === 400 && $title === 'Member Exists' ) {
        return true;
    }

    error_log( '[CWF] Mailchimp API error ' . (string) $code . ': ' . $raw );

    return new WP_Error(
        'cwf_mc_api',
        __( 'Signup could not be completed. If this continues, contact the site owner.', 'configure-website-form' )
    );
}

// AJAX handler for newsletter signup.
function cwf_newsletter_signup() {
    // Verify nonce.
    if ( ! isset( $_POST['newsletter_nonce'] ) || ! wp_verify_nonce( $_POST['newsletter_nonce'], 'cwf_newsletter_action' ) ) {
        wp_send_json_error( esc_html__( 'Security check failed', 'configure-website-form' ) );
    }

    $email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
    if ( ! is_email( $email ) ) {
        wp_send_json_error( esc_html__( 'Please enter a valid email address', 'configure-website-form' ) );
    }

    // Rate limiting.
    $ip = $_SERVER['REMOTE_ADDR'];
    $transient_key = 'cwf_newsletter_' . md5( $ip );
    $signups = get_transient( $transient_key );
    if ( $signups && $signups >= 2 ) {
        wp_send_json_error( esc_html__( 'Too many signup attempts. Please try again later.', 'configure-website-form' ) );
    }

    $mc_result = cwf_mailchimp_subscribe( $email );
    if ( is_wp_error( $mc_result ) ) {
        wp_send_json_error( $mc_result->get_error_message() );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'cwf_subscribers';
    $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE email = %s", $email ) );

    if ( ! $exists ) {
        $wpdb->insert(
            $table_name,
            array(
                'email'      => $email,
                'ip_address' => $ip,
                'subscribed_at' => current_time( 'mysql' ),
            )
        );
    }

    // Notify admin (optional, you could skip for duplicates).
    $admin_email = get_option( 'admin_email' );
    $subject = __( 'New Newsletter Signup', 'configure-website-form' );
    $message = sprintf( __( 'New newsletter signup: %s', 'configure-website-form' ), $email );
    global $cwf_sending_email;
    $cwf_sending_email = true;
    try {
        wp_mail( $admin_email, $subject, $message );
    } finally {
        $cwf_sending_email = false;
    }

    set_transient( $transient_key, $signups ? $signups + 1 : 1, HOUR_IN_SECONDS );

    wp_send_json_success( esc_html__( 'Thanks for signing up! Check your email for your welcome gift.', 'configure-website-form' ) );
}
add_action( 'wp_ajax_cwf_newsletter_signup', 'cwf_newsletter_signup' );
add_action( 'wp_ajax_nopriv_cwf_newsletter_signup', 'cwf_newsletter_signup' );

/**
 * Admin area functions.
 */
require_once CWF_PLUGIN_DIR . 'admin/class-admin.php';

// Deactivation hook (optional).
function cwf_deactivate_plugin() {
    // Nothing needed; uninstall.php handles cleanup.
}
register_deactivation_hook( __FILE__, 'cwf_deactivate_plugin' );