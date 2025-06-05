<?php

namespace EmailValidation\Classes;

if (!defined('ABSPATH')) exit;  // if direct access


class EmailVerifier
{



    /**
     * Check if an email address has a valid syntax.
     *
     * @param string $email The email address to validate.
     * @return bool True if the email syntax is valid, false otherwise.
     */
    public static function isSyntaxValid(string $email): bool
    {
        // Regular expression for validating email syntax with additional restrictions
        $pattern = "/^(?![._%-])[A-Za-z0-9._%+-]+@(?!.*\.\.)([A-Za-z0-9.-]+\.[A-Za-z]{2,})$/";

        // Check syntax validity
        if (!preg_match($pattern, $email)) {
            return false;
        }

        // Additional filters for invalid formats
        $invalidPatterns = [
            '/^\d/',               // Starts with a number
            '/^\./',               // Starts with a dot
            '/^_/',                // Starts with an underscore
            '/[%]/',               // Contains `%` in the local part
        ];

        foreach ($invalidPatterns as $invalidPattern) {
            if (preg_match($invalidPattern, strtok($email, "@"))) {
                return false;
            }
        }



        return true;
    }




    /**
     * Check if an email address is valid.
     *
     * @param string $email The email address to validate.
     * @return bool True if the email is valid, false otherwise.
     */
    public static function isValidEmail(string $email): bool
    {
        // Validate email format
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }


    /**
     * Check if an email is role-based.
     *
     * @param string $email The email address to check.
     * @return bool True if the email is role-based, false otherwise.
     */
    public static function isRoleBasedEmail(string $email): bool
    {

        $roleBasedPrefixes = [
            // Administrative & IT
            'admin',
            'administrator',
            'root',
            'sysadmin',
            'webmaster',
            'postmaster',
            'hostmaster',
            'server',
            'dns',
            'api',
            'cloud',
            'network',
            'tech',
            'technology',
            'support',
            'monitoring',
            'security',
            'firewall',
            'malware',
            'hacker',
            'privacy',
            'abuse',
            'spam',

            // Customer Support & Helpdesk
            'support',
            'help',
            'helpdesk',
            'servicedesk',
            'troubleshooting',
            'fix',
            'techsupport',
            'solutions',
            'customerservice',
            'clientcare',
            'repair',
            'maintenance',
            'case',
            'ticket',
            'resolution',
            'response',
            'feedback',

            // Sales & Marketing
            'sales',
            'marketing',
            'advertising',
            'media',
            'press',
            'branding',
            'publicrelations',
            'sponsorship',
            'promotions',
            'affiliates',
            'influencers',
            'campaigns',
            'outreach',
            'referrals',
            'discounts',
            'coupons',
            'deals',
            'offers',
            'leads',
            'b2b',
            'b2c',
            'wholesale',
            'partner',
            'partnerships',
            'reseller',
            'business',
            'growth',
            'marketplace',

            // Finance, Accounting & Billing
            'billing',
            'payments',
            'invoice',
            'invoices',
            'accounting',
            'finance',
            'receipts',
            'refund',
            'payroll',
            'tax',
            'money',
            'salary',
            'purchases',
            'subscriptions',

            // HR, Jobs & Recruitment
            'hr',
            'humanresources',
            'careers',
            'jobs',
            'recruitment',
            'recruiter',
            'hiring',
            'intern',
            'talent',
            'apply',
            'hireme',
            'training',
            'learning',
            'onboarding',

            // Legal & Compliance
            'legal',
            'compliance',
            'gdpr',
            'privacy',
            'terms',
            'conditions',
            'policies',
            'security',
            'dataprotection',
            'dmca',
            'copyright',
            'dispute',
            'violations',

            // Leadership & Executive Teams
            'ceo',
            'founder',
            'president',
            'coo',
            'cfo',
            'cmo',
            'cio',
            'cto',
            'vp',
            'vicepresident',
            'managingdirector',
            'director',
            'leader',
            'teamlead',
            'board',
            'boardmembers',
            'executives',
            'chairman',
            'owner',
            'management',

            // General Contact & Miscellaneous
            'contact',
            'info',
            'hello',
            'hi',
            'welcome',
            'mail',
            'office',
            'team',
            'staff',
            'group',
            'everyone',
            'newsletter',
            'subscribe',
            'unsubscribe',
            'notifications',
            'alerts',
            'announcements',
            'test',
            'noreply',
            'nobody',
            'anonymous',
            'robot',
            'no-reply',
            'testaccount',
            'supportteam',
            'services',
            'enquiries',
            'queries',
            'query',
            'general',
            'generalinfo'
        ];

        // Extract username from email
        $username = explode('@', $email)[0];

        // Check if username matches a known role-based address
        return in_array(strtolower($username), $roleBasedPrefixes);
    }

