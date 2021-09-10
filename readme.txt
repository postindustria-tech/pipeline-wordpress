=== 51Degrees: Pipeline API & Google Analytics Plugin ===

Contributors: 51Degrees
Donate link: https://51degrees.com/
Tags: google analytics, custom dimensions, properties, analytics, tracking, 51Degrees, pipeline, device, detection, user agent
Requires at least: 3.6
Tested up to: 5.8
Requires PHP: 5.6
Stable tag: trunk
License: EUPL
 
The best plugin for WordPress to send Device properties as Custom Dimensions to Google Analytics to get richer insights of device specifications and capabilities.

== Description ==

Integrating 51Degrees Device Detection with your website will allow you to make informed decisions about what content a user engages with and how it is displayed. Combining the information learned from your analytics data with real-time enhanced device data on your website will empower you to produce a page built for that specific device’s needs. Taking this one step further, you have an additional 224 device properties available to enhance your user's user experience. The possibilities are endless as to what you can do with the information - it’s remarkably powerful.

This plugin makes use of the 51Degrees Pipeline API to deliver various data intelligence [services](https://51degrees.com/services). You can also add custom dimensions to your Google Analytics solution which will enhance your analytical data. With 51Degrees you can capture and pipe various properties into GA such as screen orientation and screen size. 

== Features ==

## Integration With Google Analytics

51Degrees plugin allows you to add the Device Data Properties as Custom Dimensions to Google Analytics in a seamless and useful manner. The integration is super simple and does not require the help of a developer to set up the integration. Once you integrate Google Analytics in WordPress using 51Degrees, you will be able to fetch the Custom Dimensions in the Google Analytics Custom Reports to get the Useful Insights.

## Value replacement

You can insert snippets into your pages that will be replaced with the corresponding value. For example, the text `{Pipeline::get("device", "browsername")}` would be replaced with `Chrome`, `Safari` and `Firefox`, etc. Depending on the browser being used by the person visiting your site. To set this up, take the text from the 'Usage in Content' column on the 'Properties' tab of the plugin.

## Conditional blocks

This feature allows you to show/hide content based on the property values supplied by the Pipeline API. To start, click add a new block and select the `51Degrees conditional group block`.
Select the block to display the configuration UI on the right-hand side. For example, upu can configure the block to only appear if the hardware vendor property is 'Apple'.


## Accessing properties in PHP code

To get a specific property, look it up on the available properties list and use the get() method specified.

`Pipeline::get("device", "ismobile")`
You can also get a list of properties by category as an array.

`Pipeline::getCategory("Supported Media"))`

Above code snippet will give you all the properties with `Supported Media` category included in the resource key.

## JavaScript Integration

The 51Degrees library exposes the same property values in JavaScript. These are accessed through the global `fod` object

`
<script type="text/javascript" >
	window.onload = function() {
	  fod.complete(function(data){
	  // console.log(data.device.screenpixelswidth);
	  });
	}
</script>
`


In some cases, additional evidence needs to be gathered by running JavaScript on the client. This is mostly handled automatically by the plugin and the fod object. For specific examples, see the 'Location' and 'Apple device models' sections below.

## Location

Location works slightly differently to other properties. Currently, the address is determined from the location provided by the client device. When this data is requested, a confirmation pop-up will appear. It is good practice to delay the appearance of this pop-up until the location is really needed. Otherwise, the user may not know why they are being asked for the information and is more likely to refuse. To facilitate this, the location data needs to be explicitly requested by adding some additional JavaScript. There are many ways to do this but for an example, we have gone with the simplest approach.

Firstly, add a button to your page. Make sure to set a css class that we can use to identify this button and add an event to it.

Next, add an HTML element and paste the following snippet of code into it:

`
<script type="text/javascript" >
	window.onload = function() {
	  var elements = document.getElementsByClassName('get-user-location');

	  for(var i = 0; i < elements.length; i++) {
		elements[i].addEventListener('click', function() {
		  fod.complete(function(data) { /* use values here if needed e.g. data.location.country will contain country the user is in */ }, 'location');
		});
	  }
	};
</script>
`

Now, when the user clicks on the 'Use my location' button, the JavaScript that we pasted in will execute. This lets the global `fod` object know that we want access to the location data, which in turn causes the 'wants to know your location' confirmation pop-up to be displayed.

**Note:** On the first request, the server will not have the location information so the location properties will not have values. After the button is clicked, we need to make another request to the server for the location values to be populated.  The content on the page can also be updated by using JavaScript, rather than waiting for the user to make a second request. This involves editing the JavaScript snippet above to update the page within the callback function that is passed to fod.complete.

## Apple device models

Determining the exact model of Apple devices is more difficult that others. This is because Apple include only very limited information about the device hardware in the 'User-Agent' HTTP header that is sent to the webserver. To get around this problem, device detection uses JavaScript that runs directly on the client to gether some additional information. This can usually be used to determine the exact model of device and will at least narrow down the possibilities.

The WordPress plugin will handle this for you automatically. However, be aware that, due to having to get additional data from the client, the model may be less clear on the first request than on subsequent requests. After the JavaScript runs on the client, a second request is made and the array of values would be significantly narrowed down.

**Note**: The content on the page can also be updated by using JavaScript, rather than waiting for the user to make a second request. The global `fod` object can be used to pass a callback that is executed when the updated values are available. For example:

`
<script type="text/javascript" >
	window.onload = function() {
	  fod.complete(function(data) { /* access values here. e.g. data.device.hardwarename */ });
	};
</script>
`

== Installation ==
 
Following three ways can be used to install 51Degrees WordPress Plugin.

= Installation from within WordPress =

1. Visit `Plugins > Add New`.
2. Search for `51Degrees`.
3. Install and activate the 51Degrees plugin.
 
= Manual installation using WordPress Plugin Manager =

1. Download `fiftyonedegrees` zip package from [WordPress Plugin Manager](https://wordpress.org/plugins/wp-plugin-manager/).
2. Upload the entire `fiftyonedegrees` zip folder to the `/wp-content/plugins/` directory.
3. Visit `Plugins`.
4. Activate the 51Degrees plugin.

= Manual installation using GitHub Repository =

If you want to build the plugin yourself and install locally, you will need to follow these steps:

1. Clone 51Degrees plugin GitHub repository from [here](https://github.com/51Degrees/pipeline-wordpress/).
2. Execute `composer install` in the `lib` directory.
3. Create a new directory outside this repo called `fiftyonedegrees` and copy all directories and php files from the root of this repo into it.
4. Copy the `fiftyonedegrees` directory into your 'wp-content/plugins' directory.
5. Visit `Plugins`.
6. Activate the 51Degrees plugin.
 
= After activation =

1. Visit the new `51Degrees` Settings menu.
2. To start using this plugin, you will need to create a `resource key`. This enables access to the data you need via the 51Degrees cloud service. You can create a `resource key` for free, using the [configurator](https://configure.51degrees.com/) to select the properties you want.

= Integration with Google Analytics =

1. To integrate with Google Analytics goto `Google Analytics` tab and click `Log in with Google Analytics Account` button, follow the steps and give 51Degrees plugin the required permissions and copy the provided Google Analytics `Access Code` in the end.
2. Enter the copied Code in `Access Code` text field and click `Authenticate`. This will connect your Google Analytics Account to 51Degrees Plugin.
3. After authentication, select your preferred profiles for which you want to enable Custom Dimensions Tracking via `Google Analytics Property` dropdown.
4. Check `Send Page View` if you want to send Default Page View hit along with Custom Dimensions. It is only recommended if you have not already integrated with any other Google Analytics plugin to avoid data duplication.
5. Click `Save Changes`. This will prompt to new Custom Dimensions Screen where you can find all the Custom Dimensions available with resource key.
6. Click on `Enable Google Analytics Tracking` to enable tracking of all the Device Data Properties as Custom Dimensions.


== Screenshots ==

1. Value Replacement - Browser Vendor, Name and Version Output
2. Value Replacement - Browser Vendor, Name and Version Setup
3. Conditional Block  - Conditional Block Settings
4. Conditional Block - Add 51Degrees Block
5. Location - Know Your Location
6. Location - Set CSS Class Name
7. Location - HTML Element
8. Location - First Request Town and Country Output
9. Location - Second Request Town and Country Output
10. Apple Device Models - First Request hardware Property for iPhone 6 Plus
11. Apple Device Models - Second Request hardware Property for iPhone 6 Plus
12. Google Analytics - Connect with Google Analytics
13. Google Analytics - Permissions Screen
14. Google Analytics - Access Code & Authenticate
15. Google Analytics - Select Property
16. Google Analytics - Enable Tracking

== Frequently Asked Questions ==

For more information, visit the [official 51Degrees website](https://51degrees.com/).

= Where should I submit my support request? =

If you're experiencing any issues, use the [wordpress.org support forums](https://wordpress.org/support/plugin/fiftyonedegrees). If you have a technical issue with the plugin where you already have more insight on how to fix it, you can also [open an issue on GitHub](https://github.com/51Degrees/pipeline-wordpress/issues).
 
= Is 51Degrees free? =

The 51Degrees plugin is free and open source. However Our [Cloud Configurator](https://configure.51degrees.com/) contains both FREE and PAID properties. The properties you will need to pay for are shown with a dollar icon. You can buy what you need on our [Pricing Page](https://51degrees.com/pricing).

= What happens if I already use another plugin to integrate Google Analytics? =

You can continue using your existing installed plugins to send Custom Dimensions or view Analytics Data along with 51Degrees plugin.


== Changelog ==
 
= 1.0.0 =
* Initial Release.

== Upgrade Notice ==

= 1.0.0 =
* Install 51Degrees Plugin.
 
== Project documentation ==

For complete documentation on the Pipeline API and associated engines, see the [51Degrees documentation][Documentation].
Note that the WordPress plugin is built on top of the PHP implementation of the Pipeline API

[Documentation]: https://51degrees.com/device-detection-php/4.3/md__home_vsts_work_1_s_apis_device-detection-php_readme.html