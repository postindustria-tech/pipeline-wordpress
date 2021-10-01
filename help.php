<!--
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
-->

<h3>Integration With Google Analytics</h3>

<p>Below are the steps to enable google analytics custom dimensions tracking with 51Degrees Plugin.</p>

<p>1. To integrate with Google Analytics goto <code>Google Analytics</code> tab and click <code>Log in with Google Analytics Account</code> button.
</p>
<p><img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/images/screenshot-12.png"?>" alt="GoogleAnalytics1"></p>

<p>2. Follow the steps and give 51Degrees plugin the required permissions and copy the provided Google Analytics <code>Access Code</code> in the end.
</p>
<p><img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/images/screenshot-13.png"?>" alt="GoogleAnalytics2"></p>

<p>3. Enter the copied Code in <code>Access Code</code> text field and click <code>Authenticate</code>. This will connect your Google Analytics Account to 51Degrees Plugin.
</p>
<p><img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/images/screenshot-14.png"?>" alt="GoogleAnalytics3"></p>

<p>4. After authentication, select your preferred profiles for which you want to enable Custom Dimensions Tracking via <code>Google Analytics Property</code> dropdown.
</p>

<p>5. Check <code>Send Page View</code> if you want to send Default Page View hit along with Custom Dimensions. It is only recommended if you have not already integrated with any other Google Analytics plugin to avoid data duplication.
</p>

<p>6. Click <code>Save Changes</code>. This will prompt to new Custom Dimensions Screen where you can find all the Custom Dimensions available with resource key.
</p>
<p><img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/images/screenshot-15.png"?>" alt="GoogleAnalytics4"></p>

<p>7. Click on <code>Enable Google Analytics Tracking</code> to enable tracking of all the Device Data Properties as Custom Dimensions.
</p>
<p><img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/images/screenshot-16.png"?>" alt="GoogleAnalytics5"></p>


<h3>Use in templates</h3>

<p>Below are some tips for how you can use the 51Degrees Pipeline plugin in your theme or plugin development.</p>

<h4>Value replacement</h4>
<p>You can insert snippets into your pages that will be replaced with the corresponding value.</p>
<p>For example, the text <code>{Pipeline::get(&quot;device&quot;, &quot;browsername&quot;)}</code> would be replaced with <code>Chrome</code>, <code>Safari</code>, <code>Firefox</code>, etc. Depending on the browser being used by the person visiting your site.</p>
<p>In this case, we display the vendor, name and version number of the client&#39;s browser:</p>
<p><img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/images/screenshot-1.png"?>" alt="ValueReplacementExample1"></p>
<p>To set this up, we take the text from the &#39;Usage in Content&#39; column on the &#39;properties&#39; page of the plugin:</p>
<p><img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/images/screenshot-2.png"?>" alt="ValueReplacementExample2"></p>

<h4>Conditional blocks</h4>
<p>This feature allows you to show/hide content based on the property values supplied by the Pipeline API.</p>
<p>To start, add a new block and select the &#39;51Degrees conditional group block&#39;:</p>
<p><img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/images/screenshot-3.png"?>" alt="ConditionalBlockExample1"></p>
<p>Select the block to display the configuration UI on the right-hand side. In the example below, the block has been configured to only appear if the hardware vendor property is &#39;Apple&#39;:</p>
<p><img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/images/screenshot-4.png"?>" alt="ConditionalBlockExample2"></p>

<h3>Accessing properties in PHP code</h3>

<p>To get a specific property, look it up on the <a href='?page=51Degrees&tab=properties'>available properties list</a> and use the get() method specified.</p>

<pre>Pipeline::get("device", "ismobile")</pre>

<p>You can also get a list of properties by category as an array:</p>

<pre>Pipeline::getCategory("Supported Media"))</pre>

<h3>JavaScript integration</h3>

<p>The 51Degrees library exposes the same property values in JavaScript. These are accessed through the global 'fod' object</p>

