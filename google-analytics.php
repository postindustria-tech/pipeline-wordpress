	
<?php
/*
    This Original Work is copyright of 51 Degrees Mobile Experts Limited.
    Copyright 2019 51 Degrees Mobile Experts Limited, 5 Charlotte Close,
    Caversham, Reading, Berkshire, United Kingdom RG4 7BY.

    This Original Work is licensed under the European Union Public Licence (EUPL) 
    v.1.2 and is subject to its terms as set out below.

    If a copy of the EUPL was not distributed with this file, You can obtain
    one at https://opensource.org/licenses/EUPL-1.2.

    The 'Compatible Licences' set out in the Appendix to the EUPL (as may be
    amended by the European Commission) shall be deemed incompatible for
    the purposes of the Work and the provisions of the compatibility
    clause in Article 5 of the EUPL shall not apply.
*/

if ( !get_option( 'fiftyonedegrees_ga_access_token' ) && empty(get_option( 'fiftyonedegrees_ga_access_token' ))) { ?>
	<form method="post" action="options.php">
	
	<table class="form-table">
		<tbody>
        <?php
			if ( get_option( "fiftyonedegrees_ga_error" )) {
				echo '<p></p><span class="fod-pipeline-status error">' . get_option( "fiftyonedegrees_ga_error" ) . '</span>';      
				delete_option( "fiftyonedegrees_ga_error" );
			}
		?>
		<p>Set up a liaison between 51Degrees and your Google Analytics account.</p>
			<tr>
				<th scope="row" ><label class="pt-20">Google Authentication</label></th>
				<td>
					<a title="Log in with Google Analytics Account" id="fiftyonedegrees_ga_token" class="button-primary authentication_btn" href="https://accounts.google.com/o/oauth2/auth?<?php echo generate_login_url(); ?>" target="_blank">Log in with Google Analytics Account</a>
					<p class="description">It is required to <a href="https://support.google.com/analytics/answer/1008015?hl=en/">Set up</a> your account and a website profile at <a href="https://analytics.google.com/">Google Analytics</a> to send 51Degrees Custom Dimensions to Google Analytics.<br></p>
				</td>
			</tr>
			<tr>
				<th scope="row" ><label for="fiftyonedegrees_ga_code">Access Code</label></th>
				<td>
					<input name="fiftyonedegrees_ga_code" id="fiftyonedegrees_ga_code" type="text" size="32"></input>
					<p class="description">Enter copied Google Account Access Code here.<br></p>
				</td>
			</tr>
		</tbody>
	</table>

	<?php submit_button( $name = 'Authenticate' );?>
	</form>
<?php	
} else if ( get_option("custom_dimension_screen") ) { 

	include plugin_dir_path(__FILE__) . "/ga-customdimensions.php";

} else if ( get_option("fiftyonedegrees_ga_change_screen") ) { 

	include plugin_dir_path(__FILE__) . "/ga-authentication.php";

} else { ?>

	<form method="post" action="options.php">

		<table class="form-table">
	
			<tbody>

			<?php 

				if ( get_option( 'tracking_id_error' ) ) {
					
					?>
					<p></p>
					<?php echo '<span class="fod-pipeline-status error">Please Select Analytics Property.</span>';
				}
				?>

				<tr>
					<th scope="row" ><label class="pt-20">Google Authentication</label></th>
					<td>
						<input type="submit" class="button-primary" value="Logout" name="ga_log_out" />
						<p class="description">You have allowed your site to access the data from your Google Analytics account. Click on logout button to disconnect or re-authenticate.</p>
					</td>
				</tr>
				<tr>
				<th scope="row" ><label class="pt-20" for="fiftyonedegrees_ga_tracking_id">Analytics Account/Property</label></th>
					<td>
						<select id="fiftyonedegrees_ga_tracking_id" name = "fiftyonedegrees_ga_tracking_id">
						    <option >Select Analytics Property</option>
							<script>
							    var preSelectedTrackingId = "<?php echo get_option("fiftyonedegrees_ga_tracking_id"); ?>";
								var propertiesList = <?php echo json_encode(get_option( 'fiftyonedegrees_ga_properties_list' ));?>;
								for(i=0; i<propertiesList.length; i++) {
									if(preSelectedTrackingId == propertiesList[i]["id"]) {
										document.write('<option value="' + propertiesList[i]["id"] +'" selected>' + propertiesList[i]["name"] + '</option>');
									}
									else {
                                        document.write('<option value="' + propertiesList[i]["id"] +'">' + propertiesList[i]["name"] + '</option>');
									}									
								}
							</script>
						</select>				
					<p class="description">Select your Google Analytics Property to send 51Degrees Custom Dimensions to.<br></p>
					</td>
				</tr>
                
				<tr>
				<th scope="row" ><label class="pt-20" for="fiftyonedegrees_ga_send_page_view">Send Page View</label></th>
					<td>
					    <?php if( get_option("fiftyonedegrees_ga_send_page_view")) { ?>
						    <input type="checkbox" id="fiftyonedegrees_ga_send_page_view" name="fiftyonedegrees_ga_send_page_view" checked>
						<?php } else { ?>
							<input type="checkbox" id="fiftyonedegrees_ga_send_page_view" name="fiftyonedegrees_ga_send_page_view"> 
						<?php } ?>
						<label for="fiftyonedegrees_ga_send_page_view">Send Page View</label>
						<p class="description">Check Send Page View to send default Page View hit with custom dimensions.<br></p>											
					</td>
				</tr>				
			</tbody>
		</table>

		<?php submit_button(); ?>
	</form>

<?php } 

function generate_login_url() {

	$url = array(
	 'scope'           => FIFTYONEDEGREES_SCOPE,
	 'response_type'   => FIFTYONEDEGREES_RESPONSE_TYPE,
	 'access_type'     => FIFTYONEDEGREES_ACCESS_TYPE,
	 'approval_prompt' => FIFTYONEDEGREES_PROMPT,
	 'redirect_uri'    => FIFTYONEDEGREES_REDIRECT,
	 'client_id'       => FIFTYONEDEGREES_CLIENT_ID
	);

	return http_build_query( $url );
}

?>