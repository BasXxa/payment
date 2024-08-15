<?php
include '../config/config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['installment_no']) && isset($data['kiosk_number'])) {
    $installment_no = $data['installment_no'];
    $kiosk_number = $data['kiosk_number'];

    // อัปเดตสถานะเป็น 'paid' และตั้งค่า remaining_amount เป็น 0
    $sql = "UPDATE installments 
            SET status = 'paid', remaining_amount = 0 
            WHERE installment_no = :installment_no 
              AND kiosk_id = (SELECT id FROM kiosks WHERE kiosk_code = :kiosk_number)";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':installment_no', $installment_no, PDO::PARAM_INT);
    $stmt->bindParam(':kiosk_number', $kiosk_number, PDO::PARAM_STR);

    if ($stmt->execute()) {
        // ดึงข้อมูล remaining_amount และ months_remaining ใหม่
        $sql = "SELECT SUM(remaining_amount) AS remaining_amount, 
                       (SELECT COUNT(*) FROM installments WHERE kiosk_id = (SELECT id FROM kiosks WHERE kiosk_code = :kiosk_number) AND status != 'paid') AS months_remaining 
                FROM installments 
                WHERE kiosk_id = (SELECT id FROM kiosks WHERE kiosk_code = :kiosk_number)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':kiosk_number', $kiosk_number, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $remaining_amount = $result['remaining_amount'];
        $months_remaining = $result['months_remaining'];

        echo json_encode([
            'success' => true,
            'remaining_amount' => $remaining_amount,
            'months_remaining' => $months_remaining
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถอัปเดตสถานะได้']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
}
?>
