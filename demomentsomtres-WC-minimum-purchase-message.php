<?php
/**
 * Plugin Name: DeMomentSomTres Woocommerce Free Shipping Message
 * Plugin URI:  http://demomentsomtres.com/english/wordpress-plugins/woocommerce-minimum-purchase-message/
 * Version: 0.1
 * Author URI: demomentsomtres.com
 * Author: Marc Queralt
 * Description: Shows a message if the user didn't reach the minimum purchase order
 * Requires at least: 3.9
 * Tested up to: 3.9.1
 * License: GPLv3 or later
 * License URI: http://www.opensource.org/licenses/gpl-license.php
 */
define('DMS3_WCMPM_TEXT_DOMAIN', 'DeMomentSomTres-WC-minPurchaseMessage');

if (!in_array('demomentsomtres-tools/demomentsomtres-tools.php', apply_filters('active_plugins', get_option('active_plugins')))):
    add_action('admin_notices', 'DMS3_WCMPM_messageNoTools');
else:
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))):
        add_action('admin_notices', 'DMS3_WCMPM_messageNoWC');
    else:
        $dms3_wcmpm = new DeMomentSomTresWCminimumPurchaseMessage();
    endif;
endif;

function DMS3_WCMPM_messageNoTools() {
    ?>
    <div class="error">
        <p><?php _e('The DeMomentSomTres WooCommerce Minimum Purchase plugin requires the free DeMomentSomTres Tools plugin.', DMS3_WCMPM_TEXT_DOMAIN); ?>
            <br/>
            <a href="http://demomentsomtres.com/english/wordpress-plugins/demomentsomtres-tools/?utm_source=web&utm_medium=wordpress&utm_campaign=adminnotice&utm_term=dms3WCminimumPurchase" target="_blank"><?php _e('Download it here', DMS3_WCMPM_TEXT_DOMAIN); ?></a>
        </p>
    </div>
    <?php
}

function DMS3_WCMPM_messageNoWC() {
    ?>
    <div class="error">
        <p>
            <?php _e('The DeMomentSomTres WooCommerce Minimum Purchase plugin requires WooCommerce.', DMS3_WCDD_TEXT_DOMAIN); ?>
        </p>
    </div>
    <?php
}

class DeMomentSomTresWCminimumPurchaseMessage {

    const TEXT_DOMAIN = DMS3_WCMPM_TEXT_DOMAIN;
    const MENU_SLUG = 'dmst_wc_minimumPurchase';
    const OPTIONS = 'dmst_wc_minimum_purchase_options';
    const PAGE = 'dmst_wc_minimumpurchase';
    const SECTION_1 = 'dmst_wcmpm_1';
    const OPTION_MINIMUM = 'minimumTotal';
    const OPTION_MESSAGE = 'message';

    private $pluginURL;
    private $pluginPath;
    private $langDir;

    /**
     * @since 1.0
     */
    function __construct() {
        $this->pluginURL = plugin_dir_url(__FILE__);
        $this->pluginPath = plugin_dir_path(__FILE__);
        $this->langDir = dirname(plugin_basename(__FILE__)) . '/languages';

        add_action('plugins_loaded', array(&$this, 'plugin_init'));
        add_action('admin_menu', array(&$this, 'admin_menu'));
        add_action('admin_init', array(&$this, 'admin_init'));

        add_action('woocommerce_before_cart_contents', array(&$this, 'message'));
        add_action('woocommerce_before_checkout_form', array(&$this, 'message'));
    }

    /**
     * @since 1.0
     */
    function plugin_init() {
        load_plugin_textdomain(DMS3_WCMPM_TEXT_DOMAIN, false, $this->langDir);
    }

    /**
     * @since 1.0
     */
    function admin_menu() {
        add_submenu_page('woocommerce', __('DeMomentSomTres Minimum Purchase Message', self::TEXT_DOMAIN), __('DeMomentSomTres Minimum Purchase Message', self::TEXT_DOMAIN), 'manage_options', self::MENU_SLUG, array(&$this, 'admin_page'));
    }

