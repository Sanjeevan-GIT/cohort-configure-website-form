<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Admin menu and pages.
 */
class CWF_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_init', array( $this, 'handle_exports' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
    }

    public function enqueue_admin_styles( $hook_suffix ) {
        if ( strpos( $hook_suffix, 'cwf-' ) === false ) {
            return;
        }
        wp_enqueue_style(
            'cwf-admin-style',
            CWF_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            CWF_VERSION
        );
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'CWF Submissions', 'configure-website-form' ),
            __( 'CWF Forms', 'configure-website-form' ),
            'manage_options',
            'cwf-dashboard',
            array( $this, 'dashboard_page' ),
            'dashicons-feedback',
            30
        );

        add_submenu_page(
            'cwf-dashboard',
            __( 'Quote Submissions', 'configure-website-form' ),
            __( 'Quote Submissions', 'configure-website-form' ),
            'manage_options',
            'cwf-quotes',
            array( $this, 'quotes_page' )
        );

        add_submenu_page(
            'cwf-dashboard',
            __( 'Newsletter Subscribers', 'configure-website-form' ),
            __( 'Newsletter', 'configure-website-form' ),
            'manage_options',
            'cwf-newsletter',
            array( $this, 'newsletter_page' )
        );

        add_submenu_page(
            'cwf-dashboard',
            __( 'Form Settings', 'configure-website-form' ),
            __( 'Settings', 'configure-website-form' ),
            'manage_options',
            'cwf-settings',
            array( $this, 'settings_page' )
        );
    }

    public function register_settings() {
        register_setting(
            'cwf_settings_group',
            'cwf_recipient_emails',
            array(
                'type'              => 'string',
                'sanitize_callback' => array( $this, 'sanitize_recipient_emails' ),
            )
        );
        register_setting(
            'cwf_settings_group',
            'cwf_from_email',
            array(
                'type'              => 'string',
                'sanitize_callback' => array( $this, 'sanitize_from_email' ),
            )
        );
        register_setting(
            'cwf_settings_group',
            'cwf_smtp_enabled',
            array(
                'type'    => 'string',
                'default' => '',
            )
        );
        register_setting(
            'cwf_settings_group',
            'cwf_smtp_email',
            array(
                'type'              => 'string',
                'sanitize_callback' => array( $this, 'sanitize_from_email' ),
            )
        );
        register_setting(
            'cwf_settings_group',
            'cwf_smtp_app_password',
            array(
                'type'              => 'string',
                'sanitize_callback' => array( $this, 'sanitize_smtp_password' ),
            )
        );
        register_setting(
            'cwf_settings_group',
            'cwf_mailchimp_enabled',
            array(
                'type'    => 'string',
                'default' => '',
            )
        );
        register_setting(
            'cwf_settings_group',
            'cwf_mailchimp_api_key',
            array(
                'type'              => 'string',
                'sanitize_callback' => array( $this, 'sanitize_mailchimp_api_key' ),
            )
        );
        register_setting(
            'cwf_settings_group',
            'cwf_mailchimp_list_id',
            array(
                'type'              => 'string',
                'sanitize_callback' => array( $this, 'sanitize_mailchimp_list_id' ),
            )
        );
        register_setting(
            'cwf_settings_group',
            'cwf_mailchimp_status',
            array(
                'type'              => 'string',
                'default'           => 'pending',
                'sanitize_callback' => array( $this, 'sanitize_mailchimp_status' ),
            )
        );
    }

    /**
     * Sanitize SMTP app password. Keep existing if empty (user didn't change it).
     *
     * @param string $value Raw input from settings form.
     * @return string Sanitized password.
     */
    public function sanitize_smtp_password( $value ) {
        if ( ! is_string( $value ) || empty( trim( $value ) ) ) {
            return get_option( 'cwf_smtp_app_password', '' );
        }
        return $value;
    }

    /**
     * Sanitize Mailchimp API key; keep stored value if field left blank.
     *
     * @param string $value Raw input.
     * @return string
     */
    public function sanitize_mailchimp_api_key( $value ) {
        if ( ! is_string( $value ) || '' === trim( $value ) ) {
            return get_option( 'cwf_mailchimp_api_key', '' );
        }
        return trim( $value );
    }

    /**
     * Sanitize Mailchimp audience (list) ID.
     *
     * @param string $value Raw input.
     * @return string
     */
    public function sanitize_mailchimp_list_id( $value ) {
        if ( ! is_string( $value ) ) {
            return '';
        }
        $id = preg_replace( '/[^a-zA-Z0-9_-]/', '', trim( $value ) );
        return is_string( $id ) ? $id : '';
    }

    /**
     * Allow only Mailchimp member statuses used for newsletter signup.
     *
     * @param string $value Raw input.
     * @return string
     */
    public function sanitize_mailchimp_status( $value ) {
        if ( ! is_string( $value ) ) {
            return 'pending';
        }
        return in_array( $value, array( 'subscribed', 'pending' ), true ) ? $value : 'pending';
    }

    /**
     * Sanitize From email for better Gmail deliverability.
     *
     * @param string $value Raw input from settings form.
     * @return string Sanitized email or empty string.
     */
    public function sanitize_from_email( $value ) {
        if ( ! is_string( $value ) || empty( trim( $value ) ) ) {
            return '';
        }
        $email = sanitize_email( trim( $value ) );
        return is_email( $email ) ? $email : '';
    }

    /**
     * Sanitize recipient emails: trim, validate, and return comma-separated valid emails.
     *
     * @param string $value Raw input from settings form.
     * @return string Sanitized comma-separated emails.
     */
    public function sanitize_recipient_emails( $value ) {
        if ( ! is_string( $value ) || empty( trim( $value ) ) ) {
            return '';
        }
        $emails = array_map( 'trim', explode( ',', $value ) );
        $valid  = array_filter( $emails, 'is_email' );
        return implode( ', ', $valid );
    }

    public function handle_exports() {
        if ( ! is_admin() ) {
            return;
        }

        // Quote export.
        if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === 'cwf-quotes' && isset( $_GET['export'] ) && sanitize_text_field( wp_unslash( $_GET['export'] ) ) === 'quotes' ) {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'You do not have sufficient permissions.', 'configure-website-form' ) );
            }
            check_admin_referer( 'cwf_export_quotes' );
            $this->export_quotes();
            exit;
        }

        // Newsletter export.
        if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === 'cwf-newsletter' && isset( $_GET['export'] ) && sanitize_text_field( wp_unslash( $_GET['export'] ) ) === 'newsletter' ) {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'You do not have sufficient permissions.', 'configure-website-form' ) );
            }
            check_admin_referer( 'cwf_export_newsletter' );
            $this->export_newsletter();
            exit;
        }
    }

