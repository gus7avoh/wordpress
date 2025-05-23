<?php

/**
 * The Standard Card Button Gateway
 *
 * @package WooCommerce\PayPalCommerce\WcGateway\Gateway
 */
declare (strict_types=1);
namespace WooCommerce\PayPalCommerce\WcGateway\Gateway;

use Exception;
use WooCommerce\PayPalCommerce\Vendor\Psr\Log\LoggerInterface;
use WC_Order;
use WooCommerce\PayPalCommerce\ApiClient\Exception\PayPalApiException;
use WooCommerce\PayPalCommerce\WcGateway\Helper\Environment;
use WooCommerce\PayPalCommerce\Session\SessionHandler;
use WooCommerce\PayPalCommerce\WcSubscriptions\FreeTrialHandlerTrait;
use WooCommerce\PayPalCommerce\WcSubscriptions\Helper\SubscriptionHelper;
use WooCommerce\PayPalCommerce\Vaulting\PaymentTokenRepository;
use WooCommerce\PayPalCommerce\WcGateway\Exception\GatewayGenericException;
use WooCommerce\PayPalCommerce\WcGateway\Exception\PayPalOrderMissingException;
use WooCommerce\PayPalCommerce\WcGateway\Processor\OrderProcessor;
use WooCommerce\PayPalCommerce\WcGateway\Processor\RefundProcessor;
use WooCommerce\PayPalCommerce\Vendor\Psr\Container\ContainerInterface;
use WooCommerce\PayPalCommerce\WcGateway\Settings\SettingsRenderer;
/**
 * Class CardButtonGateway
 */
