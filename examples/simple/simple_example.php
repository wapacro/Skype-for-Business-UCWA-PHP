<?php
/********************************************************
//	simple_example.php
//-------------------------------------------------------
//	This file shows how to use the UCWA
//	PHP class. It's a simple example where
//	we just send one instant message.
//-------------------------------------------------------
// Initial commit by Roman Ackermann
********************************************************/

/*
 * Include UCWA class
*/
require( "lib/base.ucwa.class.php" );

/*
 * Initialize the class
*/
// Create a new instance of UCWA_init and pass your FQDN (full qualified domain name),
// which has to be allowed on the Skype for Business/Lync Server
$ucwa = new UCWA_init( "http://myapp.example.com" ) OR die( "Autodiscover failed" );						
																					
/*
 * Get Access token
*/
// Gets and stores an access token.
// Authentication with passed username and password.
$ucwa->getAccessToken( "user@example.com", "p@ssw0rd!" ) OR die( "Couldn't get an access token" );							

/*####################################################################################################																	
Okay, we have initialized a new instance of UCWA_init.
Now we have to load the second module, called "UCWA_use".
In this simple example we'll use it in the same file, but if you want to send a lot of messages
you should do it in another file and pass parameters by using HTTP POST (see advanced_example.php).
Or - if time doesn't matter - do it in a loop.
/*####################################################################################################*/

/*
 * Initialize the USE class
*/
$im = new UCWA_use();

/*
 * Register application
*/
// You are now engaged to register your application.
// Be creative and chose your name.
$im->registerApplication( "My App" ) OR die( "Couldn't register application" );

/*
 * Create a conversation
*/
// We're sending now an invitation for a conversation
$im->createConversation( "sip:some.one@example.com", "Conversation Subject" ) OR die( "Couldn't create conversation" );

/*
 * Wait for accept
*/
// We have to wait until the receiver accepts the conversation.
// In Skype for Business 2015, the invitation will automatically be accepted
// after 30 seconds, if the user doesn't react.
$im->waitForAccept() OR die( "User ignored converation or is offline" );

/*
 * Send message
*/
// Great! The conversation has been accepted. That means, we're now
// able to send our message.
$im->sendMessage( "First message!" ) OR die( "Couldn't send <first message!>." );
$im->sendMessage( "Just a test..." ) OR die( "Couldn't send <just a test...>." );

/*
 * Terminate conversation
*/
// If you have nothing more to say, you should
// terminate the conversation.
$im->terminateConversation();

?>