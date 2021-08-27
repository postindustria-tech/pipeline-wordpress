# 51Degrees Pipeline API

![51Degrees](https://51degrees.com/DesktopModules/FiftyOne/Distributor/Logo.ashx?utm_source=github&utm_medium=repository&utm_content=readme_main&utm_campaign=dotnet-open-source "Data rewards the curious") **Pipeline API - WordPress plugin**

[Developer Documentation](https://docs.51degrees.com?utm_source=github&utm_medium=repository&utm_content=documentation&utm_campaign=dotnet-open-source "developer documentation")

# Introduction
This repository contains the code for a WordPress plugin that makes use 
of the 51Degrees Pipeline API to deliver various data intelligence 
[services](https://51degrees.com/services).

# Pre-requesites

In order to use this plugin, you will need to create a **resource key**. This enables access to the data you need via the 51Degrees cloud service.

You can create a **resource key** for free, using the [configurator](https://configure.51degrees.com/) to select the properties you want.

# Installation

TODO: Add installation instructions if installing through the WordPress plugin system.

If you want to build the plugin yourself and install locally, you will need to follow these steps:

1. Execute `composer install` in the 'library' directory.
2. Create a new directory outside this repo called 'pipeline-plugin' and copy all directories and php files from the root of this repo into it.
3. Copy the 'pipeline-plugin' directory into your 'wp-content/plugins' directory.

# Features

## Value replacement

You can insert snippets into your pages that will be replaced with the corresponding value.

For example, the text `{fiftyonedegrees::get("device", "browsername")}` would be replaced with `Chrome`, `Safari`, `Firefox`, etc. Depending on the browser being used by the person visiting your site.

In this case, we display the vendor, name and version number of the client's browser:

![ValueReplacementExample1](static/property-example.png)

To set this up, we take the text from the 'Usage in Content' column on the 'properties' page of the plugin:

![ValueReplacementExample2](static/properties-in-content.png)

## Conditional blocks

This feature allows you to show/hide content based on the property values supplied by the Pipeline API.

To start, add a new block and select the '51Degrees conditional group block':

![ConditionalBlockExample1](static/conditional-block-1.png)

Select the block to display the configuration UI on the right-hand side. In the example below, the block has been configured to only appear if the hardware vendor property is 'Apple':

![ConditionalBlockExample2](static/conditional-block-2.png)


## Accessing properties in PHP code

To get a specific property, look it up on the available properties list and use the get() method specified.

```fiftyonedegrees::get("device", "ismobile")```

You can also get a list of properties by category as an array:

```fiftyonedegrees::getCategory("Supported Media"))```

## JavaScript integration

The 51Degrees library exposes the same property values in JavaScript. These are accessed through the global 'fod' object

```
window.onload = function() {
  fod.complete(function(data){
  // console.log(data.device.screenpixelswidth);
  });
}
```

In some cases, additional evidence needs to be gathered by running JavaScript on the client. This is mostly handled automatically by the plugin and the fod object. For specific examples, see the 'Location' and 'Apple device models' sections below

## Location

Location works slightly differently to other properties. Currently, the address is determined from the location provided by the client device. When this data is requested, a confirmation pop-up similar to the following will appear:

![LocationExample1](static/location-1.png)

It is good practice to delay the appearance of this pop-up until the location is really needed. Otherwise, the user may not know why they are being asked for the information and is more likely to refuse.

To facilitate this, the location data needs to be explicitly requested by adding some additional JavaScript. There are many ways to do this but for an example, we have gone with the simplest approach.

Firstly, add a button to your page. Make sure to set a css class that we can use to identify this button and add an event to it.

![LocationExample2](static/location-2.png)

Next, add an HTML element and paste the following snippet of code into it:

```
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
```

![LocationExample3](static/location-3.png)

Now, when the user clicks on the 'Use my location' button, the JavaScript that we pasted in will execute. This lets the global `fod` object know that we want access to the location data, which in turn causes the 'wants to know your location' confirmation pop-up to be displayed.

Note that on the first request, the server will not have the location information so the location properties will not have values: 

![LocationExample4](static/location-4.png)

After the button is clicked, we need to make another request to the server for the location values to be populated:

![LocationExample5](static/location-5.png)

Note that the content on the page can also be updated by using JavaScript, rather than waiting for the user to make a second request. This involves editing the JavaScript snippet above to update the page within the callback function that is passed to fod.complete.

## Apple device models

Determining the exact model of Apple devices is more difficult that others. This is because Apple include only very limited information about the device hardware in the 'User-Agent' HTTP header that is sent to the webserver.

To get around this problem, device detection uses JavaScript that runs directly on the client to gether some additional information. This can usually be used to determine the exact model of device and will at least narrow down the possibilities.

The WordPress plugin will handle this for you automatically. However, be aware that, due to having to get additional data from the client, the model may be less clear on the first request than on subsequent requests.

For example, using an iPhone 6 Plus, the `hardwareName` property contains the following array of values on the first request:

![AppleExample1](static/apple-device-1.png)

After the JavaScript runs on the client, a second request is made and the array of values has been significantly narrowed down:

![AppleExample2](static/apple-device-2.png)

The content on the page can also be updated by using JavaScript, rather than waiting for the user to make a second request. The global `fod` object can be used to pass a callback that is executed when the updated values are available. For example:

```
<script type="text/javascript" >
window.onload = function() {
  fod.complete(function(data) { /* access values here. e.g. data.device.hardwarename */ });
};
</script>
```

# Project documentation

For complete documentation on the Pipeline API and associated engines, see the [51Degrees documentation][Documentation].
Note that the WordPress plugin is built on top of the PHP implementation of the Pipeline API

[Documentation]: https://docs.51degrees.com