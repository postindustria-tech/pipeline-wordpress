	
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

if ((!get_option(Options::GA_TOKEN) &&
	empty(get_option(Options::GA_TOKEN))) ||
	get_option(Options::GA_ERROR)) { ?>
	<form method="post" action="options.php">
	
		<table class="form-table">
			<tbody>
				<?php
					if (get_option(Options::GA_ERROR)) {
						echo '<p></p><span class="fod-pipeline-status error">' .
							esc_html(get_option(Options::GA_ERROR)) .
							'</span>';
						delete_option(Options::GA_ERROR);
					}
				?>
				<p>
					It is required to
					<a href="https://support.google.com/analytics/answer/1008015?hl=en/" target="_blank">
						Set up
					</a>
					an account and a website profile at
					<a href="https://analytics.google.com/" target="_blank">
						Google Analytics
					</a>
					to send 51Degrees Custom Dimensions to Google Analytics.
					Once Set Up, create a connection between 51Degrees and your
					Google Analytics account.
				</p>
				<tr>
					<th scope="row" >
						<label class="pt-20">Google Authentication</label>
					</th>
					<td>
						<p class="description">
							Please ensure you allow 51Degrees access to both
							<b>Edit Google Analytics management entities</b>
							and <b>See and download your Google Analytics data</b>
							when logging into Google Analytics.</br>
						</p>
						</br>
						<a title="Log in with Google Analytics Account" id="<?php echo Options::GA_TOKEN; ?>" class="button-primary authentication_btn" href="https://accounts.google.com/o/oauth2/auth?<?php echo generate_login_url(); ?>" target="_blank">
							Log in with Google Analytics Account
						</a>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="<?php echo "fiftyonedegrees_ga_code"; ?>">Access Code</label>
					</th>
					<td>
						<input name="<?php echo "fiftyonedegrees_ga_code"; ?>" id="<?php echo "fiftyonedegrees_ga_code"; ?>" type="text" size="32"></input>
						<p class="description">
							Enter copied Google Account Access Code here.<br>
						</p>
					</td>
				</tr>
			</tbody>
		</table>

		<?php submit_button($name = 'Authenticate');?>
	</form>
<?php	
}
else if (get_option(Options::GA_CUSTOM_DIMENSIONS_SCREEN)) { 

	include plugin_dir_path(__FILE__) . "/ga-customdimensions.php";

}
else { ?>

	<form method="post" action="options.php">
		<table class="form-table">
			<tbody>
			<?php 
				if (get_option(Options::GA_TRACKING_ID_ERROR)) {
			?>
					<p></p>
					<?php echo '<span class="fod-pipeline-status error"><b>Please Select Analytics Property.</b></span>';
					}
					?>

				<tr>
					<th scope="row">
						<label class="pt-20">Google Authentication</label>
					</th>
					<td>
						<input type="submit" class="button-primary" value="Logout" name="ga_log_out" />
						<p class="description">
							You have allowed your site to access the data
							from your Google Analytics account. Click on logout
							button to disconnect or re-authenticate.
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row" >
						<label class="pt-20" for="<?php echo Options::GA_TRACKING_ID; ?>">
							Analytics Account/Property
						</label>
					</th>
					<td>
						<select id="<?php echo Options::GA_TRACKING_ID; ?>" name = "<?php echo Options::GA_TRACKING_ID; ?>">
						    <option >Select Analytics Property</option>
							<script>
							    var preSelectedTrackingId = "<?php echo esc_html(get_option(Options::GA_TRACKING_ID)); ?>";
								var propertiesList = <?php echo sprintf(esc_html('%1$s'), json_encode(get_option(Options::GA_PROPERTIES)));?>;
								for (i = 0; i<propertiesList.length; i++) {
									if (preSelectedTrackingId == propertiesList[i]["id"]) {
										document.write('<option value="' +
											propertiesList[i]["id"] +
											'" selected>' +
											propertiesList[i]["name"] +
											'</option>');
									}
									else {
                                        document.write(
											'<option value="' +
											propertiesList[i]["id"] +
											'">' +
											propertiesList[i]["name"] +
											'</option>');
									}
								}
							</script>
						</select>
						<a href=<?php echo esc_url( "?page=51Degrees&tab=google-analytics" ); ?>>
						<span class="fa-stack fa-lg" style="font-size:15px;">
								<i class="fa fa-circle fa-stack-2x" style="color:#666666;"></i>
								<i class="fa fa-refresh fa-stack-1x fa-inverse"></i>
						</span>
					    </a>			
						<p class="description">
							Select your Google Analytics Property to send 51Degrees
							Custom Dimensions to.<br>
						</p>
					</td>
				</tr>
                
				<tr>
					<th scope="row" >
						<label class="pt-20" for="<?php echo Options::GA_SEND_PAGE_VIEW; ?>">
							Send Page View
						</label>
					</th>
					<td>
					    <?php if (get_option(Options::GA_SEND_PAGE_VIEW)) { ?>
						    <input type="checkbox" id="<?php echo Options::GA_SEND_PAGE_VIEW; ?>" name="<?php echo Options::GA_SEND_PAGE_VIEW; ?>" checked>
						<?php } else { ?>
							<input type="checkbox" id="<?php echo Options::GA_SEND_PAGE_VIEW; ?>" name="<?php echo Options::GA_SEND_PAGE_VIEW; ?>"> 
						<?php } ?>
						<label for="<?php echo Options::GA_SEND_PAGE_VIEW; ?>">
							Send Page View
							<span class="fa-stack fa-lg" style="font-size:12px;">
								<i class="fa fa-circle fa-stack-2x" style="color:#666666;"></i>
								<i class="fa fa-info fa-stack-1x fa-inverse" title="Send a pageview for each page your users visit to get the information including:
1. Time spent by the user on each page or The total time a user spends on your site.
2. The geographic location.
3. Information related to browser and operating system.
4. Internal links clicked etc.">
							</i>
							</span>
						</label> 
						<p class="description">
							Check Send Page View to send default Page View hit
							with custom dimensions.<br>
						</p>
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

	return http_build_query($url);
}

?>