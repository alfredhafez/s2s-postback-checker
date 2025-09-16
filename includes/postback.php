<?php
/**
 * Postback system with token replacement and HTTP firing
 */

function firePostback($pdo, $clickId, $data, $customTemplate = null) {
    $startTime = microtime(true);
    
    try {
        // Get global postback template if no custom template provided
        if (!$customTemplate) {
            $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = 'global_postback_template'");
            $stmt->execute();
            $result = $stmt->fetch();
            $customTemplate = $result['value'] ?? 'https://example.com/postback?tid={transaction_id}';
        }
        
        // Replace tokens in the postback URL
        $postbackUrl = replaceTokens($customTemplate, $data);
        
        // Fire HTTP request
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $postbackUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_USERAGENT => 'S2S-Postback-Checker/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        $responseTime = round((microtime(true) - $startTime) * 1000, 2); // in milliseconds
        
        // Log the postback attempt
        logPostback($pdo, $clickId, $postbackUrl, $httpCode, $response, $responseTime, $error);
        
        $success = !$error && $httpCode >= 200 && $httpCode < 400;
        
        return [
            'success' => $success,
            'url' => $postbackUrl,
            'http_code' => $httpCode,
            'response' => $response,
            'response_time' => $responseTime,
            'error' => $error
        ];
        
    } catch (Exception $e) {
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);
        error_log("Postback firing error: " . $e->getMessage());
        
        return [
            'success' => false,
            'url' => $postbackUrl ?? 'Unknown',
            'http_code' => 0,
            'response' => '',
            'response_time' => $responseTime,
            'error' => $e->getMessage()
        ];
    }
}

function replaceTokens($template, $data) {
    $tokens = [
        '{transaction_id}' => $data['transaction_id'] ?? '',
        '{goal}' => $data['goal'] ?? '',
        '{name}' => urlencode($data['name'] ?? ''),
        '{email}' => urlencode($data['email'] ?? ''),
        '{offer_id}' => $data['offer_id'] ?? '',
        '{sub1}' => urlencode($data['sub1'] ?? ''),
        '{sub2}' => urlencode($data['sub2'] ?? ''),
        '{sub3}' => urlencode($data['sub3'] ?? ''),
        '{sub4}' => urlencode($data['sub4'] ?? ''),
        '{sub5}' => urlencode($data['sub5'] ?? ''),
        '{timestamp}' => $data['timestamp'] ?? time(),
        '{click_id}' => $data['click_id'] ?? '',
        '{unix_timestamp}' => time(),
        '{date}' => date('Y-m-d'),
        '{datetime}' => date('Y-m-d H:i:s'),
        '{ip}' => $_SERVER['REMOTE_ADDR'] ?? '',
        '{user_agent}' => urlencode($_SERVER['HTTP_USER_AGENT'] ?? ''),
        '{referer}' => urlencode($_SERVER['HTTP_REFERER'] ?? ''),
        '{random}' => mt_rand(100000, 999999)
    ];
    
    return str_replace(array_keys($tokens), array_values($tokens), $template);
}

function logPostback($pdo, $clickId, $url, $httpCode, $response, $responseTime, $error) {
    try {
        $stmt = $pdo->prepare("INSERT INTO postback_logs (click_id, url, http_code, response_body, response_time, error_message) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $clickId,
            $url,
            $httpCode,
            substr($response, 0, 5000), // Limit response body to 5000 chars
            $responseTime,
            $error
        ]);
        
        error_log("Postback logged: click_id={$clickId}, url={$url}, http_code={$httpCode}, response_time={$responseTime}ms");
    } catch (PDOException $e) {
        error_log("Failed to log postback: " . $e->getMessage());
    }
}

/**
 * Manual postback testing function
 */
function testPostbackManually($pdo, $testUrl, $transactionId) {
    $startTime = microtime(true);
    
    try {
        // Fire HTTP request
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $testUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_USERAGENT => 'S2S-Postback-Checker-Manual-Test/1.0',
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);
        
        // Log manual test
        try {
            $stmt = $pdo->prepare("INSERT INTO manual_tests (transaction_id, test_url, http_code, response_body, response_time, error_message) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $transactionId,
                $testUrl,
                $httpCode,
                substr($response, 0, 5000),
                $responseTime,
                $error
            ]);
        } catch (PDOException $e) {
            error_log("Failed to log manual test: " . $e->getMessage());
        }
        
        $success = !$error && $httpCode >= 200 && $httpCode < 400;
        
        return [
            'success' => $success,
            'url' => $testUrl,
            'http_code' => $httpCode,
            'response' => $response,
            'response_time' => $responseTime,
            'error' => $error
        ];
        
    } catch (Exception $e) {
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);
        error_log("Manual postback test error: " . $e->getMessage());
        
        return [
            'success' => false,
            'url' => $testUrl,
            'http_code' => 0,
            'response' => '',
            'response_time' => $responseTime,
            'error' => $e->getMessage()
        ];
    }
}
?>