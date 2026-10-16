<?php
// Script to create the rate_limits table

require_once 'includes/dbh.inc.php';

$sql = "
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) NOT NULL,
  `action` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `idx_identifier_action` (`identifier`, `action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

try {
    $pdo->exec($sql);
    echo "✓ Rate limits table created successfully.\n";
} catch (PDOException $e) {
    echo "✗ Error creating rate limits table: " . $e->getMessage() . "\n";
}
