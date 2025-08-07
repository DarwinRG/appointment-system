<?php
$logFile = '/home/appoint/htdocs/appoint/storage/logs/deploy.log';
$secret = 'Baseplate11'; // Match this with GitHub webhook secret

function logMessage($message) {
    global $logFile;
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL, FILE_APPEND);
}

$payload = file_get_contents('php://input');
$headers = getallheaders();

if (!isset($headers['X-Hub-Signature-256'])) {
    logMessage("Missing signature.");
    http_response_code(403);
    exit('Forbidden');
}

$signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if (!hash_equals($signature, $headers['X-Hub-Signature-256'])) {
    logMessage("Invalid signature.");
    http_response_code(403);
    exit('Forbidden');
}

$data = json_decode($payload, true);
$branch = $data['ref'] ?? '';

if ($branch !== 'refs/heads/main') {
    logMessage("Ignored push to branch: $branch");
    http_response_code(200);
    exit("Non-main branch ignored.");
}

try {
    logMessage("Starting deployment...");

    $output = [];
    $returnCode = 0;

    exec('cd /home/appoint/htdocs/appoint && git pull origin main 2>&1', $output, $returnCode);
    logMessage("Git output:\n" . implode("\n", $output));

    if ($returnCode !== 0) {
        throw new Exception("Git pull failed.");
    }

    $output = [];
    exec('cd /home/appoint/htdocs/appoint && php artisan easy-deploy:run 2>&1', $output, $returnCode);
    logMessage("Artisan output:\n" . implode("\n", $output));

    if ($returnCode !== 0) {
        throw new Exception("Artisan deploy failed.");
    }

    logMessage("Deployment successful.");
    echo "Deployment complete.";

} catch (Exception $e) {
    logMessage("Error: " . $e->getMessage());
    http_response_code(500);
    echo "Deployment failed.";
}
