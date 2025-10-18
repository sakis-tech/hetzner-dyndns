<?php

// Author: sakis-tech
// Version: 2.0.5-hetzner-fritzbox
// Created: 19.07.2025
// License: GNU General Public License v3.0
// Rewrite: by_lexus
//
// Description:
//   This simple PHP script replaces a DynDNS provider and passes your current IP address to Hetzner Cloud DNS.
//   It uses the Hetzner Cloud API under https://api.hetzner.cloud/v1/.
//
// Parameters:
//   - token - Hetzner Cloud API token - required
//   - domains to edit - comma-separated, required, e.g. "mail.alexi.ch, service.my-domain.ch". Note: The TLD is used as Zone name!
//   - ipv4 - optional
//   - ipv6 - optional
//   - log (true/false) - optional (default: false)
//
// Env vars (to prevent parameter injection from outside):
// - HETZNER_DYNDNS_LOGPATH : Path to a directory where logfiles are created
// - HETZNER_CLOUD_API_TOKEN:  Hetzner Cloud API-key, if not provided via URL (for safety reasons)
//   
//   Fritz!Box update URL:
//   https://example.com/hetzner_dyndns.php?token=<pass>&domain=<domain>&ipv4=<ipaddr>&ipv6=<ip6addr>&log=true
//   (Benutzername: dummy, Kennwort: Ihr Hetzner Cloud API Token)

wlog("INFO", "===== Starting Hetzner DNS Script =====");
header("Content-Type: text/plain");

// API Token aus Fritz!Box Kennwort holen
$api_token = getenv('HETZNER_CLOUD_API_TOKEN') ?: ($_GET['token'] ?? null);
if (empty($api_token) || !isset($_GET["domain"]) || empty($_GET["domain"])) {
    wlog("ERROR", "API token or domain parameter missing or invalid");
    wlog("INFO", "Available parameters: " . implode(", ", array_keys($_GET)));
    wlog("INFO", "Script aborted");
    exit_error(400, "API token or domain parameter missing or invalid");
}

wlog("INFO", "Using API token: " . substr($api_token, 0, 8) . "..." . substr($api_token, -4));

