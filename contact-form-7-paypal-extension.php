<?php
/**
 * Plugin Name: Contact Form 7 - PayPal Extension
 * Plugin URL: https://wordpress.org/plugins/contact-form-7-paypal-extension/
 * Description:  This plugin will integrate PayPal submit button which redirects you to PayPal website for making your payments after submitting the form.
 * Version: 1.5
 * Author: ZealousWeb Technologies
 * Author URI: http://zealousweb.com
 * Developer: The Zealousweb Team
 * Developer E-Mail: info@opensource.zealousweb.com
 * Text Domain: contact-form-7-extension
 * Domain Path: /languages
 * 
 * Copyright: © 2009-2015 ZealousWeb Technologies.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Register the [paypalsubmit] shortcode
 *
 * This shortcode will integrate PayPal button with your contact form.
 * It will allow you to generate tag with parameters like 
 * PayPal business email, item amount field, item name field, currency, PayPal mode, return page URL
 *
 * @access      public
 * @since       1.0 
 * @return      $content
*/
if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}
/**
 * Check if Contact Form 7 is active
 **/
require_once (dirname(__FILE__) . '/contact-form-7-paypal-extension.php');

register_activation_hook (__FILE__, 'paypal_submit_activation_check');
function paypal_submit_activation_check()
{
    if ( !in_array( 'contact-form-7/wp-contact-form-7.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        wp_die( __( '<b>Warning</b> : Install/Activate Contact Form 7 to activate "Contact Form 7 - PayPal Extension" plugin', 'contact-form-7' ) );
    }
}

/**
** A base module for [paypalsubmit] - A submit button that will redirect to PayPal after form submit.
**/

/* Shortcode handler */

add_action('init', 'contact_form_7_paypal_submit', 11);

function contact_form_7_paypal_submit() {	
	if(function_exists('wpcf7_add_shortcode')) {
		wpcf7_add_shortcode( 'paypalsubmit', 'wpcf7_paypal_submit_shortcode_handler', false );		
	} else {
		 return; 		
	}
}

/**
  * Generate paypal redirection URL using parameters entered in tag 
  */

add_action('wp_head','wpcf7_paypal_location');
function wpcf7_paypal_location(){	?>
	<script>	
		var paypal_location = "";			
		function returnURL(location, itemamount, itemname, itemqty)
		{			
			var amount = 0;
			if(itemamount != "" && itemamount != undefined){
				var type = jQuery('#'+itemamount).attr('type');						
				if(type == 'text' || type == 'number' || type == 'range'){				
		        	amount = jQuery('#'+itemamount).val();		
		        	alert(amount);
		        } else {		            	   	
		       		amount = jQuery('#'+itemamount+' :checked').val();		
		        }	
		    }
		    else
		    {
		    	amount = 0;
		    }
	        /*------------------------------------------------------*/
	        var quantity = 0;	
	        if(itemqty != "" && itemqty != undefined){
				var type = jQuery('#'+itemqty).attr('type');				
				if(type == 'text' || type == 'number' || type == 'range'){
		        	quantity = jQuery('#'+itemqty).val();		
		        } else {	       	   	
		       		quantity = jQuery('#'+itemqty+' :checked').val();		
		        } 	  
		    } else {
				quantity = '1';
		    }   			
			/*------------------------------------------------------*/
			var item = '';
			if(itemname != "" && itemname != undefined )
			{
				var type = jQuery('#'+itemname).attr('type');			
				if(type == 'text' || type == 'number' || type == 'range'){				
		        	item = jQuery('#'+itemname).val();		
		        } else {	  
		       		item = jQuery('#'+itemname+' :checked').val();		
		        }
		    } else {
		    	item = "";
		    }
	    	if(amount != "" && amount != undefined) {					
				paypal_location = location + '&amount=' + amount + '&item_name=' + item + '&quantity=' + quantity;													
			} 
		 }	
		jQuery(document).ready(function(){
			jQuery(document).on('mailsent.wpcf7', function () {					
				if(paypal_location != ""){
			    	window.location = paypal_location;
			    }
			});
		});			
	</script>
<?php
}

/**
  * Regenerate shortcode into PayPal submit button
  */

function wpcf7_paypal_submit_shortcode_handler( $tag ) {	
	$tag = new WPCF7_Shortcode( $tag );	
	$class = wpcf7_form_controls_class( $tag->type );	
	$atts = array();	
	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );
	$businessemail = $tag->get_option('email');
	$currencycode = $tag->get_option('currency');
	$successURL = $tag->get_option('return_url');
	$cancelURL = $tag->get_option('cancel_url');
    $returnURL = $tag->get_option('return_url');
    $itemqty = $tag->get_option('quantity');
    $itemamount = $tag->get_option('itemamount');
    $itemname = $tag->get_option('itemname');
	if(!empty($businessemail[0]))
	{
		$querystring = array(
						'business'=> $businessemail[0],
						'currency_code'=> (empty($currencycode[0])) ? 'USD' : $currencycode[0],						
						'return'=> (empty($successURL[0])) ? get_site_url() : $successURL[0],
						'cancel_return'=> (empty($cancelURL[0])) ? get_site_url() : $cancelURL[0],
						'notify_url'=> $returnURL[0]
					);

		$mode = $tag->has_option( 'sandbox' );
		$mode = (isset($mode) && !empty($mode)) ? 'sandbox.paypal' : 'paypal';

		$location = "https://www.".$mode.".com/us/cgi-bin/webscr?cmd=_xclick&".http_build_query($querystring);	
		$atts['onclick'] = 'returnURL("'.$location.'","'.$itemamount[0].'","'.$itemname[0].'","'.$itemqty[0].'");';
	}
	$value = isset( $tag->values[0] ) ? $tag->values[0] : '';

	if ( empty( $value ) )
		$value = __( 'Submit', 'contact-form-7' );

	$atts['type'] = 'submit';
	$atts['value'] = $value;

	$atts = wpcf7_format_atts( $atts );

	$html .= sprintf( '<input %1$s />', $atts );

	return $html;
}

