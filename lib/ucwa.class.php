<?php
class UCWA {
	
	/*************************************************
	//	Variables
	*************************************************/

	/*
	 *	Conversation things
	*/
	private static $ucwa_conv_subject = "";
	private static $ucwa_conv_to = "sip:";
	private static $ucwa_conv_msg = "";

	
	/*
	 *	Server configuration
	*/
	private static $ucwa_autodiscover = "http://lyncdiscoverinternal.example.com";
	private static $ucwa_fqdn = "";
	private static $ucwa_baseserver = "";
	private static $ucwa_path_oauth = "";
	private static $ucwa_path_user = "";
	private static $ucwa_path_xframe = "";
	private static $ucwa_path_application = "";
	private static $ucwa_path_conversation = "";
	private static $ucwa_path_events = "";
	private static $ucwa_path_send = "";
	private static $ucwa_path_terminate = "";
	
	/*
	 *	Storage
	*/
	private static $ucwa_accesstoken = "";
	private static $ucwa_operationid = "";
	private static $ucwa_user = "";
	private static $ucwa_pass = "";
	
	
	/*************************************************
	//	Constructor
	*************************************************/
	function __construct($fqdn) {
		// FQDN
		$link = parse_url($fqdn);
		self::$ucwa_fqdn = $link["scheme"] . "://" . $link["host"];
		
		// Do AutoDiscover
		if ( self::autodiscover() ) {
			// Get OAuth URL
			if ( !self::getOauthLink() ) {
				return false;	
			}
		} else {
			return false;	
		}
	}
	
