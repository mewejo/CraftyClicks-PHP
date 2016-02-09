<?php

namespace CraftyClicks;

class Base
{
	var $_API_Endpoint = "https://pcls1.craftyclicks.co.uk/json/";
	var $_AccessToken = null;
	var $_PATH = null;
	var $_DebugEnable = false;
	
	function __construct($AccessToken)
	{
		// Dependencies
		$this->_PATH = dirname(__FILE__) . "/";
		require_once($this->_PATH . "Exception.class.php");
		
		// Check and set $AccessToken
		$AccessToken = trim($AccessToken);
		if(strlen($AccessToken) < 1) throw new Exception("Invalid access token supplied");
		$this->_AccessToken = $AccessToken;
		
	}
	
	function __destruct()
	{
		
	}
	
	///////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////
	
	function _BuildAPICall($Params)
	{
		if(!$this->_AccessToken) throw new Exception("No access token set");
		$AccessTokenArray = array("key" => $this->_AccessToken);
		return array_merge($Params, $AccessTokenArray);
	}
	
	function _ExceuteAPICall($Route, $Params)
	{
		$Params = $this->_BuildAPICall($Params);
		
		// Set it up
		$cURL = curl_init($this->_API_Endpoint . $Route);
		curl_setopt($cURL, CURLOPT_HEADER, false);
		curl_setopt($cURL, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
		curl_setopt($cURL, CURLOPT_POST, true);
		curl_setopt($cURL, CURLOPT_POSTFIELDS, json_encode($Params));
		curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
				
		// Do it..
		$Result = curl_exec($cURL);
		curl_close($cURL);
		
		return json_decode($Result, true);
	}
	
	function _FormatRapidAddressResult($Result)
	{
		$Response = array();
		if($Result["thoroughfare_count"] < 1) return $Response;
		
		// Loop the thoroughfares
		foreach($Result["thoroughfares"] as $Thoroughfare)
		{
			if(count($Thoroughfare["delivery_points"]) < 1) continue;
			
			foreach($Thoroughfare["delivery_points"] as $DeliveryPoint)
			{
				$DeliveryPoint["name_parts"] = array();
				
				$DeliveryPoint["address_lines"] = array();
				$DeliveryPoint["address_lines"][1] = array();
				
				$NextAddressLine = 1;
				
				if(strlen($DeliveryPoint["organisation_name"]) > 0)
				{
					$DeliveryPoint["name_parts"][] = $DeliveryPoint["organisation_name"];
					$DeliveryPoint["address_lines"][$NextAddressLine][] = $DeliveryPoint["organisation_name"];
					$NextAddressLine++;
				}
				
				if(strlen($DeliveryPoint["department_name"]) > 0)
				{
					$DeliveryPoint["name_parts"][] = $DeliveryPoint["department_name"];
					$DeliveryPoint["address_lines"][$NextAddressLine][] = $DeliveryPoint["department_name"];
					$NextAddressLine++;
				}
				
				if(strlen($DeliveryPoint["po_box_number"]) > 0)
				{
					$DeliveryPoint["name_parts"][] = $DeliveryPoint["po_box_number"];
					$DeliveryPoint["address_lines"][$NextAddressLine][] = $DeliveryPoint["po_box_number"];
					$NextAddressLine++;
				}
				
				if(strlen($DeliveryPoint["building_number"]) > 0)
				{
					$DeliveryPoint["name_parts"][] = $DeliveryPoint["building_number"];
					$DeliveryPoint["address_lines"][$NextAddressLine][] = $DeliveryPoint["building_number"];
				}
				
				if(strlen($DeliveryPoint["sub_building_name"]) > 0)
				{
					$DeliveryPoint["name_parts"][] = $DeliveryPoint["sub_building_name"];
					$DeliveryPoint["address_lines"][$NextAddressLine][] = $DeliveryPoint["sub_building_name"];
				}
				
				if(strlen($DeliveryPoint["building_name"]) > 0)
				{
					$DeliveryPoint["name_parts"][] = $DeliveryPoint["building_name"];
					$DeliveryPoint["address_lines"][$NextAddressLine][] = $DeliveryPoint["building_name"];
					$NextAddressLine++;
				}
				
				// And the main parts
				if(strlen($Thoroughfare["thoroughfare_name"]) > 0)
				{
					$DeliveryPoint["name_parts"][] = $Thoroughfare["thoroughfare_name"];
					$DeliveryPoint["address_lines"][$NextAddressLine][] = $Thoroughfare["thoroughfare_name"];
				}
				
				if(strlen($Thoroughfare["thoroughfare_descriptor"]) > 0)
				{
					$DeliveryPoint["name_parts"][] = $Thoroughfare["thoroughfare_descriptor"];
					$DeliveryPoint["address_lines"][$NextAddressLine][] = $Thoroughfare["thoroughfare_descriptor"];
					$NextAddressLine++;
				}
				
				// County / towns.
				if(strlen($Result["dependent_locality"]) > 0)
				{
					$DeliveryPoint["address_lines"]["dependent_locality"][] = $Result["dependent_locality"];
					$NextAddressLine++;
				}
				
				if(strlen($Result["town"]) > 0)
				{
					$DeliveryPoint["address_lines"]["town"][] = $Result["town"];
					$NextAddressLine++;
				}
				
				if(strlen($Result["postal_county"]) > 0)
				{
					$DeliveryPoint["address_lines"]["county"][] = $Result["postal_county"];
					$NextAddressLine++;
				}
				
				if(strlen($Result["postcode"]) > 0)
				{
					$DeliveryPoint["address_lines"]["postcode"][] = $Result["postcode"];
					$NextAddressLine++;
				}

				// Concat them
				$DeliveryPoint["name_parts_concat"] = implode(" ", $DeliveryPoint["name_parts"]);
				
				foreach($DeliveryPoint["address_lines"] as &$LineItems)
				{
					$LineItems = implode(" ", $LineItems);
				}
				
				// Add it in
				$Response[] = $DeliveryPoint;
			}
		}
		
		usort($Response, function($a, $b)
		{
			$this->_Debug($a);
			$this->_Debug($b);
			
			if((strlen($a["building_number"]) > 0) && (strlen($b["building_number"]) > 0))
			{
				if ($a["building_number"] == $b["building_number"])
				{
					if((strlen($a["building_name"]) > 0) && (strlen($b["building_name"]) < 1))
					{
						$this->_Debug("Line: " . __LINE__ . " = " . 1);
						return 1;
					}
					elseif((strlen($a["building_name"]) < 1) && (strlen($b["building_name"]) > 0))
					{
						$this->_Debug("Line: " . __LINE__ . " = " . -1);
						return -1;
					}
					
					$this->_Debug("Line: " . __LINE__ . " = " . 0);
					return 0;
				}
				
				$this->_Debug("Line: " . __LINE__ . " = " . (($a["building_number"] < $b["building_number"]) ? -1 : 1));
				return ($a["building_number"] < $b["building_number"]) ? -1 : 1;
			}
			
			// Get the numerics from the full line address and compare those too.
			preg_match_all('/\d+/', $a["name_parts_concat"], $a_matches);
			preg_match_all('/\d+/', $b["name_parts_concat"], $b_matches);
			$a_numbers = (isset($a_matches[0])) ? $a_matches[0] : null;
			$b_numbers = (isset($b_matches[0])) ? $b_matches[0] : null;
			
			if($a_numbers && $b_numbers)
			{
				if($a_numbers == $b_numbers)
				{
					
					
					if((strlen($a["building_number"]) > 0) && (strlen($b["building_number"]) < 1))
					{
						$this->_Debug("Line: " . __LINE__ . " = " . -1);
						return -1;
					}
					elseif((strlen($a["building_number"]) < 1) && (strlen($b["building_number"]) > 0))
					{
						$this->_Debug("Line: " . __LINE__ . " = " . 1);
						return 1;
					}
					
					$this->_Debug("Line: " . __LINE__ . " = " . 0);
					return 0;
				}
				$this->_Debug("Line: " . __LINE__ . " = " . (($a_numbers < $b_numbers) ? -1 : 1));
				return ($a_numbers < $b_numbers) ? -1 : 1;
			}
			elseif($a_numbers && !$b_numbers)
			{
				$this->_Debug("Line: " . __LINE__ . " = " . -1);
				return -1;
			}
			elseif($b_numbers && !$a_numbers)
			{
				$this->_Debug("Line: " . __LINE__ . " = " . 1);
				return 1;
			}
			
			$this->_Debug("Line: " . __LINE__ . " = " . strcmp($a["name_parts_concat"], $b["name_parts_concat"]));
			return strcmp($a["name_parts_concat"], $b["name_parts_concat"]);
		});
		
		return $Response;
	}
	
	function _Debug($Input)
	{
		if($this->_DebugEnable) echo "<textarea rows=\"20\" cols=\"50\">" . print_r($Input, true) . "</textarea>";
	}
	
	// Full Address (RapidAddress)
	function _c_RapidAddress($Params)
	{
		if(!is_array($Params)) throw new Exception("Invalid params supplied for " . __METHOD__);
		return $this->_ExceuteAPICall("rapidaddress", $Params);
	}
}