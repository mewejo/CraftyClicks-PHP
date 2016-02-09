<?php

require_once("../Base.class.php");

// Init base
$CraftyClicks = new \CraftyClicks\Base("YOUR-TOKEN-HERE");

// Get the raw result from the API
$Response = $CraftyClicks->_c_RapidAddress(array("postcode" => "AA11AA"));

// Debug out the raw API response
$CraftyClicks->_Debug($Response);

// Format it so it's more useful
$Result = $CraftyClicks->_FormatRapidAddressResult($Response);

// Debug out the result
$CraftyClicks->_Debug($Result);