public function dashboard_page() {
    global $wpdb;
    $quotes_table      = $wpdb->prefix . 'cwf_quotes';
    $subscribers_table = $wpdb->prefix . 'cwf_subscribers';

    $total_quotes      = $wpdb->get_var( "SELECT COUNT(*) FROM $quotes_table" );
    $total_subscribers = $wpdb->get_var( "SELECT COUNT(*) FROM $subscribers_table" );
    $recent_quotes     = $wpdb->get_results( "SELECT * FROM $quotes_table ORDER BY submitted_at DESC LIMIT 5" );
    ?>
    <div class="wrap cwf-admin-wrap">
        <h1><?php esc_html_e( 'CWF Forms Dashboard', 'configure-website-form' ); ?></h1>

        <div class="cwf-stats-grid">
            <div class="cwf-stat-card">
                <div class="cwf-stat-icon">
                    <span class="dashicons dashicons-format-quote"></span>
                </div>
                <div class="cwf-stat-content">
                    <h3><?php esc_html_e( 'Total Quotes', 'configure-website-form' ); ?></h3>
                    <div class="cwf-stat-number"><?php echo intval( $total_quotes ); ?></div>
                </div>
            </div>

            <div class="cwf-stat-card">
                <div class="cwf-stat-icon">
                    <span class="dashicons dashicons-email-alt"></span>
                </div>
                <div class="cwf-stat-content">
                    <h3><?php esc_html_e( 'Newsletter Subscribers', 'configure-website-form' ); ?></h3>
                    <div class="cwf-stat-number"><?php echo intval( $total_subscribers ); ?></div>
                </div>
            </div>
        </div>

        <div class="cwf-recent-quotes">
            <h2><?php esc_html_e( 'Recent Quote Submissions', 'configure-website-form' ); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Name', 'configure-website-form' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'configure-website-form' ); ?></th>
                        <th><?php esc_html_e( 'Size', 'configure-website-form' ); ?></th>
                        <th><?php esc_html_e( 'Ecommerce', 'configure-website-form' ); ?></th>
                        <th><?php esc_html_e( 'Budget', 'configure-website-form' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'configure-website-form' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $recent_quotes as $quote ) : ?>
                        <tr>
                            <td><?php echo esc_html( $quote->name ); ?></td>
                            <td><?php echo esc_html( $quote->email ); ?></td>
                            <td><?php echo esc_html( $quote->size ); ?></td>
                            <td><?php echo esc_html( $quote->ecommerce ); ?></td>
                            <td><?php echo esc_html( $quote->budget ); ?></td>
                            <td><?php echo date_i18n( get_option( 'date_format' ) . ' H:i', strtotime( $quote->submitted_at ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

    public function quotes_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cwf_quotes';

        // Handle delete with nonce.
        if ( isset( $_GET['action'] ) && sanitize_text_field( wp_unslash( $_GET['action'] ) ) === 'delete' && isset( $_GET['id'] ) ) {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'You do not have sufficient permissions.', 'configure-website-form' ) );
            }
            $id = intval( $_GET['id'] );
            check_admin_referer( 'delete_cwf_quote_' . $id );

            $wpdb->delete( $table_name, array( 'id' => $id ) );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Entry deleted successfully.', 'configure-website-form' ) . '</p></div>';
        }

        // Pagination.
        $per_page     = 20;
        $current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $offset       = ( $current_page - 1 ) * $per_page;

        $total   = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ) );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Quote Submissions', 'configure-website-form' ); ?></h1>

            <div class="cwf-export-link">
                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=cwf-quotes&export=quotes' ), 'cwf_export_quotes' ) ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Export All to CSV', 'configure-website-form' ); ?>
                </a>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php esc_html_e( 'Name', 'configure-website-form' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'configure-website-form' ); ?></th>
                        <th><?php esc_html_e( 'Size', 'configure-website-form' ); ?></th>
                        <th><?php esc_html_e( 'Ecommerce', 'configure-website-form' ); ?></th>
                        <th><?php esc_html_e( 'Logo', 'configure-website-form' ); ?></th>
                        <th><?php esc_html_e( 'Copy', 'configure-website-form' ); ?></th>
                        <th><?php esc_html_e( 'Budget', 'configure-website-form' ); ?></th>
                        <th><?php esc_html_e( 'IP Address', 'configure-website-form' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'configure-website-form' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'configure-website-form' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $results as $row ) : ?>
                        <tr>
                            <td><?php echo intval( $row->id ); ?></td>
                            <td><?php echo esc_html( $row->name ); ?></td>
                            <td><?php echo esc_html( $row->email ); ?></td>
                            <td><?php echo esc_html( $row->size ); ?></td>
                            <td><?php echo esc_html( $row->ecommerce ); ?></td>
                            <td><?php echo esc_html( $row->logo ); ?></td>
                            <td><?php echo esc_html( $row->copy ); ?></td>
                            <td><?php echo esc_html( $row->budget ); ?></td>
                            <td><?php echo esc_html( $row->ip_address ); ?></td>
                            <td><?php echo date_i18n( get_option( 'date_format' ) . ' H:i', strtotime( $row->submitted_at ) ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $row->id ) ), 'delete_cwf_quote_' . $row->id ) ); ?>" class="button button-small" onclick="return confirm('<?php echo esc_js( __( 'Are you sure?', 'configure-website-form' ) ); ?>');">
                                    <?php esc_html_e( 'Delete', 'configure-website-form' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
            // Pagination.
            $total_pages = ceil( $total / $per_page );
            if ( $total_pages > 1 ) {
                echo '<div class="tablenav"><div class="tablenav-pages">';
                echo paginate_links( array(
                    'base'      => add_query_arg( 'paged', '%#%' ),
                    'format'    => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total'     => $total_pages,
                    'current'   => $current_page,
                ) );
                echo '</div></div>';
            }
            ?>
        </div>
        <?php
    }

    public function newsletter_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cwf_subscribers';

        // Handle delete with nonce.
        if ( isset( $_GET['action'] ) && sanitize_text_field( wp_unslash( $_GET['action'] ) ) === 'delete' && isset( $_GET['id'] ) ) {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'You do not have sufficient permissions.', 'configure-website-form' ) );
            }
            $id = intval( $_GET['id'] );
            check_admin_referer( 'delete_cwf_subscriber_' . $id );

            $wpdb->delete( $table_name, array( 'id' => $id ) );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Subscriber deleted successfully.', 'configure-website-form' ) . '</p></div>';
        }

        $per_page     = 20;
        $current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $offset       = ( $current_page - 1 ) * $per_page;

        $total   = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY subscribed_at DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ) );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Newsletter Subscribers', 'configure-website-form' ); ?></h1>

            <div class="cwf-export-link">
                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=cwf-newsletter&export=newsletter' ), 'cwf_export_newsletter' ) ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Export All to CSV', 'configure-website-form' ); ?>
                </a>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php esc_html_e( 'Email', 'configure-website-form' ); ?></th>
                        <th><?php esc_html_e( 'IP Address', 'configure-website-form' ); ?></th>
                        <th><?php esc_html_e( 'Subscribed Date', 'configure-website-form' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'configure-website-form' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $results as $row ) : ?>
                        <tr>
                            <td><?php echo intval( $row->id ); ?></td>
                            <td><?php echo esc_html( $row->email ); ?></td>
                            <td><?php echo esc_html( $row->ip_address ); ?></td>
                            <td><?php echo date_i18n( get_option( 'date_format' ) . ' H:i', strtotime( $row->subscribed_at ) ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $row->id ) ), 'delete_cwf_subscriber_' . $row->id ) ); ?>" class="button button-small" onclick="return confirm('<?php echo esc_js( __( 'Are you sure?', 'configure-website-form' ) ); ?>');">
                                    <?php esc_html_e( 'Delete', 'configure-website-form' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
            $total_pages = ceil( $total / $per_page );
            if ( $total_pages > 1 ) {
                echo '<div class="tablenav"><div class="tablenav-pages">';
                echo paginate_links( array(
                    'base'      => add_query_arg( 'paged', '%#%' ),
                    'format'    => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total'     => $total_pages,
                    'current'   => $current_page,
                ) );
                echo '</div></div>';
            }
            ?>
        </div>
        <?php
    }