    /**
     * Check if the domain of the email address has valid DNS records.
     *
     * @param string $email The email address to check.
     * @return bool True if the domain has valid DNS records, false otherwise.
     */
    public static function hasValidDomain(string $email): bool
    {
        $domain = substr(strrchr($email, "@"), 1);

        if (empty($domain)) return false;

        return checkdnsrr($domain, "MX") || checkdnsrr($domain, "A");
    }

    /**
     * Check if the email domain is a known disposable domain.
     *
     * @param string $email The email address to check.
     * @return bool True if the domain is disposable, false otherwise.
     */
    public static function isDisposableDomain(string $email): bool
    {

        $blocklist_path = email_verify_plugin_dir . 'assets/disposable_email_blocklist.conf';
        $disposable_domains = file($blocklist_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        // $domain = mb_strtolower(explode('@', trim($email))[1]);
        $domain = substr(strrchr($email, "@"), 1);
        return in_array($domain, $disposable_domains);


        // $domain = substr(strrchr($email, "@"), 1);
        // return in_array($domain, self::$disposableDomains);
    }

    /**
     * Check if the email domain is a known free email provider.
     *
     * @param string $email The email address to check.
     * @return bool True if the domain is a free email provider, false otherwise.
     */
    public static function isFreeEmailProvider(string $email): bool
    {

        $providerslist_path = email_verify_plugin_dir . 'assets/free_email_providers.conf';
        $disposable_domains = file($providerslist_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        // $domain = mb_strtolower(explode('@', trim($email))[1]);
        $domain = substr(strrchr($email, "@"), 1);
        return in_array($domain, $disposable_domains);
    }



    /**
     * Detect if the email address is gibberish.
     * Uses a simple heuristic based on character patterns.
     *
     * @param string $email The email address to check.
     * @return bool True if the email is gibberish, false otherwise.
     */
    // public static function isGibberishEmail(string $email): bool
    // {
    //     $localPart = strtok($email, "@"); // Extract local part

    //     // Check if the local part contains mostly random characters
    //     $gibberishPattern = "/^[bcdfghjklmnpqrstvwxyz]{4,}$/i"; // Example heuristic
    //     return preg_match($gibberishPattern, $localPart) === 1;
    // }



    public static function isGibberishEmail($email)
    {


        // Step 1: Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Step 2: Extract the local part (before @)
        $localPart = explode('@', $email)[0];

        // Step 3: Check for gibberish patterns
        // Example: Check if the local part is too short or contains repetitive characters
        if (strlen($localPart) < 5) {
            // return "Likely gibberish: Local part is too short.";

            return true;
        }

        // Check if it contains too many repeating characters or random-looking patterns
        if (preg_match('/^(?:[a-zA-Z]{2,3}\d+|[a-zA-Z\d]{12,})$/', $localPart)) {

            return true; // Likely gibberish
        }


        // Check for repetitive characters (e.g., "aaaaa")
        if (preg_match('/(.)\1{3,}/', $localPart)) { // 4 or more repeating characters
            // return "Likely gibberish: Repetitive characters detected.";
            return true;
        }
        // Step 4: Check for random sequences (e.g., "dfadasdadaqwer")
        // A simple way to detect randomness is to check for lack of vowels or meaningful patterns
        if (!preg_match('/[aeiouAEIOU]{1,}/', $localPart)) { // No vowels detected
            // return "Likely gibberish: No vowels detected.";

            return true;
        }


        // Check for random sequences (e.g., "dfadasdadaqwer")
        // This is a simple example; you can expand this logic
        if (!preg_match('/[a-zA-Z]{2,}/', $localPart)) { // At least 2 consecutive letters
            // return "Likely gibberish: Random sequence detected.";

            return true;
        }

        // Check for excessive consonants (e.g., dfghjklmnp)
        if (preg_match('/[bcdfghjklmnpqrstvwxyz]{5,}/i', $localPart)) {

            return true; // Too many consonants in a row, likely gibberish
        }

        // If none of the above, assume it's not gibberish
        // return "Email does not appear to be gibberish.";

        return false;
    }

    /*
    =====================================================

    This method will check for DNS and MX record of the
    email address domain.

    =====================================================

    @return array with details
     */
    public function checkMxAndDnsRecord($email)
    {


        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Get the domain of the email recipient
            $detailsDesc = '';
            $email_arr = explode('@', $email);
            $domain = array_slice($email_arr, -1);
            $domain = $domain[0];

            // Trim [ and ] from beginning and end of domain string, respectively
            $domain = ltrim($domain, '[');
            $domain = rtrim($domain, ']');
            if ('IPv6:' == substr($domain, 0, strlen('IPv6:'))) {
                $domain = substr($domain, strlen('IPv6') + 1);
            }
            $mxhosts = array();

            // Check if the domain has an IP address assigned to it
            if (filter_var($domain, FILTER_VALIDATE_IP)) {
                $mx_ip = $domain;
            } else {
                // If no IP assigned, get the MX records for the host name
                getmxrr($domain, $mxhosts, $mxweight);
            }
            if (!empty($mxhosts)) {
                $mx_ip = $mxhosts[array_search(min($mxweight), $mxhosts)];
            } else {
                // If MX records not found, get the A DNS records for the host
                if (filter_var($domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $record_a = dns_get_record($domain, DNS_A);
                    // else get the AAAA IPv6 address record
                } elseif (filter_var($domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $record_a = dns_get_record($domain, DNS_AAAA);
                }
                if (!empty($record_a)) {
                    $mx_ip = $record_a[0]['ip'];
                } else {
                    // Exit the program if no MX records are found for the domain host
                    $result = 'invalid';
                    $details = 'No suitable MX records found.';
                    return array($result, $details);
                }
            }
            // Open a socket connection with the hostname, smtp port 25
            try {
                if ($connect = @fsockopen($mx_ip, 25, $errno, $errstr, 5)) {
                    // Initiate the Mail Sending SMTP transaction
                    if (preg_match('/^220/i', $out = fgets($connect, 1024))) {
                        // Send the HELO command to the SMTP server
                        fputs($connect, "HELO $mx_ip\r\n");
                        $out = fgets($connect, 1024);
                        $detailsDesc .= $out . "\n";
                        // Send an SMTP Mail command from the sender's email address
                        fputs($connect, "MAIL FROM: <" . $email . ">\r\n");
                        $from = fgets($connect, 1024);
                        $detailsDesc .= $from . "\n";
                        // Send the SCPT command with the recepient's email address
                        fputs($connect, "RCPT TO: <$email>\r\n");
                        $to = fgets($connect, 1024);
                        $detailsDesc .= $to . "\n";
                        // Close the socket connection with QUIT command to the SMTP server
                        fputs($connect, 'QUIT');
                        fclose($connect);
                        // The expected response is 250 if the email is valid
                        if (!preg_match('/^250/i', $from) || !preg_match('/^250/i', $to)) {
                            $result = 'invalid';
                            $details = 'Invalid email address';
                        } else {
                            $result = 'valid';
                            $details = 'Valid email address';
                        }
                    } else {
                        $result = 'valid';
                        $details = 'MX record found but could not connect to server';
                    }
                } else {
                    $result = 'valid';
                    $details = 'MX record found but could not connect to server';
                }
            } catch (Exception $e) {
                $result = 'valid';
                $details = 'MX record found but could not connect to server';
            }
            return array($result, $details);
        } else {
            $result = 'invalid';
            $details = 'Validation error email address.';
            return array($result, $details);
        }
    }

    /**
     * Perform SMTP verification to check if the email exists.
     * WARNING: This can sometimes be flagged as spammy or blocked by some servers.
     *
     * @param string $email The email address to verify.
     * @return bool True if the email exists, false otherwise.
     */
    public static function verifySMTP(string $email): bool
    {
        $domain = substr(strrchr($email, "@"), 1);

        // Get MX records for the domain
        $mxRecords = [];
        if (!getmxrr($domain, $mxRecords)) {
            return false;
        }


        // Connect to the first MX server
        $mxHost = $mxRecords[0];
        $connection = @fsockopen($mxHost, 25, $errno, $errstr, 10);

        if (!$connection) {
            return false;
        }

        // Perform SMTP conversation
        $response = fgets($connection, 1024);
        fputs($connection, "HELO localhost\r\n");
        fgets($connection, 1024);
        fputs($connection, "MAIL FROM: <verify@localhost>\r\n");
        fgets($connection, 1024);
        fputs($connection, "RCPT TO: <$email>\r\n");
        $response = fgets($connection, 1024);
        fputs($connection, "QUIT\r\n");
        fclose($connection);

        error_log("verifySMTP");
        error_log(serialize($response));

        // Check the response code
        return strpos($response, '250') === 0;
    }
    /**
     * Perform SMTP verification to check if the email exists.
     * WARNING: This can sometimes be flagged as spammy or blocked by some servers.
     *
     * @param string $email The email address to verify.
     * @return bool True if the email exists, false otherwise.
     */
    public static function getMxRecords(string $email)
    {
        $domain = substr(strrchr($email, "@"), 1);

        // Get MX records for the domain
        $mxRecords = [];
        if (!getmxrr($domain, $mxRecords)) {
            return false;
        }



        return $mxRecords;
    }












    /**
     * Check if the SMTP server for the email domain is blacklisted.
     *
     * @param string $email The email address to check.
     * @return bool True if the SMTP server is blacklisted, false otherwise.
     */
    public static function isSMTPBlacklisted(string $email): bool
    {
        $domain = substr(strrchr($email, "@"), 1);

        // Get MX records for the domain
        $mxRecords = [];
        if (!getmxrr($domain, $mxRecords)) {
            return false;
        }

        // Resolve the IP address of the first MX server
        $mxHost = $mxRecords[0];
        $ip = gethostbyname($mxHost);
        if ($ip === $mxHost) {
            // Could not resolve IP
            return false;
        }

        // List of public DNSBLs to query
        $dnsbls = [
            "zen.spamhaus.org",
            "bl.spamcop.net",
            "b.barracudacentral.org",
            "dnsbl.sorbs.net",
            "psbl.surriel.com",
        ];

        // Reverse the IP address for DNSBL query
        $reversedIp = implode(".", array_reverse(explode(".", $ip)));

        // Check each DNSBL
        foreach ($dnsbls as $dnsbl) {
            $query = "$reversedIp.$dnsbl";
            if (checkdnsrr($query, "A")) {
                return true; // Blacklisted
            }
        }

        return false; // Not blacklisted
    }


    /**
     * Check if the domain is a catch-all domain.
     * A catch-all domain accepts emails for any address.
     *
     * @param string $email The email address to check.
     * @return bool True if the domain is a catch-all domain, false otherwise.
     */
    public static function isCatchAllDomain(string $email): bool
    {
        $domain = substr(strrchr($email, "@"), 1);
        error_log("isCatchAllDomain");

        // Get MX records for the domain
        $mxRecords = [];
        if (!getmxrr($domain, $mxRecords)) {
            return false;
        }

        // Connect to the first MX server
        $mxHost = $mxRecords[0];

        error_log("mxHost: $mxHost");


        $connection = @fsockopen($mxHost, 25, $errno, $errstr, 60);

        error_log(serialize($connection));


        if (!$connection) {
            return false;
        }

        // Perform SMTP conversation
        fputs($connection, "HELO localhost\r\n");
        fgets($connection, 1024);
        fputs($connection, "MAIL FROM: <verify@localhost>\r\n");
        fgets($connection, 1024);
        fputs($connection, "RCPT TO: <randomaddress@$domain>\r\n");
        $response = fgets($connection, 1024);
        fputs($connection, "QUIT\r\n");
        fclose($connection);

        error_log(serialize($response));
        // Check if the server responds with 250 (accepts any email address)
        return strpos($response, '250') === 0;
    }

    /**
     * Check if the domain age is old enough.
     *
     * @param string $email The email address to check.
     * @return bool True if the domain age is acceptable, false otherwise.
     */
    public static function isDomainOldEnough(string $email): bool
    {
        $domain = substr(strrchr($email, "@"), 1);

        $whoisData = shell_exec("whois $domain");

        error_log(serialize($whoisData));
        // Use `whois` to get domain registration info (requires `php-whois` or similar package)
        $whoisData = shell_exec("whois " . escapeshellarg($domain));
        error_log("whoisData: $whoisData");


        if (!$whoisData) {
            return false;
        }



        // Extract creation date
        if (preg_match('/Creation Date: (\d{4}-\d{2}-\d{2})/', $whoisData, $matches)) {
            $creationDate = new DateTime($matches[1]);
            $currentDate = new DateTime();

            // Calculate the difference in days
            $diff = $currentDate->diff($creationDate);


            return $diff->y >= 1; // Domain should be at least 1 year old
        }

        return false;
    }



    /**
     * Check the reputation of an email domain.
     * This function can use third-party APIs or basic heuristics for checking domain reputation.
     *
     * @param string $email The email address to check.
     * @return bool True if the domain reputation is good, false otherwise.
     */
    public static function checkDomainReputation(string $email): bool
    {
        $domain = substr(strrchr($email, "@"), 1);

        // Example heuristic: List of known bad domains
        $badDomains = [
            'spammer.com',
            'fakeemails.com',
            'maliciousdomain.com',
            // Add more known bad domains here or fetch dynamically.
        ];

        if (in_array($domain, $badDomains)) {
            return false; // Domain has a bad reputation
        }

        // Optionally integrate with a third-party API
        // Example: Using a hypothetical API to fetch domain reputation
        /*
        $apiUrl = "https://domainreputationapi.com/check?domain=" . urlencode($domain);
        $response = file_get_contents($apiUrl);
        $data = json_decode($response, true);

        if (isset($data['reputation']) && $data['reputation'] === 'bad') {
            return false; // Bad reputation from the API
        }
        */

        return true; // Assume good reputation if not in the bad list
    }

    /**
     * Check if the email inbox is full by analyzing SMTP response.
     *
     * @param string $email The email address to check.
     * @return bool True if the inbox is full, false otherwise.
     */
    public static function isInboxFull(string $email): bool
    {
        $domain = substr(strrchr($email, "@"), 1);

        // Get MX records for the domain
        $mxRecords = [];
        if (!getmxrr($domain, $mxRecords)) {
            return false;
        }

        // Connect to the first MX server
        $mxHost = $mxRecords[0];
        $connection = @fsockopen($mxHost, 25, $errno, $errstr, 10);

        if (!$connection) {
            return false;
        }

        // Perform SMTP conversation
        fputs($connection, "HELO localhost\r\n");
        fgets($connection, 1024);
        fputs($connection, "MAIL FROM: <verify@localhost>\r\n");
        fgets($connection, 1024);
        fputs($connection, "RCPT TO: <$email>\r\n");
        $response = fgets($connection, 1024);
        fputs($connection, "QUIT\r\n");
        fclose($connection);

        // Check if the response contains a specific code indicating inbox full (e.g., 552)
        return strpos($response, '552') !== false;
    }
    /**
     * Check if the email inbox is full by analyzing SMTP response.
     *
     * @param string $email The email address to check.
     * @return bool True if the inbox is full, false otherwise.
     */
    public static function emailParts(string $email)
    {



        $parts = explode('@', $email);
        $username = isset($parts[0]) ? $parts[0] : '';
        $domain = isset($parts[1]) ? $parts[1] : '';

        $response = [];
        $response['domain'] = $domain;
        $response['username'] = $username;



        // Check if the response contains a specific code indicating inbox full (e.g., 552)
        return $response;
    }




    /**
     * Full email verification: syntax, format, domain, and optionally SMTP.
     *
     * @param string $email The email address to verify.
     * @param string $testType Whether to perform full or short test
     * @return array True if the email is valid and exists, false otherwise.
     */
    public static function verifyEmail(string $email,  $testType = "full")
    {


        $response = [];
        $status = [];


        if (empty($email)) {

            $status[] = "empty";
            $response["status"] = $status;

            return $response;
        }


        $isSyntaxValid = self::isSyntaxValid($email);
        $isValidEmail = self::isValidEmail($email);
        $hasValidDomain = self::hasValidDomain($email);
        $isDisposableDomain = self::isDisposableDomain($email);
        $emailParts = self::emailParts($email);

        $domain = isset($emailParts['domain']) ? $emailParts['domain'] : '';
        $username = isset($emailParts['username']) ? $emailParts['username'] : '';

        if ($isDisposableDomain || !$hasValidDomain || !$isValidEmail || !$isSyntaxValid) {


            if ($isDisposableDomain) {
                $status[] = "disposable";
            }
            if (!$isValidEmail) {
                $status[] = "invalidEmail";
            }
            if (!$hasValidDomain) {
                $status[] = "invalidDomain";
            }
            if (!$isSyntaxValid) {
                $status[] = "syntaxNotValid";
            }



            $response["status"] = $status;
            $response["domain"] = $domain;
            $response["username"] = $username;
            $response["safeToSend"] = 'no';
            // $response["isSyntaxValid"] = $isSyntaxValid;
            // $response["isValidEmail"] = $isValidEmail;
            // $response["hasValidDomain"] = $hasValidDomain;
            // $response["isDisposableDomain"] = $isDisposableDomain;

            $response["isSyntaxValid"] = $isSyntaxValid ? 'yes' : 'no';
            $response["isValidEmail"] = $isValidEmail ? 'yes' : 'no';
            $response["hasValidDomain"] = $hasValidDomain ? 'yes' : 'no';
            $response["isDisposableDomain"] = $isDisposableDomain ? 'yes' : 'no';


            // $response["isFreeEmailProvider"] = '';
            // $response["isInboxFull"] = '';
            // $response["isGibberishEmail"] = '';
            // $response["checkDomainReputation"] = '';
            // $response["isSMTPBlacklisted"] = '';
            // $response["isRoleBasedEmail"] = '';
            // $response["isCatchAllDomain"] = '';
            // $response["verifySMTP"] = '';

            return $response;
        }

        $isDisposableDomain = self::isFreeEmailProvider($email);
        $isInboxFull = self::isInboxFull($email);
        $isFreeEmailProvider = self::isFreeEmailProvider($email);
        $isGibberishEmail = self::isGibberishEmail($email);
        $checkDomainReputation = self::checkDomainReputation($email);
        $isSMTPBlacklisted = self::isSMTPBlacklisted($email);
        $isRoleBasedEmail = self::isRoleBasedEmail($email);
        $isCatchAllDomain = self::isCatchAllDomain($email);
        $verifySMTP = self::verifySMTP($email);
        $mxRecords = self::getMxRecords($email);
        $isDomainOldEnough = self::isDomainOldEnough($email);

        error_log("isCatchAllDomain: $isCatchAllDomain");
        // error_log("isInboxFull: $isInboxFull");
        // error_log("verifySMTP: $verifySMTP");
        error_log("isDomainOldEnough: $isDomainOldEnough");


        if ($testType == 'short') {
            $response["status"] = 'valid';
            $response["safeToSend"] = 'yes';
            return $response;
        }

        $response["status"] = 'valid';
        $response["domain"] = $domain;
        $response["username"] = $username;
        $response["safeToSend"] = 'yes';
        $response["mxRecords"] = $mxRecords;

        $response["isSyntaxValid"] = $isSyntaxValid ? 'yes' : 'no';
        $response["isValidEmail"] = $isValidEmail ? 'yes' : 'no';
        $response["hasValidDomain"] = $hasValidDomain ? 'yes' : 'no';
        $response["isDisposableDomain"] = $isDisposableDomain ? 'yes' : 'no';
        $response["isInboxFull"] = $isInboxFull ? 'yes' : 'no';
        $response["isFreeEmailProvider"] = $isFreeEmailProvider ? 'yes' : 'no';
        $response["isGibberishEmail"] = $isGibberishEmail ? 'yes' : 'no';
        $response["checkDomainReputation"] = $checkDomainReputation ? 'good' : 'bad';
        $response["isSMTPBlacklisted"] = $isSMTPBlacklisted ? 'yes' : 'no';
        $response["isRoleBasedEmail"] = $isRoleBasedEmail ? 'yes' : 'no';
        $response["isCatchAllDomain"] = $isCatchAllDomain ? 'yes' : 'no';
        $response["verifySMTP"] = $verifySMTP ? 'yes' : 'no';





        return $response;
    }
}
