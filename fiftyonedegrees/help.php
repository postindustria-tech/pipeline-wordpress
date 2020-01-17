<p>Below are some helpful snippets for you to use in your theme or plugin development.</p>

<h3>Core API</h3>

<p>To get a specific property, look it up on the <a href='?page=51Degrees&tab=properties'>available properties list</a> and use the get() method.</p>

<pre>fiftyonedegrees::get(ismobile)</pre>

<h3>Loading 51Degrees specific JavaScript</h3>

<p>The 51Degrees library uses client side JavaScript to get access to additional values. To insert this in the page use.</p>

<pre>fiftyonedegrees::javascript()</pre>

<p>Optionally, you can pass in false to not echo it and omit the script tags.</p>

<pre>fiftyonedegrees::javascript(false)</pre>

<h3>Get a list of properties by category as an array:</h3>

<pre>fiftyonedegrees::getCategory("Supported Media"))</pre>

<?php if(fiftyonedegrees::get("ismobile")){


}; 

?>
