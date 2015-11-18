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

/*
 * Export important data
*/
// This method allows us to export important data
// such as urls, the access token and so on...
$ucwa_data = $ucwa->getUCWAData();

/*
 * Define receivers, messages & more
*/
$send = array();
$url = "http://www.example.com/examples/advanced/advanced_example_send.php"; // <= Set this!

// Receiver 1
$send[] = array(
	"url" => $url,
	"post" => array(
		"accesstoken" => $ucwa_data["accesstoken"],
		"baseserver" => $ucwa_data["baseserver"],
		"path_app" => $ucwa_data["path_application"],
		"path_xframe" => $ucwa_data["path_xframe"],
		"to" => "sip:some.one@example.com",
		"subject" => "First receiver!",
		"msg" => array(
			"Message 1!",
			"Message 2"
		),
	),
);

// Receiver 2
$send[] = array(
	"url" => $url,
	"post" => array(
		"accesstoken" => $ucwa_data["accesstoken"],
		"baseserver" => $ucwa_data["baseserver"],
		"path_app" => $ucwa_data["path_application"],
		"path_xframe" => $ucwa_data["path_xframe"],
		"to" => "sip:someone.else@example.com",
		"subject" => "Second receiver!",
		"msg" => array(
			"Just one message for you :)",
		),
	),
);

// Receiver 3
$send[] = array(
	"url" => $url,
	"post" => array(
		"accesstoken" => $ucwa_data["accesstoken"],
		"baseserver" => $ucwa_data["baseserver"],
		"path_app" => $ucwa_data["path_application"],
		"path_xframe" => $ucwa_data["path_xframe"],
		"to" => "sip:another.one@example.com",
		"subject" => "Third receiver!",
		"msg" => array(
			"Here are some emoticons for you;",
			":D :P",
			";) :)",
			"(rofl)"
		),
	),
);

/*
 * Send requests at the same time
*/
print_r( multiRequest( $send ) );


/*###################################################################################################
// Helper Functions
/*##################################################################################################*/
function multiRequest($data, $options = array()) {
	// Array mit curl handles
	$curly = array();
	// Return Data
	$result = array();
	
	// multi handle
	$mh = curl_multi_init();
	
	// Durch $data gehen und Curl-Handles erstellen
	// und dem Multi-Request hinzufügen
	foreach ($data as $id => $d) {
		$curly[$id] = curl_init();
		
		$url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;
		curl_setopt($curly[$id], CURLOPT_URL,            $url);
		curl_setopt($curly[$id], CURLOPT_HEADER,         0);
		curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);
		
		// post?
		if (is_array($d)) {
		  if (!empty($d['post'])) {
			curl_setopt($curly[$id], CURLOPT_POST,       1);
			curl_setopt($curly[$id], CURLOPT_POSTFIELDS, http_build_query($d['post']));
		  }
		}
		
		// extra options?
		if (!empty($options)) {
			curl_setopt_array($curly[$id], $options);
		}
		
		curl_multi_add_handle($mh, $curly[$id]);
	}
	
	// Parallel-Requests starten
	$running = null;
	do {
		curl_multi_exec($mh, $running);
	} while($running > 0);
	
	
	// Content erhalten und Handles schliessen
	foreach($curly as $id => $c) {
		$result[$id] = curl_multi_getcontent($c);
		curl_multi_remove_handle($mh, $c);
	}
	
	// Socket schliessen
	curl_multi_close($mh);
	
	return $result;
}
?>