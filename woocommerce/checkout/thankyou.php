<?php
// define("MERCHAND_ID","PHARGILL");
// define("QR_URL","https://pos.digica.sh/qrcode/generator");
// define("DIGICASH_CALLBACK", site_url( "/wp-content/plugins/digicash/digicash.php" )	);
/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.7.0
 */
$detect = new Mobile_Detect;


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-order">
<?php 
?>
	<?php
	if ( $order ) :
		if($order->payment_method=="digicash"){
			if(!$detect->isMobile()){
				$url = QR_URL . "?merchantId=" . MERCHAND_ID . "&amount=" . $order->get_total() * 100 . "&transactionReference=" . $order->get_id();
				echo "<img src='" . $url . "' style='margin-bottom: 50px;margin-left: auto; margin-right: auto;'/>";
			}
			$payment_applications = json_decode(file_get_contents(DIGICASH_APPLICATIONS));

			if(property_exists($payment_applications,"payLoad")&&property_exists($payment_applications->payLoad,"schemeList")){
				$payment_gateway = WC()->payment_gateways->payment_gateways();
				$test = isset($payment_gateway["digicash"])?$payment_gateway["digicash"]->settings["testmode"]:"no";

				if(is_array($payment_applications->payLoad->schemeList) && count($payment_applications->payLoad->schemeList)){
					echo "<div style='margin-bottom: 50px; display: flex; flex-wrap: wrap; justify-content: center;'>";
					$url_apps = "merchantId=" . MERCHAND_ID . "&amount=" . $order->get_total() * 100 . "&transactionReference=" . $order->get_id() . "&callback=" . DIGICASH_CALLBACK;
					
					foreach($payment_applications->payLoad->schemeList as $app){
						if($test=="yes"&&$app->testapp=="false"){
							continue;
						}
						if($test=="no"&&$app->testapp=="true"){
							continue;
						}
						echo "<a href='" . $app->scheme . "://doPay?" . $url_apps . "' style='margin-right: 10px; display: flex; align-items: center; flex-direction: column;'><img src='" . $app->logoUrl . "' alt='PaymentLogo' style='width:50px; height: auto; margin-bottom:10px;'/>" . $app->appName . "</a>";
					}
					echo "<div style='clear: both;'></div></div>";
				}

			}
			

		}

		do_action( 'woocommerce_before_thankyou', $order->get_id() );
		?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed"><?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?></p>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php esc_html_e( 'Pay', 'woocommerce' ); ?></a>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay"><?php esc_html_e( 'My account', 'woocommerce' ); ?></a>
				<?php endif; ?>
			</p>

		<?php else : ?>

			<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ), $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>

			<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">

				<li class="woocommerce-order-overview__order order">
					<?php esc_html_e( 'Order number:', 'woocommerce' ); ?>
					<strong><?php echo $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<li class="woocommerce-order-overview__date date">
					<?php esc_html_e( 'Date:', 'woocommerce' ); ?>
					<strong><?php echo wc_format_datetime( $order->get_date_created() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
					<li class="woocommerce-order-overview__email email">
						<?php esc_html_e( 'Email:', 'woocommerce' ); ?>
						<strong><?php echo $order->get_billing_email(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
					</li>
				<?php endif; ?>

				<li class="woocommerce-order-overview__total total">
					<?php esc_html_e( 'Total:', 'woocommerce' ); ?>
					<strong><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<?php if ( $order->get_payment_method_title() ) : ?>
					<li class="woocommerce-order-overview__payment-method method">
						<?php esc_html_e( 'Payment method:', 'woocommerce' ); ?>
						<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
					</li>
				<?php endif; ?>

			</ul>

		<?php endif; ?>

		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>

	<?php else : ?>

		<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ), null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>

	<?php endif; ?>

</div>
