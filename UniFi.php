<?php

// load the class using the composer autoloader
require_once('src/Client.php');
date_default_timezone_set('Europe/Berlin');

// initialize the Unifi API connection class, log in to the controller
$unifi_connection = new UniFi_API\Client('USER', 'PW', 'https://IP_OF_UNIFI_CONTROLLER:8443', 'default', '5.9.29');
$set_debug_mode   = $unifi_connection->set_debug($debug);
$loginresults     = $unifi_connection->login();
$aps_array        = $unifi_connection->list_aps();

// output the results into different files
// nodelist.json
// meshviewer.json

$nodelist = "";
$meshview = "";
$model    = "";
$link     = array();
$links    = array();
$radio_stats = new stdClass();
$radio_stat0 = new stdClass();
$radio_stat1 = new stdClass();

foreach ($aps_array as $ap) {
		if ($ap->type === 'uap') {
			// well-known name
			switch ($ap->model) {
				case "U7MSH":
					$model = "UAP-AC-M";
					break;
				case "U7MP":
					$model = "UAP-AC-M-Pro";
					break;
				case "U7PG2":
					$model = "UAP-AC-Pro";
					break;
				case "U7LT":
					$model = "UAP-AC-Lite";
					break;
				default:
			$model = $ap->model;
			}
			// nodelist.json
			$nodelist .= "{\"id\":\"" .strtolower($ap->serial). "\",";
			$nodelist .= "\"name\":\"" .$ap->name. "\",";
			$nodelist .= "\"position\":{";
			$nodelist .= "\"lat\":" .$ap->x. ",";
			$nodelist .= "\"long\":" .$ap->y. "},";
			$nodelist .= "\"status\":{";
			if ($ap->state == 1) {
				$nodelist .= "\"online\":true,";
			} else {
				$nodelist .= "\"online\":false,";
			}
			$nodelist .= "\"lastcontact\":\"" . date(DATE_ISO8601,$ap->last_seen) . "\",";
			$nodelist .= "\"clients\":" . $ap->num_sta . "}},";

			// meshviewer.json
			$stats = $ap->stat;
			$meshview .= "{\"firstseen\":\"" . $stats->datetime . "\",";
			$meshview .= "\"lastseen\":\"" . date(DATE_ISO8601,$ap->last_seen) . "\",";
			if ($ap->state == 1) {
				$meshview .= "\"is_online\":true,";
			} else {
				$meshview .= "\"is_online\":false,";
			}
			$meshview .= "\"is_gateway\":false,";
			if ($ap->state == 1) {
				$meshview .= "\"clients\":" . $ap->num_sta . ",";
				$radio_stats = (array) $ap->radio_table_stats;
				$radio_stat0 = $radio_stats[0];
				$radio_stat1 = $radio_stats[1];
				$meshview .= "\"clients_wifi24\":" . $radio_stat0->num_sta . ",";
				$meshview .= "\"clients_wifi5\":" . $radio_stat1->num_sta . ",";
				$meshview .= "\"clients_other\":0,";
				$stats = $ap->sys_stats;
				$meshview .= "\"loadavg\":" . $stats->loadavg_1 . ",";
				$meshview .= "\"memory_usage\":" . $stats->mem_used/$stats->mem_total . ",";
				$meshview .= "\"uptime\":\"" . date(DATE_ISO8601,time()-$ap->uptime) . "\",";
				// links
				$link[$ap->serial]["type"] = "other";
				$link[$ap->serial]["source"] = strtolower($ap->serial);
				$link[$ap->serial]["target"] = "ID_OF_GATEWAY";
				$link[$ap->serial]["source_tq"] = 1;
				$link[$ap->serial]["target_tq"] = 1;
				$link[$ap->serial]["source_addr"] = $ap->mac;
				$link[$ap->serial]["target_addr"] = "MAC_OF_GATEWAY";
			} else {
				$meshview .= "\"clients\":0,";
				$meshview .= "\"clients_wifi24\":0,";
				$meshview .= "\"clients_wifi5\":0,";
				$meshview .= "\"clients_other\":0,";
				$meshview .= "\"loadavg\":0,";
				$meshview .= "\"memory_usage\":0,";
				$meshview .= "\"uptime\":\"" . date(DATE_ISO8601,0) . "\",";
			}
			$meshview .= "\"gateway_nexthop\":\"ID_OF_GATEWAY\",";
			$meshview .= "\"gateway\":\"ID_OF_GATEWAY\",";
			$meshview .= "\"node_id\":\"" . strtolower($ap->serial) . "\",";
			$meshview .= "\"mac\":\"" . $ap->mac . "\",";
			$meshview .= "\"addresses\":[\"" . $ap->ip . "\"],";
			$meshview .= "\"site_code\":\"ffhgw\",";
			$meshview .= "\"hostname\":\"" . $ap->name . "\",";
			$meshview .= "\"owner\":\"support@greifswald.freifunk.net\",";
			$meshview .= "\"location\":{";
			$meshview .= "\"longitude\":" .$ap->y. ",";
			$meshview .= "\"latitude\":" .$ap->x. "},";
			$meshview .= "\"firmware\":{\"base\":\"Ubiquiti Networks\",";
			$meshview .= "\"release\":\"" . $ap->version . "\"},";

			$meshview .= "\"autoupdater\":{\"enabled\":false,";
			$meshview .= "\"release\":\"stable\"},";
			$meshview .= "\"nproc\":1,";
			$meshview .= "\"model\":\"" . $model . "\",";
			$meshview .= "\"vpn\":false},";
		}
}
// build linklist
foreach ($link as $li) {
  array_push($links,json_encode($li));
}
unset($link);

