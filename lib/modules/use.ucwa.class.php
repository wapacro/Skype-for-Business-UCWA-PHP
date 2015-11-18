<?php
// UCWA Class

class UCWA_use extends ucwa_base {
	/*************************************************
	//	Variables
	*************************************************/
	
	/*
	 *	Server configuration
	*/
	protected static $ucwa_path_send = "";
	protected static $ucwa_path_terminate = "";
	
	/*************************************************
	//	Constructor
	*************************************************/
	function __construct($token = "", $baseserver = "", $path_app = "", $path_xframe = "", $fqdn = "") {
		if ( !empty( $token ) ) {
			self::$ucwa_accesstoken = $token;
		}
		
		if ( !empty( $baseserver ) ) {
			self::$ucwa_baseserver = $baseserver;	
		}
		
		if ( !empty( $path_app ) ) {
			self::$ucwa_path_application = $path_app;	
		}
		
		if ( !empty( $path_xframe ) ) {
			self::$ucwa_path_xframe = $path_xframe;
		}
		
		if ( !empty( $fqdn ) ) {
			$link = parse_url($fqdn);
			self::$ucwa_fqdn = $link["scheme"] . "://" . $link["host"];	
		}
	}
	
	/*************************************************
	//	Main methods
	*************************************************/
	
	/*
	 *	(bool) registerApplication($agent)
	 *	######################################
	 *
	 *	Register the application
	*/
	public static function registerApplication($agent) {		
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
					"userAgent" => $agent,
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
					"subject" => $subject,
					"telemetryId" => NULL,
					"to" => $to,
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
	 *	Wait 'til the user accepts the conversation
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
	
	/*
	 *	(bool) deleteApplication()
	 *	######################################
	 *
	 *	Deletes the current application
	*/
	public static function deleteApplication() {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_URL => self::$ucwa_baseserver . self::$ucwa_path_application_fq,
			CURLOPT_REFERER => self::$ucwa_baseserver . self::$ucwa_path_xframe,
			CURLOPT_CUSTOMREQUEST => "DELETE",
			CURLOPT_HTTPHEADER => array(
				"Authorization: Bearer " . self::$ucwa_accesstoken,
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
			self::_error("Can't delete application for Skype UCWA", $status);	
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
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_URL => self::$ucwa_baseserver . self::$ucwa_path_send,
			CURLOPT_REFERER => self::$ucwa_baseserver . self::$ucwa_path_xframe,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $msg,
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

}

?>