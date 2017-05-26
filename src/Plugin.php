<?php

namespace Detain\MyAdminParallels;

//use Detain\Parallels\Parallels;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public function __construct() {
	}

	public static function Activate(GenericEvent $event) {
		// will be executed when the licenses.license event is dispatched
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_PARALLELS) {
			myadmin_log('licenses', 'info', 'Parallels Activation', __LINE__, __FILE__);
			function_requirements('activate_parallels');
			if (trim($event['field2']) != '') {
				$response = activate_parallels($license->get_ip(), $event['field1'], $event['field2']);
			} else {
				$response = activate_parallels($license->get_ip(), $event['field1']);
			}
			myadmin_log('licenses', 'info', 'Response: ' . json_encode($response), __LINE__, __FILE__);
			$license_extra = $response['mainKeyNumber'] . ',' . $response['productKey'];
			$license->set_extra($license_extra)->save();
			$event->stopPropagation();
		}
	}

	public static function ChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			$license = $event->getSubject();
			$settings = get_module_settings('licenses');
			$parallels = new Parallels(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log('licenses', 'info', "IP Change - (OLD:".$license->get_ip().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $parallels->editIp($license->get_ip(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log('licenses', 'error', 'Parallels editIp('.$license->get_ip().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $license->get_ip());
				$license->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function Menu(GenericEvent $event) {
		// will be executed when the licenses.settings event is dispatched
		$menu = $event->getSubject();
		$module = 'licenses';
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link($module, 'choice=none.reusable_parallels', 'icons/database_warning_48.png', 'ReUsable Parallels Licenses');
			$menu->add_link($module, 'choice=none.parallels_list', 'icons/database_warning_48.png', 'Parallels Licenses Breakdown');
			$menu->add_link('licensesapi', 'choice=none.parallels_licenses_list', 'whm/createacct.gif', 'List all Parallels Licenses');
		}
	}

	public static function Requirements(GenericEvent $event) {
		// will be executed when the licenses.loader event is dispatched
		$loader = $event->getSubject();
		$loader->add_requirement('crud_parallels_list', '/../vendor/detain/crud/src/crud/crud_parallels_list.php');
		$loader->add_requirement('crud_reusable_parallels', '/../vendor/detain/crud/src/crud/crud_reusable_parallels.php');
		$loader->add_requirement('get_parallels_licenses', '/licenses/parallels.functions.inc.php');
		$loader->add_requirement('get_parallels_list', '/licenses/parallels.functions.inc.php');
		$loader->add_requirement('parallels_licenses_list', '/licenses/parallels.functions.inc.php');
		$loader->add_requirement('parallels_list', '/licenses/parallels.functions.inc.php');
		$loader->add_requirement('get_available_parallels', '/licenses/parallels.functions.inc.php');
		$loader->add_requirement('activate_parallels', '/licenses/parallels.functions.inc.php');
		$loader->add_requirement('get_reusable_parallels', '/licenses/parallels.functions.inc.php');
		$loader->add_requirement('reusable_parallels', '/licenses/parallels.functions.inc.php');
		$loader->add_requirement('class.parallels', '/../vendor/detain/parallels/class.parallels.inc.php');
		$loader->add_requirement('vps_add_parallels', '/vps/addons/vps_add_parallels.php');
	}

	public static function Settings(GenericEvent $event) {
		// will be executed when the licenses.settings event is dispatched
		$settings = $event->getSubject();
		$settings->add_text_setting('apisettings', 'parallels_ka_client', 'Parallels KA Client:', 'Parallels KA Client', $settings->get_setting('PARALLELS_KA_CLIENT'));
		$settings->add_text_setting('apisettings', 'parallels_ka_login', 'Parallels KA Login:', 'Parallels KA Login', $settings->get_setting('PARALLELS_KA_LOGIN'));
		$settings->add_text_setting('apisettings', 'parallels_ka_password', 'Parallels KA Password:', 'Parallels KA Password', $settings->get_setting('PARALLELS_KA_PASSWORD'));
		$settings->add_text_setting('apisettings', 'parallels_ka_url', 'Parallels KA URL:', 'Parallels KA URL', $settings->get_setting('PARALLELS_KA_URL'));
		$settings->add_dropdown_setting('stock', 'outofstock_licenses_parallels', 'Out Of Stock Parallels Licenses', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LICENSES_FANTASTICO'), array('0', '1'), array('No', 'Yes', ));
	}

}