	/*************************************************
	//	Main methods
	*************************************************/
	
	
	/*
	 *	(bool) autodiscover()
	 *	######################################
	 *
	 *	Discovers the required URL's automatically
	*/
	private static function autodiscover() {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_URL => self::$ucwa_autodiscover,
			CURLOPT_HTTPHEADER => array(
				"Accept" => "application/json",
				"X-Ms-Origin" => self::$ucwa_fqdn,
			),
			CURLOPT_TIMEOUT => 15,
		));
		
		$response = curl_exec($curl);
		$status = curl_getinfo($curl);
		curl_close($curl);	
		
		if ($status["http_code"] == 200) {
			$data = json_decode($response, true);
			$link_usr = parse_url($data["_links"]["user"]["href"]);
			$link_frm = parse_url($data["_links"]["xframe"]["href"]);
			
			self::$ucwa_baseserver = $link_usr["scheme"] . "://" . (substr($link_usr["host"], -1) == "/" ? substr($link_usr["host"], 0, -1) : $link_usr["host"]);
			self::$ucwa_path_user = ( substr($link_usr["path"], 0, 1) == "/" ? "" : "/" ) . $link_usr["path"] . "?" . $link_usr["query"];
			self::$ucwa_path_xframe = ( substr($link_frm["path"], 0, 1) == "/" ? "" : "/" ) . $link_frm["path"];
			
			return true;
		} else {
			self::_error("Can't automatically detect user url. Autodiscover failed.", $status);	
		}
	}
	
	/*
	 *	(bool) getOauthLink()
	 *	######################################
	 *
	 *	Get the OAuth link from the
	 *	(unauthorized) "user" site
	*/
	private static function getOauthLink() {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_HEADER => true,
			CURLOPT_NOBODY => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_URL => self::$ucwa_baseserver . self::$ucwa_path_user,
			CURLOPT_REFERER => self::$ucwa_baseserver . self::$ucwa_path_xframe,
			CURLOPT_HTTPHEADER => array(
				"Accept" => "application/json",
				"X-Ms-Origin" => self::$ucwa_fqdn,
			),
			CURLOPT_TIMEOUT => 15,
		));
		
		$response = curl_exec($curl);
		$status = curl_getinfo($curl);
		curl_close($curl);	
		
		if ($status["http_code"] == 401) {			
			preg_match('/href=["\']?([^"\'>]+)["\']?/', $response, $match);
			$link = parse_url( $match[1] );
			
			self::$ucwa_path_oauth = (substr($link["path"], 0, 1) == "/" ? "" : "/") . $link["path"];
			return true;
		} else {
			self::_error("Can't get OAuth-Link.", $status);
		}
	}
	
	/*
	 *	(bool) getAccessToken()
	 *	######################################
	 *
	 *	Authorizes the sender and stores
	 *	the access token.
	*/
	public static function getAccessToken($username, $password) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_URL => self::$ucwa_baseserver . self::$ucwa_path_oauth,
			CURLOPT_REFERER => self::$ucwa_baseserver . self::$ucwa_path_xframe,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => array(
				"grant_type" => "password",
				"username" => $username,
				"password" => $password,
			),
			CURLOPT_HTTPHEADER => array(
				"Accept" => "application/json",
				"X-Ms-Origin" => self::$ucwa_fqdn,
			),
			CURLOPT_TIMEOUT => 15,
		));
		
		$response = curl_exec($curl);
		$status = curl_getinfo($curl);
		curl_close($curl);
		
		if ($status["http_code"] == 200) {
			$data = json_decode($response, true);
			self::$ucwa_accesstoken = $data["access_token"];
			self::$ucwa_user = $username;
			self::$ucwa_pass = $password;
			
			return true;
		} else {
			self::_error("Can't get an access token for Skype UCWA", $status);	
			return false;
		}
	}
	
	/*
	 *	(bool) getApplicationLink()
	 *	######################################
	 *
	 *	Get the application link and check,
	 *	if the Skype "Pool" for the authorized
	 *	user is the same as the autodiscover
	 *	pool.
	*/
	public static function getApplicationLink() {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_URL => self::$ucwa_baseserver . self::$ucwa_path_user,
			CURLOPT_REFERER => self::$ucwa_baseserver . self::$ucwa_path_xframe,
			CURLOPT_HTTPHEADER => array(
				"Accept: application/json",
				"Authorization: Bearer " . self::$ucwa_accesstoken,
				"X-Ms-Origin: " . self::$ucwa_fqdn,
			),
			CURLOPT_TIMEOUT => 15,
		));
		
		$response = curl_exec($curl);
		$status = curl_getinfo($curl);
		curl_close($curl);
		
		if ($status["http_code"] == 200) {
			$data = json_decode($response, true);
			$link = parse_url($data["_links"]["applications"]["href"]);
			
			self::$ucwa_path_application = $link["path"] . (isset($link["query"]) ? '?' . $link["query"] : '');
			
			// Check if Hostname is the same
			if ( self::$ucwa_baseserver != $link["scheme"] . "://" . ( substr($link["host"], -1) == "/" ? substr($link["host"], 0, -1) : $link["host"] ) ) {
				// Hostname different!
				// New access token
				self::$ucwa_baseserver = $link["scheme"] . "://" . ( substr($link["host"], -1) == "/" ? substr($link["host"], 0, -1) : $link["host"] );
				
				if ( self::getAccessToken(self::$ucwa_user, self::$ucwa_pass) ) {
					return true;	
				} else {
					return false;	
				}
			} else {
				return true;
			}
		} else {
			self::_error("Can't get applications link for Skype UCWA", $status);	
			return false;
		}
	}
	
	/*
	 *	(bool) registerApplication($agent)
	 *	######################################
	 *
	 *	Register the application
	*/
	public static function registerApplication($agent) {
		self::$ucwa_auth_agent = $agent;
		
		$curl = curl_init();
		curl_setopt_array($curl, array(

			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_URL => self::$ucwa_baseserver . self::$ucwa_path_application,
			CURLOPT_REFERER => self::$ucwa_baseserver . self::$ucwa_path_xframe,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode(array(
					"userAgent" => self::$ucwa_auth_agent,
					"endpointId" => self::_generateUUID(),
					"culture" => "de-CH"
				)
			),
			CURLOPT_HTTPHEADER => array(
				"Accept: application/json",
				"Authorization: Bearer " . self::$ucwa_accesstoken,
				"Content-Type: application/json",
				"X-Ms-Origin: " . self::$ucwa_fqdn,
			),
			CURLOPT_TIMEOUT => 15,
		));
		
		$response = curl_exec($curl);
		$status = curl_getinfo($curl);
		curl_close($curl);
		
		if ($status["http_code"] == 201) {
			$data = json_decode($response, true);
			$keys = array_keys($data["_embedded"]["communication"]);
			
			self::$ucwa_path_conversation = $data["_embedded"]["communication"]["_links"]["startMessaging"]["href"];
			self::$ucwa_path_events = $data["_links"]["events"]["href"];
			self::$ucwa_operationid = $keys[0];
			
			return true;
		} else {
			self::_error("Can't register application for Skype UCWA", $status);	
			return false;
		}	
	}
	
	/*
	 *	(bool) createConversation($to, $subject)
	 *	######################################
	 *
	 *	Create a conversation
	*/
	public static function createConversation($to, $subject) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_URL => self::$ucwa_baseserver . self::$ucwa_path_conversation,
			CURLOPT_REFERER => self::$ucwa_baseserver . self::$ucwa_path_xframe,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode(array(
					"importance" => "Normal",
					"sessionContext" => self::_generateUUID(),
					"subject" => self::$ucwa_conv_subject,
					"telemetryId" => NULL,
					"to" => self::$ucwa_conv_to,
					"operationId" => self::$ucwa_operationid
				)
			),
			CURLOPT_HTTPHEADER => array(
				"Authorization: Bearer " . self::$ucwa_accesstoken,
				"Content-Type: application/json",
				"X-Ms-Origin: " . self::$ucwa_fqdn,
			),
			CURLOPT_TIMEOUT => 15,
		));
		
		$response = curl_exec($curl);
		$status = curl_getinfo($curl);
		curl_close($curl);
		
		if ($status["http_code"] == 201) {	
			return true;
		} else {
			self::_error("Can't create conversation for Skype UCWA", $status);	
			return false;
		}
	}
	
	/*
	 *	(bool/null) waitForAccept($recursive = true)
	 *	######################################
	 *
	 *	Wait 'till the user accepts the conversation
	*/
	public static function waitForAccept($recursive = true) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_URL => self::$ucwa_baseserver . self::$ucwa_path_events,
			CURLOPT_REFERER => self::$ucwa_baseserver . self::$ucwa_path_xframe,
			CURLOPT_HTTPHEADER => array(
				"Accept: application/json",
				"Authorization: Bearer " . self::$ucwa_accesstoken,
				"X-Ms-Origin: " . self::$ucwa_fqdn,
			),
			CURLOPT_TIMEOUT => 30,
		));
		
		$response = curl_exec($curl);
		$status = curl_getinfo($curl);
		curl_close($curl);
		
		if ($status["http_code"] == 200) {	
			$data = json_decode($response, true);
			$return = false;
			foreach ($data["sender"] as $sender) {
				if ( strtolower($sender["rel"]) == "conversation" ) {
					foreach ( $sender["events"] as $events) {
						if ( array_key_exists("_embedded", $events) ) {
							if ( array_key_exists("messaging", $events["_embedded"]) ) {
								if ( strtolower($events["_embedded"]["messaging"]["state"]) == "connected" || strtolower($events["_embedded"]["messaging"]["state"]) == "success" ) {
									// Conversation accepted
									// Get messaging links

									self::$ucwa_path_send = $events["_embedded"]["messaging"]["_links"]["sendMessage"]["href"];
									self::$ucwa_path_terminate = $events["_embedded"]["messaging"]["_links"]["stopMessaging"]["href"];	
									
									$return = true;
								}
							}
						}
					}
				}
			}
			
			self::$ucwa_path_events = $data["_links"]["next"]["href"];
			
			if ($return) {
				return true;	
			} else {
				if ($recursive) {
					return self::waitForAccept($recursive);	
				} else {
					return true;	
				}
			}
		} else {
			self::_error("Can't get events for Skype UCWA", $status);	
			return false;
		}
	}
	
	/*
	 *	(bool) sendMessage($msg)
	 *	######################################
	 *
	 *	Sends a message
	*/
	public static function sendMessage($msg) {
		self::$ucwa_conv_msg = $msg;
		
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_URL => self::$ucwa_baseserver . self::$ucwa_path_send,
			CURLOPT_REFERER => self::$ucwa_baseserver . self::$ucwa_path_xframe,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => self::$ucwa_conv_msg,
			CURLOPT_HTTPHEADER => array(
				"Authorization: Bearer " . self::$ucwa_accesstoken,
				"Content-Type: text/plain; charset=UTF-8",
				"X-Ms-Origin: " . self::$ucwa_fqdn,
			),
			CURLOPT_TIMEOUT => 20,
		));
		
		$response = curl_exec($curl);
		$status = curl_getinfo($curl);
		curl_close($curl);	
		
		if ($status["http_code"] == 201) {
			return true;
		} else {
			self::_error("Can't send message for Skype UCWA", $status);	
			return false;	
		}
	}
	
	/*
	 *	(bool) terminateConversation()
	 *	######################################
	 *
	 *	Terminate the conversation
	*/
	public static function terminateConversation() {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_URL => self::$ucwa_baseserver . self::$ucwa_path_terminate,
			CURLOPT_REFERER => self::$ucwa_baseserver . self::$ucwa_path_xframe,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => "Exterminate!",
			CURLOPT_HTTPHEADER => array(
				"Authorization: Bearer " . self::$ucwa_accesstoken,
				"Content-Type: text/plain; charset=UTF-8",
				"X-Ms-Origin: " . self::$ucwa_fqdn,
			),
			CURLOPT_TIMEOUT => 15,
		));
		
		$response = curl_exec($curl);
		$status = curl_getinfo($curl);
		curl_close($curl);	
		
		if ($status["http_code"] == 204) {
			return true;
		} else {
			self::_error("Can't terminate conversation for Skype UCWA", $status);	
			return false;	
		}
	}
	
	/*************************************************
	//	Helper methods
	*************************************************/
	
	/*
	 *	(void) _error
	 *	######################################
	 *
	 *	Logging feature
	*/
	private static function _error($text, $debug) {	
		$file = fopen('ucwa.log', 'a');
		fwrite($file, date("d-m-Y H:i:s") . ' | ' . $text . ' | ' . var_export($debug, true) . "\r\n");
		fclose($file);
	}
	
	/*
	 *	(string) _generateUUID
	 *	######################################
	 *
	 *	Generates an unique ID
	*/
	private static function _generateUUID() {
		return  str_replace(".", "", uniqid(md5( time() ), true));	
	}
	
}

?>