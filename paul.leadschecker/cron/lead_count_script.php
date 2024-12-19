<?php

$webhookUrl = "https://testb24:8890/rest/1/6jbm1vogmer9o1d4/crm.lead.list.json";
$logFile = '/Users/pavelbalaganskij/projects/testb24/local/leads_count_log.txt';
$filter = [];
$select = ['ID'];
$start = 0;
$limit = 50;

function getLeadsCount($webhookUrl, $filter, $select, $start, $limit) {
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
            echo 'cURL Error: ' . curl_error($ch);
            return false;
        }

        curl_close($ch);

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            echo 'API Error: ' . $data['error_description'];
            return false;
        }

        $allLeads = array_merge($allLeads, $data['result']);

        if (count($data['result']) < $limit) {
            break;
        }

        $start += $limit;
    }

    return $allLeads;
}

$leads = getLeadsCount($webhookUrl, $filter, $select, $start, $limit);

if ($leads !== false) {
    $leadCount = count($leads);
    $currentDateTime = date("Y-m-d H:i:s");

    $logMessage = "$currentDateTime - Total Leads: $leadCount\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);

    echo "Log updated successfully: $logMessage";
} else {
    $errorMessage = date("Y-m-d H:i:s") . " - Error occurred while fetching leads.\n";
    file_put_contents($logFile, $errorMessage, FILE_APPEND);
    echo "Error occurred while fetching leads.\n";
}

?>
