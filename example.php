<?php
/********************************************
//				example.php
//-------------------------------------------
//	This file shows how to use the UCWA
//	PHP class. 
//-------------------------------------------
// Initial commit by Roman Ackermann
********************************************/

// Include the UCWA class
require( "lib/ucwa.class.php" );

// Initialize the class
$ucwa = new UCWA( "http://myapp.example.com" ) OR die( "Autodiscover failed" );						// You have to specify your full qualified domain name (FQDN)
																									// which has to be allowed on the Skype for Business/Lync-Server.
// Get Access token
$ucwa->getAccessToken( "user@example.com", "p@ssw0rd!" ) OR die( "Couldn't get an access token" );	// Request an Access token with the credentials of your sender

// Get Application link
$ucwa->getApplicationLink() OR die( "Couldn't get the application link" );							// Get the Application link

// Register your application
$ucwa->registerApplication( "My first UCWA app" ) OR die( "Can't register application" );			// Register your app

// Create the conversation
// ...



?>