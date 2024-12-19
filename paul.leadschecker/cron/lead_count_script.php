<?php
require_once __DIR__ . '/../lib/StaticData.php';

$webhookUrl = \Paul\StaticData::WEBHOOK_LEADS_URL;
$logFile = \Paul\StaticData::LOG_FILE_PATH;
$filter = [];
$select = ['ID'];
$start = 0;
$limit = 50;


function getLeadsCount(string $webhookUrl, array $filter, array $select, int $start, int $limit): array
{
    $allLeads = [];

    while (true) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $webhookUrl . '?' . http_build_query([
                'filter' => $filter,
                'select' => $select,
                'start' => $start,
                'limit' => $limit,
            ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('cURL Error: ' . curl_error($ch));
        }

        curl_close($ch);

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            throw new Exception('API Error: ' . $data['error_description']);
        }

        $allLeads = array_merge($allLeads, $data['result']);

        if (count($data['result']) < $limit) {
            break;
        }

        $start += $limit;
    }

    return $allLeads;
}

try {
    $leads = getLeadsCount($webhookUrl, $filter, $select, $start, $limit);

    $leadCount = count($leads);
    $currentDateTime = date("Y-m-d H:i:s");
    $logMessage = "$currentDateTime - Total Leads: $leadCount\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);

} catch (Exception $e) {
    $errorMessage = date("Y-m-d H:i:s") . " - Error: " . $e->getMessage() . "\n";
    file_put_contents($logFile, $errorMessage, FILE_APPEND);
}

?>