public function settings_page() {
    ?>
    <div class="wrap cwf-admin-wrap">
        <h1><?php esc_html_e( 'CWF Form Settings', 'configure-website-form' ); ?></h1>

        <div class="cwf-settings-section">
            <form method="post" action="options.php">
                <?php settings_fields( 'cwf_settings_group' ); ?>
                <?php do_settings_sections( 'cwf_settings_group' ); ?>

                <table class="form-table cwf-settings-table">
                    <tr>
                        <th scope="row">
                            <label for="cwf_recipient_emails"><?php esc_html_e( 'Recipient Email(s)', 'configure-website-form' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="cwf_recipient_emails" name="cwf_recipient_emails" value="<?php echo esc_attr( get_option( 'cwf_recipient_emails', '' ) ); ?>" class="regular-text" placeholder="admin@example.com, info@example.com" />
                            <p class="description">
                                <?php esc_html_e( 'Enter email addresses separated by commas. If left empty, the WordPress admin email', 'configure-website-form' ); ?> (<?php echo esc_html( get_option( 'admin_email' ) ); ?>) <?php esc_html_e( 'will be used.', 'configure-website-form' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cwf_from_email"><?php esc_html_e( 'From Email', 'configure-website-form' ); ?></label>
                        </th>
                        <td>
                            <input type="email" id="cwf_from_email" name="cwf_from_email" value="<?php echo esc_attr( get_option( 'cwf_from_email', '' ) ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" />
                            <p class="description">
                                <?php esc_html_e( 'Leave empty to use noreply@yourdomain. When using SMTP below, set this to match your Gmail address.', 'configure-website-form' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Gmail SMTP', 'configure-website-form' ); ?></th>
                        <td>
                            <label>
                                <input type="hidden" name="cwf_smtp_enabled" value="0" />
                                <input type="checkbox" name="cwf_smtp_enabled" value="1" <?php checked( get_option( 'cwf_smtp_enabled', '' ), '1' ); ?> />
                                <?php esc_html_e( 'Enable Gmail SMTP for form emails', 'configure-website-form' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr class="cwf-smtp-row">
                        <th scope="row">
                            <label for="cwf_smtp_email"><?php esc_html_e( 'Gmail Address', 'configure-website-form' ); ?></label>
                        </th>
                        <td>
                            <input type="email" id="cwf_smtp_email" name="cwf_smtp_email" value="<?php echo esc_attr( get_option( 'cwf_smtp_email', '' ) ); ?>" class="regular-text" placeholder="you@gmail.com" />
                        </td>
                    </tr>
                    <tr class="cwf-smtp-row">
                        <th scope="row">
                            <label for="cwf_smtp_app_password"><?php esc_html_e( 'Gmail App Password', 'configure-website-form' ); ?></label>
                        </th>
                        <td>
                            <input type="password" id="cwf_smtp_app_password" name="cwf_smtp_app_password" value="" class="regular-text" placeholder="<?php echo get_option( 'cwf_smtp_app_password', '' ) ? '••••••••' : ''; ?>" autocomplete="new-password" />
                            <p class="description">
                                <?php esc_html_e( 'Create an App Password at', 'configure-website-form' ); ?> <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">myaccount.google.com/apppasswords</a>. <?php esc_html_e( 'Leave blank to keep current password.', 'configure-website-form' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2"><h2 class="title" style="margin:1.5em 0 0;padding-top:1em;border-top:1px solid #c3c4c7;"><?php esc_html_e( 'Mailchimp (newsletter in the form)', 'configure-website-form' ); ?></h2></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Sync to Mailchimp', 'configure-website-form' ); ?></th>
                        <td>
                            <label>
                                <input type="hidden" name="cwf_mailchimp_enabled" value="0" />
                                <input type="checkbox" name="cwf_mailchimp_enabled" value="1" <?php checked( get_option( 'cwf_mailchimp_enabled', '' ), '1' ); ?> />
                                <?php esc_html_e( 'Send plugin newsletter signups to a Mailchimp audience', 'configure-website-form' ); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e( 'Use the same Audience ID as your footer form to keep one list. The plugin form only collects email; name is optional in Mailchimp unless you add it via code (see filter cwf_mailchimp_merge_fields).', 'configure-website-form' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="cwf-mailchimp-row">
                        <th scope="row">
                            <label for="cwf_mailchimp_api_key"><?php esc_html_e( 'Mailchimp API key', 'configure-website-form' ); ?></label>
                        </th>
                        <td>
                            <input type="password" id="cwf_mailchimp_api_key" name="cwf_mailchimp_api_key" value="" class="regular-text" placeholder="<?php echo get_option( 'cwf_mailchimp_api_key', '' ) ? '••••••••••••••••' : ''; ?>" autocomplete="new-password" />
                            <p class="description">
                                <?php esc_html_e( 'Create a key under Account → Extras → API keys. Leave blank to keep the current key.', 'configure-website-form' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="cwf-mailchimp-row">
                        <th scope="row">
                            <label for="cwf_mailchimp_list_id"><?php esc_html_e( 'Audience ID', 'configure-website-form' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="cwf_mailchimp_list_id" name="cwf_mailchimp_list_id" value="<?php echo esc_attr( get_option( 'cwf_mailchimp_list_id', '' ) ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. a1b2c3d4e5', 'configure-website-form' ); ?>" />
                            <p class="description">
                                <?php esc_html_e( 'In Mailchimp: Audience → Settings → Audience name and defaults → Audience ID.', 'configure-website-form' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="cwf-mailchimp-row">
                        <th scope="row">
                            <label for="cwf_mailchimp_status"><?php esc_html_e( 'New subscribers', 'configure-website-form' ); ?></label>
                        </th>
                        <td>
                            <select id="cwf_mailchimp_status" name="cwf_mailchimp_status">
                                <option value="pending" <?php selected( get_option( 'cwf_mailchimp_status', 'pending' ), 'pending' ); ?>><?php esc_html_e( 'Pending — send Mailchimp confirmation (double opt-in)', 'configure-website-form' ); ?></option>
                                <option value="subscribed" <?php selected( get_option( 'cwf_mailchimp_status', 'pending' ), 'subscribed' ); ?>><?php esc_html_e( 'Subscribed — add immediately (single opt-in; use only where allowed)', 'configure-website-form' ); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>

        <div class="cwf-settings-section">
            <h2><?php esc_html_e( 'Current Settings', 'configure-website-form' ); ?></h2>
            <table class="widefat fixed striped cwf-settings-table">
                <tr>
                    <th><?php esc_html_e( 'WordPress Admin Email', 'configure-website-form' ); ?></th>
                    <td><?php echo esc_html( get_option( 'admin_email' ) ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'From Email', 'configure-website-form' ); ?></th>
                    <td><?php echo esc_html( get_option( 'cwf_from_email', '' ) ?: __( 'Using noreply@domain (default)', 'configure-website-form' ) ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Gmail SMTP', 'configure-website-form' ); ?></th>
                    <td>
                        <?php
                        if ( get_option( 'cwf_smtp_enabled', '' ) === '1' && get_option( 'cwf_smtp_email', '' ) && get_option( 'cwf_smtp_app_password', '' ) ) {
                            echo '<span style="color:#00a32a">✓ ' . esc_html__( 'Enabled', 'configure-website-form' ) . '</span> (' . esc_html( get_option( 'cwf_smtp_email', '' ) ) . ')';
                        } else {
                            echo '<em>' . esc_html__( 'Disabled', 'configure-website-form' ) . '</em>';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Custom Recipient Emails', 'configure-website-form' ); ?></th>
                    <td>
                        <?php
                        $custom_emails = get_option( 'cwf_recipient_emails', '' );
                        if ( ! empty( $custom_emails ) ) {
                            $emails = array_map( 'trim', explode( ',', $custom_emails ) );
                            echo '<ul style="margin:0">';
                            foreach ( $emails as $email ) {
                                if ( is_email( $email ) ) {
                                    echo '<li>' . esc_html( $email ) . '</li>';
                                } else {
                                    echo '<li style="color:#d63638">' . sprintf( esc_html__( 'Invalid: %s', 'configure-website-form' ), esc_html( $email ) ) . '</li>';
                                }
                            }
                            echo '</ul>';
                        } else {
                            echo '<em>' . esc_html__( 'No custom emails set. Using WordPress admin email.', 'configure-website-form' ) . '</em>';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Mailchimp newsletter sync', 'configure-website-form' ); ?></th>
                    <td>
                        <?php
                        if ( get_option( 'cwf_mailchimp_enabled', '' ) === '1' && get_option( 'cwf_mailchimp_api_key', '' ) && get_option( 'cwf_mailchimp_list_id', '' ) ) {
                            $st = get_option( 'cwf_mailchimp_status', 'pending' );
                            echo '<span style="color:#00a32a">✓ ' . esc_html__( 'Enabled', 'configure-website-form' ) . '</span> — ';
                            echo esc_html__( 'Audience:', 'configure-website-form' ) . ' ' . esc_html( get_option( 'cwf_mailchimp_list_id', '' ) );
                            echo ' (' . esc_html( $st === 'subscribed' ? __( 'subscribed immediately', 'configure-website-form' ) : __( 'pending confirmation', 'configure-website-form' ) ) . ')';
                        } elseif ( get_option( 'cwf_mailchimp_enabled', '' ) === '1' ) {
                            echo '<span style="color:#d63638">' . esc_html__( 'Enabled but incomplete — add API key and Audience ID.', 'configure-website-form' ) . '</span>';
                        } else {
                            echo '<em>' . esc_html__( 'Disabled', 'configure-website-form' ) . '</em>';
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php
}

    /**
     * Sanitize CSV cell to prevent formula injection in Excel/Sheets.
     *
     * @param string|int $value Cell value.
     * @return string Safe value for CSV.
     */
    private function sanitize_csv_cell( $value ) {
        $value = (string) $value;
        $first = substr( $value, 0, 1 );
        if ( in_array( $first, array( '=', '+', '-', '@', "\t" ), true ) ) {
            return "'" . $value;
        }
        return $value;
    }

    /**
     * Export quotes to CSV.
     */
    private function export_quotes() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cwf_quotes';
        $results = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY submitted_at DESC" );

        // Clear output buffers.
        while ( ob_get_level() ) {
            ob_end_clean();
        }

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="cwf-quotes-' . date( 'Y-m-d' ) . '.csv"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $output = fopen( 'php://output', 'w' );
        fprintf( $output, chr(0xEF) . chr(0xBB) . chr(0xBF) ); // UTF-8 BOM

        fputcsv( $output, array( 'ID', 'Name', 'Email', 'Size', 'Ecommerce', 'Logo', 'Copy', 'Budget', 'IP Address', 'Submitted Date' ) );

        foreach ( $results as $row ) {
            fputcsv( $output, array(
                $row->id,
                $this->sanitize_csv_cell( $row->name ),
                $this->sanitize_csv_cell( $row->email ),
                $this->sanitize_csv_cell( $row->size ),
                $this->sanitize_csv_cell( $row->ecommerce ),
                $this->sanitize_csv_cell( $row->logo ),
                $this->sanitize_csv_cell( $row->copy ),
                $this->sanitize_csv_cell( $row->budget ),
                $this->sanitize_csv_cell( $row->ip_address ),
                $row->submitted_at,
            ) );
        }

        fclose( $output );
        exit;
    }

    /**
     * Export newsletter subscribers to CSV.
     */
    private function export_newsletter() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cwf_subscribers';
        $results = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY subscribed_at DESC" );

        while ( ob_get_level() ) {
            ob_end_clean();
        }

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="cwf-newsletter-' . date( 'Y-m-d' ) . '.csv"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $output = fopen( 'php://output', 'w' );
        fprintf( $output, chr(0xEF) . chr(0xBB) . chr(0xBF) );

        fputcsv( $output, array( 'ID', 'Email', 'IP Address', 'Subscribed Date' ) );

        foreach ( $results as $row ) {
            fputcsv( $output, array(
                $row->id,
                $this->sanitize_csv_cell( $row->email ),
                $this->sanitize_csv_cell( $row->ip_address ),
                $row->subscribed_at,
            ) );
        }

        fclose( $output );
        exit;
    }
}

// Initialize admin.
new CWF_Admin();