class CardButtonGateway extends \WC_Payment_Gateway
{
    use \WooCommerce\PayPalCommerce\WcGateway\Gateway\ProcessPaymentTrait, FreeTrialHandlerTrait, \WooCommerce\PayPalCommerce\WcGateway\Gateway\GatewaySettingsRendererTrait;
    const ID = 'ppcp-card-button-gateway';
    /**
     * The Settings Renderer.
     *
     * @var SettingsRenderer
     */
    protected $settings_renderer;
    /**
     * The processor for orders.
     *
     * @var OrderProcessor
     */
    protected $order_processor;
    /**
     * The settings.
     *
     * @var ContainerInterface
     */
    protected $config;
    /**
     * The Session Handler.
     *
     * @var SessionHandler
     */
    protected $session_handler;
    /**
     * The Refund Processor.
     *
     * @var RefundProcessor
     */
    private $refund_processor;
    /**
     * Service able to provide transaction url for an order.
     *
     * @var TransactionUrlProvider
     */
    protected $transaction_url_provider;
    /**
     * The subscription helper.
     *
     * @var SubscriptionHelper
     */
    protected $subscription_helper;
    /**
     * The payment token repository.
     *
     * @var PaymentTokenRepository
     */
    protected $payment_token_repository;
    /**
     * Whether the plugin is in onboarded state.
     *
     * @var bool
     */
    private $onboarded;
    /**
     * Whether the gateway should be enabled by default.
     *
     * @var bool
     */
    private $default_enabled;
    /**
     * The environment.
     *
     * @var Environment
     */
    protected $environment;
    /**
     * The logger.
     *
     * @var LoggerInterface
     */
    private $logger;
    /**
     * The function return the PayPal checkout URL for the given order ID.
     *
     * @var callable(string):string
     */
    private $paypal_checkout_url_factory;
    /**
     * CardButtonGateway constructor.
     *
     * @param SettingsRenderer        $settings_renderer The Settings Renderer.
     * @param OrderProcessor          $order_processor The Order Processor.
     * @param ContainerInterface      $config The settings.
     * @param SessionHandler          $session_handler The Session Handler.
     * @param RefundProcessor         $refund_processor The Refund Processor.
     * @param bool                    $is_connected Whether onboarding was completed.
     * @param TransactionUrlProvider  $transaction_url_provider Service providing transaction view URL based on order.
     * @param SubscriptionHelper      $subscription_helper The subscription helper.
     * @param bool                    $default_enabled Whether the gateway should be enabled by default.
     * @param Environment             $environment The environment.
     * @param PaymentTokenRepository  $payment_token_repository The payment token repository.
     * @param LoggerInterface         $logger  The logger.
     * @param callable(string):string $paypal_checkout_url_factory The function return the PayPal checkout URL for the given order ID.
     * @param string                  $place_order_button_text The text for the standard "Place order" button.
     */
    public function __construct(SettingsRenderer $settings_renderer, OrderProcessor $order_processor, ContainerInterface $config, SessionHandler $session_handler, RefundProcessor $refund_processor, bool $is_connected, \WooCommerce\PayPalCommerce\WcGateway\Gateway\TransactionUrlProvider $transaction_url_provider, SubscriptionHelper $subscription_helper, bool $default_enabled, Environment $environment, PaymentTokenRepository $payment_token_repository, LoggerInterface $logger, callable $paypal_checkout_url_factory, string $place_order_button_text)
    {
        $this->id = self::ID;
        $this->settings_renderer = $settings_renderer;
        $this->order_processor = $order_processor;
        $this->config = $config;
        $this->session_handler = $session_handler;
        $this->refund_processor = $refund_processor;
        $this->transaction_url_provider = $transaction_url_provider;
        $this->subscription_helper = $subscription_helper;
        $this->default_enabled = $default_enabled;
        $this->environment = $environment;
        $this->onboarded = $is_connected;
        $this->payment_token_repository = $payment_token_repository;
        $this->logger = $logger;
        $this->paypal_checkout_url_factory = $paypal_checkout_url_factory;
        $this->order_button_text = $place_order_button_text;
        $default_support = array('products', 'refunds');
        $this->supports = array_merge($default_support, apply_filters('woocommerce_paypal_payments_card_button_gateway_supports', array()));
        $this->method_title = __('Standard Card Button', 'woocommerce-paypal-payments');
        $this->method_description = __('The separate payment gateway with the card button. If disabled, the button is included in the PayPal gateway.', 'woocommerce-paypal-payments');
        $this->title = $this->get_option('title', __('Debit & Credit Cards', 'woocommerce-paypal-payments'));
        $this->description = $this->get_option('description', '');
        $this->init_form_fields();
        $this->init_settings();
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }
    /**
     * Whether the Gateway needs to be setup.
     *
     * @return bool
     */
    public function needs_setup(): bool
    {
        return !$this->onboarded;
    }
    /**
     * Initializes the form fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = array('enabled' => array('title' => __('Enable/Disable', 'woocommerce-paypal-payments'), 'type' => 'checkbox', 'label' => __('Enable Standard Card Button gateway', 'woocommerce-paypal-payments'), 'default' => $this->default_enabled ? 'yes' : 'no', 'desc_tip' => \true, 'description' => __('Enable/Disable the separate payment gateway with the card button.', 'woocommerce-paypal-payments')), 'title' => array('title' => __('Title', 'woocommerce-paypal-payments'), 'type' => 'text', 'default' => $this->title, 'desc_tip' => \true, 'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-paypal-payments')), 'description' => array('title' => __('Description', 'woocommerce-paypal-payments'), 'type' => 'text', 'default' => $this->description, 'desc_tip' => \true, 'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-paypal-payments')), 'ppcp' => array('type' => 'ppcp'));
    }
    /**
     * Process payment for a WooCommerce order.
     *
     * @param int $order_id The WooCommerce order id.
     *
     * @return array
     */
    public function process_payment($order_id)
    {
        $wc_order = wc_get_order($order_id);
        if (!is_a($wc_order, WC_Order::class)) {
            return $this->handle_payment_failure(null, new GatewayGenericException(new Exception('WC order was not found.')));
        }
        /**
         * If customer has chosen change Subscription payment.
         */
        if ($this->subscription_helper->has_subscription($order_id) && $this->subscription_helper->is_subscription_change_payment()) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $saved_paypal_payment = wc_clean(wp_unslash($_POST['saved_paypal_payment'] ?? ''));
            if ($saved_paypal_payment) {
                $wc_order->update_meta_data('payment_token_id', $saved_paypal_payment);
                $wc_order->save();
                return $this->handle_payment_success($wc_order);
            }
        }
        /**
         * If the WC_Order is paid through the approved webhook.
         */
        //phpcs:disable WordPress.Security.NonceVerification.Recommended
        if (isset($_REQUEST['ppcp-resume-order']) && $wc_order->has_status('processing')) {
            return $this->handle_payment_success($wc_order);
        }
        //phpcs:enable WordPress.Security.NonceVerification.Recommended
        try {
            try {
                $this->order_processor->process($wc_order);
                do_action('woocommerce_paypal_payments_before_handle_payment_success', $wc_order);
                return $this->handle_payment_success($wc_order);
            } catch (PayPalOrderMissingException $exc) {
                $order = $this->order_processor->create_order($wc_order);
                return array('result' => 'success', 'redirect' => ($this->paypal_checkout_url_factory)($order->id()));
            }
        } catch (PayPalApiException $error) {
            return $this->handle_payment_failure($wc_order, new Exception(\WooCommerce\PayPalCommerce\WcGateway\Gateway\Messages::generic_payment_error_message() . ' ' . $error->getMessage(), $error->getCode(), $error));
        } catch (Exception $error) {
            return $this->handle_payment_failure($wc_order, $error);
        }
    }
    /**
     * Process refund.
     *
     * If the gateway declares 'refunds' support, this will allow it to refund.
     * a passed in amount.
     *
     * @param  int    $order_id Order ID.
     * @param  float  $amount Refund amount.
     * @param  string $reason Refund reason.
     * @return boolean True or false based on success, or a WP_Error object.
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);
        if (!is_a($order, \WC_Order::class)) {
            return \false;
        }
        return $this->refund_processor->process($order, (float) $amount, (string) $reason);
    }
    /**
     * Return transaction url for this gateway and given order.
     *
     * @param \WC_Order $order WC order to get transaction url by.
     *
     * @return string
     */
    public function get_transaction_url($order): string
    {
        $this->view_transaction_url = $this->transaction_url_provider->get_transaction_url_base($order);
        return parent::get_transaction_url($order);
    }
    /**
     * Returns the settings renderer.
     *
     * @return SettingsRenderer
     */
    protected function settings_renderer(): SettingsRenderer
    {
        return $this->settings_renderer;
    }
    /**
     * Determines if the Gateway is available for use.
     *
     * @return bool
     */
    public function is_available(): bool
    {
        $is_available = parent::is_available();
        if ($is_available && $this->is_free_trial_cart()) {
            $is_available = \false;
        }
        return $is_available;
    }
}
