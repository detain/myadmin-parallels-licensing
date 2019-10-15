<?php
/**
 * Parallels Related Functionality
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2019
 * @package MyAdmin
 * @category Licenses
 */

/**
 * get_parallels_licenses()
 * simple wrapper to get all the parallels licenses.
 *
 * @return array array of licenses. {@link Parallels.getIpListDetailed}
 */

/**
 * activate_parallels()
 * @param mixed  $ipAddress
 * @param mixed  $type
 * @param string $addons
 * @return mixed
 */
function activate_parallels($ipAddress, $type, $addons = '')
{
	function_requirements('class.Parallels');
	myadmin_log('licenses', 'info', "Parallels New License {$ipAddress} Type {$type} Addons {$addons} called", __LINE__, __FILE__);
	ini_set('max_execution_time', 1000); // just put a lot of time
	ini_set('default_socket_timeout', 1000); // same
	$parallels = new \Detain\Parallels\Parallels();
	if (trim($addons) == '') {
		$addonsArray = [];
	} else {
		$addonsArray = explode(',', $addons);
	}

	// check if already active
	myadmin_log('licenses', 'info', 'addonsArray:', __LINE__, __FILE__);
	myadmin_log('licenses', 'info', var_export($addonsArray, true), __LINE__, __FILE__);
	$request = [$type, $addonsArray, $ipAddress];
	$response = $parallels->createKey($type, $addonsArray, $ipAddress);
	request_log('licenses', false, __FUNCTION__, 'parallels', 'createKey', $request, $response);
	myadmin_log('licenses', 'info', "activate Parallels({$ipAddress}, {$type}, {$addons}) Response: ".json_encode($response), __LINE__, __FILE__);
	/* example response:
	Array(
	[mainKeyNumber] => PLSK.00007677.0000
	[expirationDate] => stdClass Object
	(
	[scalar] => 20131211T00:00:00
	[xmlrpc_type] => datetime
	[timestamp] => 1386720000
	)

	[productKey] => A00300-K4KT02-JHE757-B1FE76-JD2N77
	[additionalKeysNumbers] => Array
	(
	)

	[resultCode] => 100
	[resultDesc] => PLSK.00007677.0000 has been successfully created.
	[updateDate] => stdClass Object
	(
	[scalar] => 20131201T00:00:00
	[xmlrpc_type] => datetime
	[timestamp] => 1385856000
	)

	)
	*/
	return $response;
}

/**
* @param $ipAddress
*/
function deactivate_parallels($ipAddress)
{
	myadmin_log('licenses', 'info', "Parallels Deactivation ({$ipAddress})", __LINE__, __FILE__);
	function_requirements('class.Parallels');
	$parallels = new \Detain\Parallels\Parallels();
	$response = $parallels->getKeyNumbers($ipAddress);
	request_log('licenses', false, __FUNCTION__, 'parallels', 'getMainKeyFromIp', $ipAddress, $response);
	myadmin_log('licenses', 'info', "Parallels getMainKeyFromIp({$ipAddress}): ".json_encode($response), __LINE__, __FILE__);
    if (isset($response['keyNumbers'])) {
        $keys = $response['keyNumbers']; 
        $status = json_decode(file_get_contents(__DIR__.'/../../../../include/config/plesk.json'), true);
        foreach ($keys as $key) {
		    $response = $parallels->terminateKey($key);
            request_log('licenses', false, __FUNCTION__, 'parallels', 'terminateKey', $key, $response);
            myadmin_log('licenses', 'info', "Parallels TerminateKey({$key}) Response: ".json_encode($response), __LINE__, __FILE__);
            if (array_key_exists($key, $status)) {
                $status[$key]['terminated'] = true;
                file_put_contents(__DIR__.'/../../../../include/config/plesk.json', json_encode($status, JSON_PRETTY_PRINT));
            }
            if (array_key_exists(str_replace('0001', '0000', $key), $status)) {
                $status[str_replace('0001', '0000', $key)]['terminated'] = true;
                file_put_contents(__DIR__.'/../../../../include/config/plesk.json', json_encode($status, JSON_PRETTY_PRINT));
            }
        }
	} else {
		myadmin_log('licenses', 'info', 'Parallels No Key Found to Terminate', __LINE__, __FILE__);
	}
}
