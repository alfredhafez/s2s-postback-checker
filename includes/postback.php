<?php
// Postback firing system

function firePostback($conversionId, $pdo) {
    try {
        // Get conversion details
        $stmt = $pdo->prepare("
            SELECT c.*, o.name as offer_name, o.postback_template as offer_postback_template, o.goal_name, cl.sub1, cl.sub2, cl.sub3, cl.sub4, cl.sub5
            FROM conversions c
            LEFT JOIN offers o ON c.offer_id = o.id
            LEFT JOIN clicks cl ON c.click_id = cl.id
            WHERE c.id = ?
        ");
        $stmt->execute([$conversionId]);
        $conversion = $stmt->fetch();
        
        if (!$conversion) {
            error_log("Postback error: Conversion not found - ID: $conversionId");
            return false;
        }
        
        // Get postback template
        $postbackTemplate = $conversion['offer_postback_template'];
        
        if (empty($postbackTemplate)) {
            // Use global template
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'postback_template'");
            $stmt->execute();
            $setting = $stmt->fetch();
            $postbackTemplate = $setting['setting_value'] ?? '';
        }
        
        if (empty($postbackTemplate)) {
            error_log("Postback error: No postback template configured");
            return false;
        }
        
        // Replace tokens
        $tokens = [
            '{transaction_id}' => $conversion['transaction_id'],
            '{goal}' => $conversion['goal'],
            '{name}' => urlencode($conversion['name'] ?? ''),
            '{email}' => urlencode($conversion['email'] ?? ''),
            '{phone}' => urlencode($conversion['phone'] ?? ''),
            '{offer_id}' => $conversion['offer_id'],
            '{offer_name}' => urlencode($conversion['offer_name'] ?? ''),
            '{payout}' => $conversion['payout'],
            '{revenue}' => $conversion['revenue'],
            '{sub1}' => urlencode($conversion['sub1'] ?? ''),
            '{sub2}' => urlencode($conversion['sub2'] ?? ''),
            '{sub3}' => urlencode($conversion['sub3'] ?? ''),
            '{sub4}' => urlencode($conversion['sub4'] ?? ''),
            '{sub5}' => urlencode($conversion['sub5'] ?? ''),
            '{ip}' => $conversion['ip_address'],
            '{timestamp}' => time(),
            '{date}' => date('Y-m-d'),
            '{datetime}' => date('Y-m-d H:i:s')
        ];
        
        $postbackUrl = str_replace(array_keys($tokens), array_values($tokens), $postbackTemplate);
        
        // Fire the postback
        $startTime = microtime(true);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'user_agent' => 'S2S-Postback-Checker/1.0',
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents($postbackUrl, false, $context);
        $responseTime = round((microtime(true) - $startTime) * 1000, 3); // milliseconds
        
        // Parse response
        $httpStatus = null;
        $errorMessage = null;
        
        if ($response !== false && isset($http_response_header)) {
            // Extract HTTP status from headers
            foreach ($http_response_header as $header) {
                if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                    $httpStatus = (int)$matches[1];
                    break;
                }
            }
        } else {
            $errorMessage = error_get_last()['message'] ?? 'Failed to send postback';
            $response = null;
        }
        
        // Log the postback
        $stmt = $pdo->prepare("
            INSERT INTO postback_logs (conversion_id, postback_url, http_status, response_body, response_time, error_message) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $conversionId,
            $postbackUrl,
            $httpStatus,
            $response,
            $responseTime,
            $errorMessage
        ]);
        
        return $httpStatus >= 200 && $httpStatus < 300;
        
    } catch (Exception $e) {
        error_log("Postback error: " . $e->getMessage());
        return false;
    }
}

function testPostback($postbackUrl, $pdo = null) {
    $startTime = microtime(true);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 30,
            'user_agent' => 'S2S-Postback-Checker/1.0 (Manual Test)',
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($postbackUrl, false, $context);
    $responseTime = round((microtime(true) - $startTime) * 1000, 3); // milliseconds
    
    // Parse response
    $httpStatus = null;
    $errorMessage = null;
    
    if ($response !== false && isset($http_response_header)) {
        // Extract HTTP status from headers
        foreach ($http_response_header as $header) {
            if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                $httpStatus = (int)$matches[1];
                break;
            }
        }
    } else {
        $errorMessage = error_get_last()['message'] ?? 'Failed to send request';
        $response = null;
    }
    
    $result = [
        'url' => $postbackUrl,
        'http_status' => $httpStatus,
        'response_body' => $response,
        'response_time' => $responseTime,
        'error_message' => $errorMessage,
        'success' => $httpStatus >= 200 && $httpStatus < 300
    ];
    
    // Log manual test if PDO is provided
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO manual_tests (test_name, postback_url, http_status, response_body, response_time, error_message) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                'Manual Test - ' . date('Y-m-d H:i:s'),
                $postbackUrl,
                $httpStatus,
                $response,
                $responseTime,
                $errorMessage
            ]);
        } catch (PDOException $e) {
            error_log("Manual test logging error: " . $e->getMessage());
        }
    }
    
    return $result;
}
?>