/************************************~: Admin Section of paypal submit button :~************************************/

/* Tag generator */

add_action( 'admin_init', 'wpcf7_add_tag_generator_paypal_submit', 55 );

function wpcf7_add_tag_generator_paypal_submit() {	
	if ( ! function_exists( 'wpcf7_add_tag_generator' ) )
		return;

	wpcf7_add_tag_generator( 'paypal-submit', __( 'PayPal Submit button', 'contact-form-7' ),
		'wpcf7-tg-pane-paypal-submit', 'wpcf7_tg_pane_paypal_submit', array( 'nameless' => 1 ) );
}

/** Parameters field for generating tag at backend **/

function wpcf7_tg_pane_paypal_submit( $contact_form ) {
	$currency = array('AUD'=>'Australian Dollar','BRL'=>'Brazilian Real','CAD'=>'Canadian Dollar','CZK'=>'Czech Koruna','DKK'=>'Danish Krone','EUR'=>'Euro','HKD'=>'Hong Kong Dollar','HUF'=>'Hungarian Forint','ILS'=>'Israeli New Sheqel','JPY'=>'Japanese Yen','MYR'=>'Malaysian Ringgit','MXN'=>'Mexican Peso','NOK'=>'Norwegian Krone','NZD'=>'New Zealand Dollar','PHP'=>'Philippine Peso','PLN'=>'Polish Zloty','GBP'=>'Pound Sterling','RUB'=>'Russian Ruble','SGD'=>'Singapore Dollar', 'SEK'=>'Swedish Krona','CHF'=>'Swiss Franc','TWD'=>'Taiwan New Dollar','THB'=>'Thai Baht','TRY'=>'Turkish Lira','USD'=>'U.S. Dollar');
?>
<div id="wpcf7-tg-pane-paypal-submit" class="hidden">
<form action="">
<table>
<tr>
<td colspan="2"><b>NOTE: If required fields are missing, PayPal Submit button works as simple Submit button.</b></td>
</tr>
<tr>
<td><code>id</code> <?php echo '<font style="font-size:10px"> (optional)</font>';?><br />
<input type="text" name="id" class="idvalue oneline option" /></td>

<td><code>class</code> <?php echo '<font style="font-size:10px"> (required)</font>'; ?><br />
<input type="text" name="class" class="classvalue oneline option" /></td>
</tr>

<tr>
<td><?php echo esc_html( __( 'Label', 'contact-form-7' ) ); echo '<font style="font-size:10px"> (optional)</font>'; ?><br />
<input type="text" name="values" class="oneline" /></td>
<td><?php echo esc_html( __( 'PayPal Business E-Mail', 'contact-form-7' ) );echo '<font style="font-size:10px"> (required)</font>';?><br />
<input type="text" name="email" class="oneline option" /></td>
</tr>
<tr>
<td><?php echo esc_html( __( 'Select Currency', 'contact-form-7' ) ); echo ' (Default "USD")';?><br />
	<select name="currencies" onchange="document.getElementById('currency').value = this.value;">
		<?php foreach($currency as $key=>$value) { ?>
			<option value="<?php echo $key;?>" <?php echo ($key == "USD")?'selected':'';?>><?php echo $value;?></option>
		<?php } ?>
	</select>
	<input type="hidden" value="" name="currency" id="currency" class="oneline option">
</td>
<td><br><input type="checkbox" name="sandbox" class="option">Use PayPal Sandbox</td>
</tr>
<tr>	
	<td colspan="2"><hr>Enter Contact Form 7 Field's ID for these 3 PayPal fields,</td>
</tr>
<tr>
	<td colspan="2">
	<table><tr>
		<td style="width:33%;padding:0px 3px;"><?php echo esc_html( __( 'Itemamount', 'contact-form-7' ) ); echo '<font style="font-size:10px"> (required)</font>'; ?><br />
			<input type="text" name="itemamount" class="oneline option"/></td>
		<td style="width:33%;padding:0px 3px;"><?php echo esc_html( __( 'Itemname', 'contact-form-7' ) ); echo '<font style="font-size:10px"> (optional)</font>';?><br />
			<input type="text" name="itemname" class="oneline option" /></td>
		<td style="width:33%;padding:0px 3px;"><?php echo esc_html( __( 'Quantity', 'contact-form-7' ) ); echo '<font style="font-size:10px"> (optional)</font>'; ?><br />
			<input type="text" name="quantity" class="oneline option" /></td>
	</tr></table><hr>
</td>
</tr>
<tr>
<td colspan="2"><?php echo esc_html( __( 'Success Return URL', 'contact-form-7' ) ); echo '<font style="font-size:10px"> (optional)</font>';?><br />
	<input type="text" name="return_url" class="oneline option" /></td>
</tr>
<tr>
<td colspan="2"><?php echo esc_html( __( 'Cancel Return URL', 'contact-form-7' ) ); echo '<font style="font-size:10px"> (optional)</font>';?><br />
	<input type="text" name="cancel_url" class="oneline option" /></td>
</tr>
</table>

<div class="tg-tag"><?php echo esc_html( __( "Copy this code and paste it into the form left.", 'contact-form-7' ) ); ?><br /><input type="text" name="paypalsubmit" class="tag wp-ui-text-highlight code" readonly="readonly" onfocus="this.select()" /></div>
</form>
</div>
<?php
}

?>