if (isset($_GET["ipv4"]) && !empty($_GET["ipv4"]) && filter_var($_GET["ipv4"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) == true) {
    wlog("INFO", "New IPv4: " . $_GET["ipv4"]);
    $ipv4 = $_GET["ipv4"];
} else {
    wlog("INFO", "IPv4 not available or invalid, ignoring");
    $ipv4 = false;
}

if (isset($_GET["ipv6"]) && !empty($_GET["ipv6"]) && filter_var($_GET["ipv6"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) == true) {
    wlog("INFO", "New IPv6: " . $_GET["ipv6"]);
    $ipv6 = $_GET["ipv6"];
} else {
    wlog("INFO", "IPv6 not available or invalid, ignoring");
    $ipv6 = false;
}

if (!$ipv4 && !$ipv6) {
    wlog("ERROR", "Neither IPv4 nor IPv6 available. Probably the parameters are missing in the update URL.");
    wlog("INFO", "Script aborted");
    exit_error(400, "Neither IPv4 nor IPv6 available. Probably the parameters are missing in the update URL.");
}



// Test Hetzner DNS API authentication
$auth_test = hetzner_curl("zones", $api_token);
if (!isset($auth_test["zones"])) {
    wlog("ERROR", "Hetzner DNS authentication failed. Token: " . substr($api_token, 0, 8) . "...");
    if (isset($auth_test["message"])) {
        wlog("ERROR", "API Response: " . $auth_test["message"]);
    }
    wlog("INFO", "Script aborted");
    exit_error(401, "Hetzner DNS authentication failed");
} else {
    wlog("INFO", "Hetzner DNS authentication successful");
}



wlog("INFO", "Found records to set: " . $_GET["domain"]);
$domains = explode(", ", $_GET["domain"]);
$result = "success";


foreach ($domains as $domain) {
    $domain = trim($domain);
    wlog("INFO", "Processing domain: " . $domain);

    // Extract zone name from domain
    $domain_parts = explode(".", $domain);
    $zone_name = implode(".", array_slice($domain_parts, -2)); // Get last two parts (domain.tld)
    $subdomain = implode(".", array_slice($domain_parts, 0, count($domain_parts) - 2)); // Get parts before domain.tld

    // Find zone ID
    $zones = hetzner_curl("zones?name=" . $zone_name, $api_token);
    if (!isset($zones["zones"]) || count($zones["zones"]) == 0) {
        wlog("ERROR", "Could not find zone for domain '" . $domain . "'");
        $result = "failure";
        continue;
    }

    $zone_id = $zones["zones"][0]["id"];
    wlog("INFO", "Found zone ID: " . $zone_id . " for " . $zone_name);

    // -------------- IPv4 ----------------------------
    if (!empty($ipv4)) {
        // Get Resource Record Set (RRSet) for IPv4 entry:
        $res = hetzner_curl("zones/{$zone_id}/rrsets/{$subdomain}/A", $api_token);
        wlog("DEBUG", "Response: " . print_r($res, true));
        if (!isset($res["rrset"]['id'])) {
            wlog("INFO", "Could not find RRSet {$subdomain}:A in zone {$zone_name}, try to create one");
            $res = hetzner_curl(
                "zones/{$zone_id}/rrsets",
                $api_token,
                [
                    'name' => "{$subdomain}",
                    'type' => 'A',
                    'ttl' => 3600,
                    'records' => [
                        [
                            'value' => "{$ipv4}",
                            'comment' => 'Auto-set by ' . basename(__FILE__)
                        ]
                    ]
                ],
                'POST'
            );
            wlog("DEBUG", "Response: " . print_r($res, true));
            if (!isset($res["rrset"]['id'])) {
                wlog("ERROR", "Could not create zone for domain '" . $domain . "'. Error was: " . print_r($res, true));
                $result = "failure";
                continue;
            }
            $rrset4 = $res['rrset'];
            $rrset4Id = $rrset4['id'];
        } else {
            $rrset4 = $res['rrset'];
            $rrset4Id = $rrset4['id'];
            wlog("INFO", "Found RRSet {$rrset4Id} for {$subdomain}:A in zone {$zone_name}.");
        }
        wlog("INFO", "Done with IPv4 entry for {$domain}: " . $rrset4["type"] . " " . $rrset4["name"] . " -> " . $rrset4["records"][0]["value"]);
    }

    // -------------- IPv6 ----------------------------
    if (!empty($ipv6)) {
        // Get Resource Record Set (RRSet) for IPv6 entry:
        $res = hetzner_curl("zones/{$zone_id}/rrsets/{$subdomain}/AAAA", $api_token);
        wlog("DEBUG", "Response: " . print_r($res, true));
        if (!isset($res["rrset"]['id'])) {
            wlog("INFO", "Could not find RRSet {$subdomain}:AAAA in zone {$zone_name}, try to create one");
            $res = hetzner_curl(
                "zones/{$zone_id}/rrsets",
                $api_token,
                [
                    'name' => "{$subdomain}",
                    'type' => 'AAAA',
                    'ttl' => 3600,
                    'records' => [
                        [
                            'value' => "{$ipv6}",
                            'comment' => 'Auto-set by ' . basename(__FILE__)
                        ]
                    ]
                ],
                'POST'
            );
            wlog("DEBUG", "Response: " . print_r($res, true));
            if (!isset($res["rrset"]['id'])) {
                wlog("ERROR", "Could not create zone for domain '" . $domain . "'. Error was: " . print_r($res, true));
                $result = "failure";
                continue;
            }
            $rrset6 = $res['rrset'];
            $rrset6Id = $rrset6['id'];
        } else {
            $rrset6 = $res['rrset'];
            $rrset6Id = $rrset6['id'];
            wlog("INFO", "Found RRSet {$rrset6Id} for {$subdomain}:A in zone {$zone_name}.");
        }
        wlog("INFO", "Done with IPv6 entry for {$domain}: " . $rrset6["type"] . " " . $rrset6["name"] . " -> " . $rrset6["records"][0]["value"]);
    }
}

echo "Result: $result";
wlog("INFO", "===== Script completed =====");
exit();

function hetzner_curl($endpoint, $api_token, $data = null, $method = "GET") {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://api.hetzner.cloud/v1/" . $endpoint);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $api_token,
        'Content-Type: application/json'
    ));

    if (in_array($method, ['POST', 'PUT'])) {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            $json_data = json_encode($data);
            wlog("DEBUG", "JSON POST data: " . $json_data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        }
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 400) {
        wlog("ERROR", "HTTP Error " . $http_code . ": " . $response);
        return json_decode($response, true); // Return error for debugging
    }

    return json_decode($response, true);
}

function wlog($level, $msg) {
    // Logging nur wenn explizit auf "true" gesetzt
    if (!isset($_GET["log"]) || $_GET["log"] !== "true") return;

    $domains = explode(",", $_GET["domain"] ?? '');
    $log_path = getenv('HETZNER_DYNDNS_LOGPATH') ?: ('.' . DIRECTORY_SEPARATOR);
    $log_file = "{$log_path}log-hetzner-" . trim($domains[0]) . ".txt";
    $log_entry = date("Y-m-d H:i:s") . " - " . $level . " - " . $msg . "\n";
    // to file:
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    // and to stdout:
    file_put_contents('php://stdout', $log_entry, FILE_APPEND | LOCK_EX);
}

function exit_error($error_code, $msg) {
    http_response_code($error_code);
    echo $msg;
    exit();
}