// add gateway information
$nodelist .= "{\"id\": \"00163e2b63ba\",\"name\": \"NAME_OF_GATEWAY\",\"status\":{\"online\":true,\"lastcontact\":\"" . date(DATE_ISO8601) . "\",\"clients\":0}}";
$nodelist .= "]}";
// add gateway information
$meshview .= "{\"firstseen\":\"2018-09-16T22:10:30+0200\",";
$meshview .= "\"lastseen\":\""  .date(DATE_ISO8601) . "\",";
$meshview .= "\"is_online\":true,";
$meshview .= "\"is_gateway\":true,";
$meshview .= "\"clients\":0,";
$meshview .= "\"clients_wifi24\":0,";
$meshview .= "\"clients_wifi5\":0,";
$meshview .= "\"clients_other\":0,";
$meshview .= "\"rootfs_usage\":0,";
$load = sys_getloadavg();
$meshview .= "\"loadavg\":" . $load[0] . ",";
$meshview .= "\"memory_usage\":0,";
$uptime = system("cat /proc/uptime");
$uptime = date(DATE_ISO8601,time() - $uptime);
$meshview .= "\"uptime\":\"". $uptime ."\",";
// needs some individual specifications
$meshview .= "\"node_id\":\"ID_OF_GATEWAY\",";
$meshview .= "\"mac\":\"MAC_OF_GATEWAY\",";
$meshview .= "\"addresses\":[\"IP_OF_GATEWAY\"],";
$meshview .= "\"hostname\":\"NAME_OF_GATEWAY\",";
$meshview .= "\"firmware\":{\"base\":\"BASE\",\"release\":\"RELEASE\"},";
$meshview .= "\"autoupdater\":{\"enabled\":false},";
$meshview .= "\"nproc\":2,";
$meshview .= "\"vpn\":true";
// add link-list
$meshview .= "}],\"links\":[";
$meshview .= implode(",",$links);
$meshview .= "]}";
// current date and time
$nodelist = "{\"version\":\"1.0.1\",\"updated_at\":\"" . date(DATE_ISO8601) . "\",\"nodes\":[" . $nodelist;
$meshview = "{\"timestamp\":\"" . date(DATE_ISO8601) . "\",\"nodes\":[" . $meshview;
// write files
if ($loginresults) {
	$file = "/var/www/unifi/data/nodelist.json";
	file_put_contents($file,$nodelist);

	$file = "/var/www/unifi/data/meshviewer.json";
	file_put_contents($file,$meshview);
}

?>
