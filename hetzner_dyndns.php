<?php

// Author: sakis-tech
// Version: 2.0.5-hetzner-fritzbox
// Created: 19.07.2025
// License: GNU General Public License v3.0
//
// Description:
//   This simple PHP script replaces a DynDNS provider and passes your current IP address to Hetzner DNS.
//   Optimized for Fritz!Box compatibility using password authentication.
//
// Parameters:
//   - Hetzner DNS API token (as Fritz!Box password) - required
//   - domain(s) (as domain) -> use multiple domains by separating by semicolon (,) - required
//   - ipv4 (is automatically provided by the Fritz!Box) - optional
//   - ipv6 (is automatically provided by the Fritz!Box) - optional
//   - log (true/false) - optional (default: false)
//   
//   Fritz!Box update URL:
//   https://example.com/hetzner_dyndns.php?pass=<pass>&domain=<domain>&ipv4=<ipaddr>&ipv6=<ip6addr>&log=true
//   (Benutzername: dummy, Kennwort: Ihr Hetzner DNS API Token)

wlog("INFO", "===== Starting Hetzner DNS Script =====");
header("Content-Type: text/plain");

// API Token aus Fritz!Box Kennwort holen
if (!isset($_GET["pass"]) || empty($_GET["pass"]) || !isset($_GET["domain"]) || empty($_GET["domain"])) {
    wlog("ERROR", "API token (pass) or domain parameter missing or invalid");
    wlog("INFO", "Available parameters: " . implode(", ", array_keys($_GET)));
    wlog("INFO", "Script aborted");
    exit_error(400, "API token (pass) or domain parameter missing or invalid");
}

$api_token = $_GET["pass"];
wlog("INFO", "Using API token from Fritz!Box: " . substr($api_token, 0, 8) . "..." . substr($api_token, -4));

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
$domains = explode(",", $_GET["domain"]);
$result = "success";

foreach ($domains as $domain) {
    $domain = trim($domain);
    wlog("INFO", "Processing domain: " . $domain);
    
    // Extract zone name from domain
    $domain_parts = explode(".", $domain);
    $zone_name = implode(".", array_slice($domain_parts, -2)); // Get last two parts (domain.tld)
    
    // Find zone ID
    $zones = hetzner_curl("zones?name=" . $zone_name, $api_token);
    if (!isset($zones["zones"]) || count($zones["zones"]) == 0) {
        wlog("ERROR", "Could not find zone for domain '" . $domain . "'");
        $result = "failure";
        continue;
    }
    
    $zone_id = $zones["zones"][0]["id"];
    wlog("INFO", "Found zone ID: " . $zone_id . " for " . $zone_name);
    
    // Get existing records for this domain
    $records = hetzner_curl("records?zone_id=" . $zone_id, $api_token);
    if (!isset($records["records"])) {
        wlog("ERROR", "Could not get records for zone " . $zone_id);
        $result = "failure";
        continue;
    }
    
    $found_a_record = false;
    $found_aaaa_record = false;
    
    // Check existing records
    foreach ($records["records"] as $record) {
        if ($record["name"] == $domain) {
            wlog("INFO", "Found existing record: " . $record["type"] . " " . $record["name"] . " -> " . $record["value"]);
            
            if ($ipv4 && $record["type"] == "A") {
                $found_a_record = true;
                if ($record["value"] == $ipv4) {
                    wlog("INFO", "IPv4 record already up-to-date");
                } else {
                    // Update A record
                    $update_data = array(
                        "value" => $ipv4,
                        "ttl" => 60
                    );
                    $response = hetzner_curl("records/" . $record["id"], $api_token, $update_data, "PUT");
                    if (isset($response["record"])) {
                        wlog("INFO", "Updated A record successfully: " . $ipv4);
                    } else {
                        wlog("ERROR", "Failed to update A record");
                        $result = "failure";
                    }
                }
            }
            
            if ($ipv6 && $record["type"] == "AAAA") {
                $found_aaaa_record = true;
                if ($record["value"] == $ipv6) {
                    wlog("INFO", "IPv6 record already up-to-date");
                } else {
                    // Update AAAA record
                    $update_data = array(
                        "value" => $ipv6,
                        "ttl" => 60
                    );
                    $response = hetzner_curl("records/" . $record["id"], $api_token, $update_data, "PUT");
                    if (isset($response["record"])) {
                        wlog("INFO", "Updated AAAA record successfully: " . $ipv6);
                    } else {
                        wlog("ERROR", "Failed to update AAAA record");
                        $result = "failure";
                    }
                }
            }
        }
    }
    
    // Create new records if they don't exist
    if ($ipv4 && !$found_a_record) {
        wlog("INFO", "Creating new A record");
        $create_data = array(
            "type" => "A",
            "name" => $domain,
            "value" => $ipv4,
            "ttl" => 60,
            "zone_id" => $zone_id
        );
        $response = hetzner_curl("records", $api_token, $create_data, "POST");
        if (isset($response["record"])) {
            wlog("INFO", "Created A record successfully: " . $ipv4);
        } else {
            wlog("ERROR", "Failed to create A record");
            $result = "failure";
        }
    }
    
    if ($ipv6 && !$found_aaaa_record) {
        wlog("INFO", "Creating new AAAA record");
        $create_data = array(
            "type" => "AAAA",
            "name" => $domain,
            "value" => $ipv6,
            "ttl" => 60,
            "zone_id" => $zone_id
        );
        $response = hetzner_curl("records", $api_token, $create_data, "POST");
        if (isset($response["record"])) {
            wlog("INFO", "Created AAAA record successfully: " . $ipv6);
        } else {
            wlog("ERROR", "Failed to create AAAA record");
            $result = "failure";
        }
    }
}

echo "Result: $result";
wlog("INFO", "===== Script completed =====");
exit();

function hetzner_curl($endpoint, $api_token, $data = null, $method = "GET") {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, "https://dns.hetzner.com/api/v1/" . $endpoint);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Auth-API-Token: ' . $api_token,
        'Content-Type: application/json'
    ));
    
    if ($method == "POST") {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method == "PUT") {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
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
    
    $domains = explode(",", $_GET["domain"]);
    $log_file = "log-hetzner-" . trim($domains[0]) . ".txt";
    $log_entry = date("Y-m-d H:i:s") . " - " . $level . " - " . $msg . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

function exit_error($error_code, $msg) {
    http_response_code($error_code);
    echo $msg;
    exit();
}

?>
