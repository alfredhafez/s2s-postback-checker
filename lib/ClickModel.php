<?php
class ClickModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new click record with proper PDO binding to fix HY093 error
     */
    public function create($offerId, $sub1 = null, $sub2 = null, $sub3 = null, $sub4 = null, $sub5 = null, $meta = [], $source = 'click') {
        try {
            // Use positional placeholders to avoid HY093 Invalid parameter number
            $sql = "INSERT INTO clicks (offer_id, transaction_id, sub1, sub2, sub3, sub4, sub5, meta, source, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $transactionId = $sub1 ?: $this->generateTransactionId();
            
            // Ensure meta is properly JSON encoded and never directly interpolated
            $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE);
            
            $stmt = $this->pdo->prepare($sql);
            $params = [
                $offerId,
                $transactionId,
                $sub1,
                $sub2,
                $sub3,
                $sub4,
                $sub5,
                $metaJson,
                $source
            ];
            
            $result = $stmt->execute($params);
            
            if ($result) {
                $clickId = $this->pdo->lastInsertId();
                error_log("Click created successfully: ID={$clickId}, transaction_id={$transactionId}, offer_id={$offerId}, source={$source}");
                return $clickId;
            } else {
                error_log("Failed to create click: " . print_r($stmt->errorInfo(), true));
                return false;
            }
            
        } catch (PDOException $e) {
            error_log("ClickModel::create PDO Error: " . $e->getMessage() . " | SQL: {$sql} | Params: " . print_r($params, true));
            return false;
        }
    }
    
    /**
     * Find click by transaction ID with proper parameter binding
     */
    public function findByTransactionId($transactionId) {
        try {
            $sql = "SELECT * FROM clicks WHERE transaction_id = ? ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$transactionId]);
            
            $result = $stmt->fetch();
            
            if ($result) {
                // Decode meta JSON if it exists
                if (!empty($result['meta'])) {
                    $result['meta'] = json_decode($result['meta'], true);
                }
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("ClickModel::findByTransactionId PDO Error: " . $e->getMessage() . " | transaction_id: {$transactionId}");
            return false;
        }
    }
    
    /**
     * Find click by ID with proper parameter binding
     */
    public function findById($clickId) {
        try {
            $sql = "SELECT * FROM clicks WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$clickId]);
            
            $result = $stmt->fetch();
            
            if ($result && !empty($result['meta'])) {
                $result['meta'] = json_decode($result['meta'], true);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("ClickModel::findById PDO Error: " . $e->getMessage() . " | click_id: {$clickId}");
            return false;
        }
    }
    
    /**
     * Get recent clicks with robust parameter binding
     */
    public function recent($limit = 10, $offerId = null) {
        try {
            if ($offerId) {
                $sql = "SELECT * FROM clicks WHERE offer_id = ? ORDER BY created_at DESC LIMIT ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$offerId, $limit]);
            } else {
                $sql = "SELECT * FROM clicks ORDER BY created_at DESC LIMIT ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$limit]);
            }
            
            $results = $stmt->fetchAll();
            
            // Decode meta JSON for each result
            foreach ($results as &$result) {
                if (!empty($result['meta'])) {
                    $result['meta'] = json_decode($result['meta'], true);
                }
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log("ClickModel::recent PDO Error: " . $e->getMessage() . " | limit: {$limit}, offer_id: " . ($offerId ?: 'null'));
            return [];
        }
    }
    
    /**
     * Generate a unique transaction ID
     */
    private function generateTransactionId() {
        return 'txn_' . uniqid() . '_' . mt_rand(1000, 9999);
    }
    
    /**
     * Update click with conversion data
     */
    public function recordConversion($clickId, $name = null, $email = null, $goal = 'lead') {
        try {
            $sql = "UPDATE clicks SET converted_at = NOW(), conversion_name = ?, conversion_email = ?, conversion_goal = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$name, $email, $goal, $clickId]);
            
            if ($result) {
                error_log("Conversion recorded for click ID: {$clickId}, goal: {$goal}");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("ClickModel::recordConversion PDO Error: " . $e->getMessage() . " | click_id: {$clickId}");
            return false;
        }
    }
}
?>