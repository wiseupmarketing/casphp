<?php
/*
Plugin Name: PMPro Customizations
Plugin URI: https://www.paidmembershipspro.com/wp/pmpro-customizations/
Description: Customizations for my Paid Memberships Pro Setup
Version: .1
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com
*/
 
//Now start placing your customization code below this line


/*  
  Tax solution for Australia
  
  This solution assume the GST tax rate of 10% from March 15th, 2017. Ask your accountant how much tax you must charge.
  More info: https://www.business.gov.au/info/run/tax/register-for-goods-and-services-tax-gst
  
  Edit as needed, then save this file in your plugins folder and activate it through the plugins page in the WP dashboard.
*/ 
//add tax info to cost text. this is enabled if the danish checkbox is checked.
function agst_pmpro_tax($tax, $values, $order)
{  	
	$tax = round((float)$values[price] * 0.1, 2);
	return $tax;
}
 
function agst_pmpro_level_cost_text($cost, $level)
{
	//only applicable for levels > 1
	$cost .= __(" Customers in Australia will be charged a 10% tax.", 'pmpro-australia-gst');
	
	return $cost;
}
add_filter("pmpro_level_cost_text", "agst_pmpro_level_cost_text", 10, 2);
 
//set the default country to Australia
function agst_pmpro_default_country($country) {
	return 'AU';
}
add_filter('pmpro_default_country', 'agst_pmpro_default_country');
 
//add AU checkbox to the checkout page
function agst_pmpro_checkout_boxes()
{
?>
<table id="pmpro_pricing_fields" class="pmpro_checkout" width="100%" cellpadding="0" cellspacing="0" border="0">
<thead>
	<tr>
		<th>
			<?php _e('Australian Residents', 'pmpro-australia-gst');?>
		</th>						
	</tr>
</thead>
<tbody>                
	<tr>	
		<td>
			<div>				
				<input id="taxregion" name="taxregion" type="checkbox" value="1" <?php if(!empty($_REQUEST['taxregion']) || !empty($_SESSION['taxregion'])) {?>checked="checked"<?php } ?> /> <label for="taxregion" class="pmpro_normal pmpro_clickable"><?php _e('Check this box if your billing address is in Australia.', 'pmpro-australia-gst');?></label>
			</div>				
		</td>
	</tr>
</tbody>
</table>
<?php
}
add_action("pmpro_checkout_boxes", "agst_pmpro_checkout_boxes");
 
//update tax calculation if buyer is danish
function agst_region_tax_check()
{
	//check request and session
	if(isset($_REQUEST['taxregion']))
	{
		//update the session var
		$_SESSION['taxregion'] = $_REQUEST['taxregion'];	
		
		//not empty? setup the tax function
		if(!empty($_REQUEST['taxregion']))
			add_filter("pmpro_tax", "agst_pmpro_tax", 10, 3);
	}
	elseif(!empty($_SESSION['taxregion']))
	{
		//add the filter
		add_filter("pmpro_tax", "agst_pmpro_tax", 10, 3);
	}
	else
	{
		//check state and country
		if(!empty($_REQUEST['bcountry']))
		{			
			$bcountry = trim(strtolower($_REQUEST['bcountry']));
			if($bcountry == "au")
			{
				//billing address is in AU
				add_filter("pmpro_tax", "agst_pmpro_tax", 10, 3);
			}
		}
	}
}
add_action("init", "agst_region_tax_check");
 
//remove the taxregion session var on checkout
function agst_pmpro_after_checkout()
{
	if(isset($_SESSION['taxregion']))
		unset($_SESSION['taxregion']);
}
add_action("pmpro_after_checkout", "agst_pmpro_after_checkout");


/**
 * Only sell to specific countries by adding in the 'allowed' countries' in 
 * the $restricted_countries array.
 * 
 * You can add this recipe to your site by creating a custom plugin
 * or using the Code Snippets plugin available for free in the WordPress repository.
 * Read this companion article for step-by-step directions on either method.
 * https://www.paidmembershipspro.com/create-a-plugin-for-pmpro-customizations/
 */
function my_init()
{
	global $restricted_countries;
	
	//Only allow for the countries in the $restricted_countries array
	$restricted_countries = array(
							1 => array('AU'),
							2 => array('AU'),
	);
}

add_action('init', 'my_init');

function my_pmpro_registration_checks($value)
{
	global $restricted_countries, $pmpro_msg, $pmpro_msgt;
	
	$country = $_REQUEST['bcountry'];
	$level_id = $_REQUEST['level'];
	
	//only check if the level has restrictions
	if(array_key_exists($level_id, $restricted_countries) != in_array($country, $restricted_countries[$level_id]))
	{
		$pmpro_msg = "You must be a resident of Australia to register.";
		$pmpro_msgt = "pmpro_error";
			
		$value = false;
	}
	
	return $value;
}
add_filter("pmpro_registration_checks", "my_pmpro_registration_checks");

function my_pmpro_level_expiration_text($text, $level)
{
	global $restricted_countries, $pmpro_countries;
	
	if(array_key_exists($level->id, $restricted_countries ))
	{
		$text = $text." Only people from Australia can sign up here! ";
		
		//code for commas
		$i = 1;
		foreach($restricted_countries[$level->id] as $country)
		{
			$text = $text. $pmpro_countries[$country];
			
			if($i != count($restricted_countries[$level->id]))
				   $text = $text. ", ";
		
			$i++;
		}
		
	}
	
	return $text;
}
add_filter("pmpro_level_expiration_text", "my_pmpro_level_expiration_text", 10, 2);

/**
 * Use the Paid Memberships Pro countries list in a custom field on checkout.
 * This requires the Register Helper Add On installed and activated - https://www.paidmembershipspro.com/add-ons/pmpro-register-helper-add-checkout-and-profile-fields/
 * Add this code to your PMPro Customizations Plugin - https://www.paidmembershipspro.com/create-a-plugin-for-pmpro-customizations/
 */
 
 function my_pmprorh_init()
{
//don't break if Register Helper is not loaded
	if(!function_exists( 'pmprorh_add_registration_field' )) {
		return false;
	}
global $pmpro_countries;
$countries = array_merge(array("" => "Choose One"), $pmpro_countries);
	//define the fields
    $fields = array();
    $fields[] = new PMProRH_Field(
    	"bcountry",
    	"select",
    	array(
		"label"=>"Country",
		"required" => true,
		"profile" => true,		// show in user profile
		"options"=> $countries
		));

	//add the fields into a new checkout_boxes are of the checkout page
	foreach($fields as $field)
		pmprorh_add_registration_field(
			'checkout_boxes',		// location on checkout page
			$field		// PMProRH_Field object
		);
	//that's it. see the PMPro Register Helper readme for more information and examples.
}
add_action( 'init', 'my_pmprorh_init' );
/**
 * Show custom text before submit button on Paid Memberships Pro checkout page.
 * Follow this guide - https://www.paidmembershipspro.com/create-a-plugin-for-pmpro-customizations/
 */
function show_my_text_on_checkout() {
 echo '<p>All credit card details are processed and stored securely via Stripe.</p>'; 
}
add_action( 'pmpro_checkout_before_submit_button', 'show_my_text_on_checkout' );