    /**
     * @since 1.0
     */
    function admin_page() {
        ?>
        <div class="wrap">
            <h2><?php _e('DeMomentSomTres Minimum Purchase Message', self::TEXT_DOMAIN); ?></h2>
            <form action="options.php" method="post">
                <?php settings_fields(self::OPTIONS); ?>
                <?php do_settings_sections(self::PAGE); ?>
                <br/>
                <input name="Submit" class="button button-primary" type="submit" value="<?php _e('Save Changes', self::TEXT_DOMAIN); ?>"/>
            </form>
        </div>
        <div style="background-color:#eee;/*display:none;*/">
            <h2><?php _e('Options', self::TEXT_DOMAIN); ?></h2>
            <pre style="font-size:0.8em;"><?php print_r(get_option(self::OPTIONS)); ?></pre>
        </div>
        <?php
    }

    /**
     * @since 1.0
     */
    function admin_init() {
        register_setting(self::OPTIONS, self::OPTIONS, array(&$this, 'admin_validate_options'));

        add_settings_section(self::SECTION_1, __('Main parameters', self::TEXT_DOMAIN), array(&$this, 'admin_section_1'), self::PAGE);

        add_settings_field(self::OPTION_MINIMUM, __('Minimum order total to get free shipping', self::TEXT_DOMAIN), array(&$this, 'admin_field_minimum'), self::PAGE, self::SECTION_1);
        add_settings_field(self::OPTION_MESSAGE, __('Message to show if minimum order is not reached', self::TEXT_DOMAIN), array(&$this, 'admin_field_message'), self::PAGE, self::SECTION_1);
    }

    /**
     * @since 1.0
     */
    function admin_validate_options($input = array()) {
        $input = DeMomentSomTresTools::adminHelper_esc_attr($input);
        return $input;
    }

    /**
     * @since 1.0
     */
    function admin_section_1() {
        echo '<p>' . __('Main parameters to control messages', self::TEXT_DOMAIN) . '</p>';
    }

    /**
     * @since 1.0
     */
    function admin_field_minimum() {
        $name = self::OPTION_MINIMUM;
        $value = DeMomentSomTresTools::get_option(self::OPTIONS, $name, 0);
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name, $value, array(
//            'class' => 'regular-text'
        ));
        echo "<p style='font-size:0.8em;'>"
        . __('If order total is below this amount, the message will be shown.', self::TEXT_DOMAIN) . '</p>';
    }

    /**
     * @since 1.0
     */
    function admin_field_message() {
        $name = self::OPTION_MESSAGE;
        $value = DeMomentSomTresTools::get_option(self::OPTIONS, $name, __('Your purchase is below the minimum purchase of %s€ needed to get free shipping', self::TEXT_DOMAIN));
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name, $value, array(
            'class' => 'regular-text'
        ));
        echo "<p style='font-size:0.8em;'>"
        . __('Message should include %s in order to show the minimum amount inside it.', self::TEXT_DOMAIN) . '</p>';
    }

    /**
     * Gets the cart contents total (after calculation).
     *
     * @return string formatted price
     */
    private function get_cart_total() {
        global $woocommerce;

        if (!$woocommerce->cart->prices_include_tax) {
            // if prices don't include tax, just return the total
            $cart_contents_total = $woocommerce->cart->cart_contents_total;
        } else {
            // if prices do include tax, add the tax amount back in
            $cart_contents_total = $woocommerce->cart->cart_contents_total + $woocommerce->cart->tax_total;
        }

        return $cart_contents_total;
    }

    /**
     * Prints the message
     * @since 1.1
     * @global type $woocommerce
     */
    function message() {
        global $woocommerce;
//        $message = DeMomentSomTresTools::get_option(self::OPTIONS, self::OPTION_MESSAGE_CHECKOUT_MESSAGE);

        $minimumPurchase = DeMomentSomTresTools::get_option(self::OPTIONS, self::OPTION_MINIMUM, 0);
        $message = DeMomentSomTresTools::get_option(self::OPTIONS,self::OPTION_MESSAGE,__('Your purchase is below the minimum purchase of %s€ needed to get free shipping', self::TEXT_DOMAIN));
        $total = $this->get_cart_total();
        if ($total < $minimumPurchase):
            ?>
            <div class="woocommerce-error">
                <?php printf($message, $minimumPurchase); ?>
            </div>
            <?php
        endif;
    }

}