<pre>
&lt;script type="text/javascript"&gt;
	window.onload = function() {
	  fod.complete(function(data){
	  // console.log(data.device.screenpixelswidth);
	  })
	}
&lt;/script&gt;
</pre>

<p>In some cases, additional evidence needs to be gathered by running JavaScript on the client. This is mostly handled automatically by the plugin and the fod object. For specific examples, see the 'Location' and 'Apple device models' sections below</p>

<h3>Location</h3>
<p>Location works slightly differently to other properties. Currently, the address is determined from the location provided by the client device. When this data is requested, a confirmation pop-up similar to the following will appear:</p>

<p><img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/images/screenshot-5.png"?>" alt="LocationExample1"></p>
<p>It is good practice to delay the appearance of this pop-up until the location is really needed. Otherwise, the user may not know why they are being asked for the information and is more likely to refuse.</p>
<p>To facilitate this, the location data needs to be explicitly requested by adding some additional JavaScript. There are many ways to do this but for an example, we have gone with the simplest approach.</p>
<p>Firstly, add a button to your page. Make sure to set a css class that we can use to identify this button and add an event to it.</p>
<p><img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/images/screenshot-6.png"?>" alt="LocationExample2"></p>
<p>Next, add an HTML element and paste the following snippet of code into it:</p><
&lt;script type="text/javascript"&gt;
window.onload = function() {
	  var elements = document.getElementsByClassName('get-user-location');

	  for(var i = 0; i &lt; elements.length; i++) {
		elements[i].addEventListener('click', function() {
		  fod.complete(function(data) { /* use values here if needed e.g. data.location.country will contain country the user is in */ }, 'location');
		});
	  }
	};
&lt;/script&gt;
<p><img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/images/screenshot-7.png"?>" alt="LocationExample3"></p>
<p>Now, when the user clicks on the &#39;Use my location&#39; button, the JavaScript that we pasted in will execute. This lets the global <code>fod</code> object know that we want access to the location data, which in turn causes the &#39;wants to know your location&#39; confirmation pop-up to be displayed.</p>
<p>Note that on the first request, the server will not have the location information so the location properties will not have values: </p>
<p><img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/images/screenshot-8.png"?>" alt="LocationExample4"></p>
<p>After the button is clicked, we need to make another request to the server for the location values to be populated:</p>
<p><img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/images/screenshot-9.png"?>" alt="LocationExample5"></p>
<p>Note that the content on the page can also be updated by using JavaScript, rather than waiting for the user to make a second request. This involves editing the JavaScript snippet above to update the page within the callback function that is passed to fod.complete.</p>


<h3>Apple device models</h3>
<p>Determining the exact model of Apple devices is more difficult that others. This is because Apple include only very limited information about the device hardware in the &#39;User-Agent&#39; HTTP header that is sent to the webserver.</p>
<p>To get around this problem, device detection uses JavaScript that runs directly on the client to gether some additional information. This can usually be used to determine the exact model of device and will at least narrow down the possibilities.</p>
<p>The WordPress plugin will handle this for you automatically. However, be aware that, due to having to get additional data from the client, the model may be less clear on the first request than on subsequent requests.</p>
<p>For example, using an iPhone 6 Plus, the <code>hardwareName</code> property contains the following array of values on the first request:</p>
<p><img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/images/screenshot-10.png"?>" alt="AppleExample1"></p>
<p>After the JavaScript runs on the client, a second request is made and the array of values has been significantly narrowed down:</p>
<p><img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/images/screenshot-11.png"?>" alt="AppleExample2"></p>
<p>The content on the page can also be updated by using JavaScript, rather than waiting for the user to make a second request. The global <code>fod</code> object can be used to pass a callback that is executed when the updated values are available. For example:</p>
<pre>
&lt;script type="text/javascript"&gt;
	window.onload = function() {
	  fod.complete(function(data) { /* access values here. e.g. data.device.hardwarename */ });
	};
&lt;/script&gt;
</pre>

