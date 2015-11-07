<?php
/********************************************************
//	advanced_example_init.php
//-------------------------------------------------------
//	This file shows how to use the UCWA
//	PHP class. It shows how to send messages
//	to multiple receivers at the same time.
//-------------------------------------------------------
// Initial commit by Roman Ackermann
********************************************************/

/*
 * Include UCWA class
*/
require( "../../lib/base.ucwa.class.php" );

/*
 * Get POST parameters
*/
// Configuration things...
$v_accesstoken = !empty( $_POST["accesstoken"] ) ? $_POST["accesstoken"] : "";
$v_baseserver = !empty( $_POST["baseserver"] ) ? $_POST["baseserver"] : "";
$v_path_app = !empty( $_POST["path_app"] ) ? $_POST["path_app"] : "";
$v_path_xframe = !empty( $_POST["path_xframe"] ) ? $_POST["path_xframe"] : "";
$v_fqdn = !empty( $_POST["fqdn"] ) ? $_POST["fqdn"] : "";

// Message things...
$v_skype_to = !empty( $_POST["to"] ) ? $_POST["to"] : "";
$v_skype_subject = !empty( $_POST["subject"] ) ? $_POST["subject"] : "";
$v_skype_msgs = !empty( $_POST["msgs"] ) ? $_POST["msgs"] : array();


/*
 * Initialize the USE class
*/
// Pass the parameters which we sent in advanced_example_init.php
$im = new UCWA_use( $v_accesstoken, $v_baseserver, $v_path_app, $v_path_xframe, $v_fqdn );

/*
 * Register application
*/
// You are now engaged to register your application.
$im->registerApplication( "My App" ) OR die( "Couldn't register application" );

/*
 * Create a conversation
*/
$im->createConversation( $v_skype_to, $v_skype_subject ) OR die( "Couldn't create conversation" );

/*
 * Wait for accept
*/
// We have to wait until the receiver accepts the conversation.
// In Skype for Business 2015, the invitation will automatically be accepted
// after 30 seconds, if the user doesn't react.
$im->waitForAccept() OR die( "User ignored converation or is offline" );

/*
 * Send message(s)
*/
if ( count( $v_skype_msgs ) < 2 ) {
	// Just one message
	$im->sendMessage( reset( $v_skype_msgs ) ) OR die( "Couldn't send message" );	
} else {
	// More than one message
	foreach( $v_skype_msgs as $msg ) {
		$im->sendMessage( $msg );
	}
}

/*
 * Terminate conversation
*/
$im->terminateConversation() OR die( "Couldn't terminate conversation" );

// ##################################################################################################

// If the interpreter gets to this point, everything worked as expected.
echo "Everything okay for <" . $v_skype_to . ">";

?>