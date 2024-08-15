<?php
header('Content-Type: application/json');

// API URL and Key
$apiUrl = "https://api.slipok.com/api/line/apikey/26449";
$apiKey = "SLIPOK83SA90D";

// Retrieve POST data
$installmentNo = $_POST['installmentNo'] ?? null;
$adminPhone = $_POST['adminPhone'] ?? null;
$paymentAmount = $_POST['paymentAmount'] ?? null;
$remainingAmount = $_POST['remainingAmount'] ?? null;
$monthsRemaining = $_POST['monthsRemaining'] ?? null;
$kioskNumber = $_POST['kioskNumber'] ?? null;

// Check if file is uploaded
if (isset($_FILES['slipFile'])) {
    $file = $_FILES['slipFile'];

    // Validate the file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(["success" => false, "message" => "Error uploading file."]);
        exit;
    }

    // Create CURLFile object for file upload
    $postData = [
        'files' => curl_file_create($file['tmp_name'], $file['type'], $file['name']),
        'log' => true
    ];

    // Add additional data if provided
    if (!empty($_POST['data'])) {
        $postData['data'] = $_POST['data'];
    }
    if (!empty($_POST['url'])) {
        $postData['url'] = $_POST['url'];
    }

    // Connect to the database
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=huay_payment', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch kiosk information
        $stmt = $pdo->prepare('SELECT * FROM kiosks WHERE kiosk_code = :kiosk_code');
        $stmt->execute(['kiosk_code' => $kioskNumber]);
        $kiosk = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$kiosk) {
            echo json_encode(["success" => false, "message" => "Kiosk not found."]);
            exit;
        }

        // Prepare and execute the API request
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-authorization: $apiKey"
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = "cURL Error: " . curl_error($ch);
            echo json_encode(["success" => false, "message" => $error]);
            error_log($error);
        } else {
            // Parse API response if needed
            $apiResponse = json_decode($response, true);

            if (isset($apiResponse['error'])) {
                echo json_encode(["success" => false, "message" => $apiResponse['error']]);
                exit;
            }

            // Send SMS
            $smsResponse = sendSms($kiosk, $remainingAmount, $monthsRemaining, $paymentAmount);
            if ($smsResponse === false) {
                error_log('SMS Sending Failed: ' . curl_error($ch));
            } else {
                echo json_encode(["success" => true, "message" => "File uploaded and SMS sent successfully."]);
            }
        }

        curl_close($ch);

    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
        error_log("Database error: " . $e->getMessage());
    }
} else {
    echo json_encode(["success" => false, "message" => "No file uploaded."]);
    error_log("No file uploaded.");
}

// Function to send SMS
function sendSms($kiosk, $remaining_amount, $months_remaining, $paymentAmount) {
    $ownerName = htmlspecialchars($kiosk['owner_name']);
    $ownerPhone = htmlspecialchars($kiosk['owner_phone']);
    $kioskNumber = htmlspecialchars($kiosk['kiosk_code']);
    $adminPhone = htmlspecialchars($kiosk['admin_phone']);
    $installmentDate = date('d/m/Y');

    $message = "ชื่อเจ้าของตู้: $ownerName\n" .
        "เบอร์มือถือ: $ownerPhone\n" .
        "รหัสตู้: $kioskNumber\n" .
        "ชำระเงินผ่อน: $paymentAmount\n" .
        "งวดวันที่: $installmentDate\n" .
        "ยอดเงินผ่อนคงเหลือ: $remaining_amount บาท\n" .
        "เดือนที่ผ่อนคงเหลือ: $months_remaining เดือน";

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://portal-otp.smsmkt.com/api/send-message',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "api_key: a43b79caf209950fb1b182b7b3f22af3",
            "secret_key: IBw6wqFygAimDHmB",
        ),
        CURLOPT_POSTFIELDS => json_encode(array(
            "sender" => "boxnumchock",
            "phone" => $adminPhone,
            "message" => $message,
        )),
    ));

    $smsResponse = curl_exec($curl);
    
    if (curl_errno($curl)) {
        error_log('SMS cURL Error: ' . curl_error($curl));
    }

    curl_close($curl);

    return $smsResponse;
}
?>
