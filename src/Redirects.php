<?php
namespace Truecast;
/**
 * Redirect class
 *
 * @package General
 * @author Daniel Baldwin
 * @version 1.0.1
 */
class Redirects
{
	/**
	* Perform a 301 redirect of the url if needed.
	* json file should use keys for request uri and values for redirected uri
	* json format: {
	"path1/path2/":"path3/",
	"path4/path5/*":"path4/path6/*",  <- matches "path4/path5/path8/" redirects to "path4/path6/path8/" The path that matches the * on the request is moved over to the end of the redirect uri in place of the *
	"path7/path9/*":"path7/path10/",  <- matches "path7/path9/path8/" redirects to "path7/path10/" The path that matches the * on the request is NOT moved over to the end of the redirect uri because there is no * on the redirect
	}
	*
	* @param array $params ['request'=>$_SERVER['REQUEST_URI'], 'lookup'=>BP.'/redirects.json', 'type'=>'301']
	* @return type
	* @throws conditon
	**/
	public static function redirect($params)
	{
		$redirect = null;
		# check to make sure all keys are passed.
		if ( array_diff(['request', 'lookup', 'type'], array_keys($params)) ) {
			trigger_error("One of the required parameters is missing from your array passed.", 256);
			return false;
		}   
	  
		extract($params);
	  
		$redirectList = json_decode(file_get_contents($lookup), true);

		if (json_last_error() !== 0) {
			trigger_error("There was an error parsing the redirects json file. Error: ".json_last_error(), 256);
			return false;
		}

		$requestUri = ltrim($request,'/');

		if (array_key_exists($requestUri, $redirectList)) {
			$redirect = $redirectList[$requestUri];
		} else {
			foreach ($redirectList as $key=>$value) {
				# check whether an internal redirect has a forward slash at the front and if not, add it
				if(substr($value, 0, 1) !== "/" && substr($value, 0, 7) !== "http://" && substr($value, 0, 8) !== "https://") {
					$value = "/" . $value;
				}
				
				$match = strstr($key, '*', true);
				if ($match !== false) {
					$strLen = strlen($match);
					$matchingPartOfRequest = substr($requestUri, 0,$strLen);
					if ($match == $matchingPartOfRequest) {
						# check whether to add end of url to end of redirct or not 
						if (strpos($value, '*') !== false) {
							$requestLen = strlen($requestUri) - $strLen;
							$redirect = str_replace('*','',$value).substr($requestUri, -$requestLen);
						} else {
							$redirect = $value;
						}						
					}
				}
			}
			
			if ($redirect === null) {
				return false;
			}
		}
		
		switch ($type) {
			case "301": $header = "301 Moved Permanently"; break; # redirects permanently from one URL to another passing link equity to the redirected page
			case "303": $header = "303 See Other"; break; # forces a GET request to the new URL even if original request was POST
			case "307": $header = "307 Temporary Redirect"; break; # forces a GET request to the new URL even if original request was POST
			case "308": $header = "308 Permanent Redirect"; break; # The request and all future requests should be repeated using another URI, using same method
		}

		header("HTTP/1.1 $header"); 
		header("Location: $redirect");
		exit;
	}
}
