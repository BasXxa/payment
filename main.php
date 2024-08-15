<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}
?>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Title # Start -->
    <?php include '../config/config.php'; ?>
    <title><?php echo $title; ?></title>
    <!-- Title # End -->

    <!-- Link # Start -->
    <link rel="stylesheet" href="../assets/css/main.style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Link # End -->
</head>
<!-- Body # Start -->
<div class="container mx-auto px-1">
    <div class="w-100 h-50 flex flex-col border border-cyan-900 p-1 rounded-xl bg-cyan-200 p-3 mt-5">
        <span class="text-xl underline">ข้อมูลผู้ใช้</span>
        <div class="account-group mt-2 flex gap-2">
            <div class="icon">
                <i class="fa-solid fa-user"></i>
            </div>
            <span>ชื่อผู้ใช้ : <span class="text-blue-800"> <?php echo $_SESSION['user_firstname'] . ' ' . $_SESSION['user_lastname']; ?></span></span>
        </div>
        <div class="account-group flex gap-2">
            <div class="icon">
                <i class="fa-solid fa-phone"></i>
            </div>
            <span>เบอร์โทรเจ้าของตู้ : <?php echo $_SESSION['user_phone']; ?></span>
        </div>
        <div class="account-group flex gap-2">
            <div class="icon">
                <i class="fa-solid fa-phone"></i>
            </div>
            <?php include '../api/get_info.php'; ?>
            <span>เบอร์โทรเซลล์ : <?php echo $_SESSION['sales_phone']; ?></span>
        </div>
        <div class="account-group flex gap-2">
            <div class="icon">
                <i class="fa-solid fa-phone"></i>
            </div>
            <span>เบอร์โทรแอดมิน : <?php echo $_SESSION['admin_phone']; ?></span>
        </div>
        <div class="account-group flex gap-2">
            <div class="icon">
                <i class="fa-solid fa-location-dot"></i>
            </div>
            <span> ที่อยู่ : บ้านเลขที่.<?php echo $_SESSION['user_address']; ?> ต.<?php echo $_SESSION['user_subdistrict']; ?> อ.<?php echo $_SESSION['user_district']; ?> จ.<?php echo $_SESSION['user_province']; ?></span>
        </div>
        <div class="account-group flex gap-2">
            <div class="icon">
                <i class="fa-solid fa-cube"></i>
            </div>
            <?php include '../api/get_kiosk.php'; ?>
            <span>จำนวนตู้ : <?php echo count($kiosks); ?></span>
        </div>
        <!-- Button # Start -->
        <div class="flex mt-3 justify-end gap-2">
            <button onclick="logout()" class="focus:outline-none text-white bg-red-500 hover:bg-red-800 font-medium rounded-lg text-sm px-5 py-2.5">ออกจากระบบ</button>
            <button onclick="setting()" class="focus:outline-none text-white bg-blue-500 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5">ตั้งค่า</button>
        </div>
        <!-- Button # End -->
    </div>

    <!-- Kiosk Show # Start -->
    <div class="kiosk-index mt-5">
        <?php if (empty($kiosks)) : ?>
            <div class="text-red-500 flex items-center">ไม่มีตู้</div>
        <?php else : ?>
            <?php foreach ($kiosks as $index => $kiosk) : ?>
                <?php
                $kiosk_id = $kiosk['id'];
                $remaining_amount = 0;
                $installments_paid = 0;

                $sql = "SELECT remaining_amount, status FROM installments WHERE kiosk_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$kiosk_id]);
                $installments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($installments as $installment) {
                    if ($installment['status'] == 'paid') {
                        $installments_paid++;
                    } else {
                        $remaining_amount += $installment['remaining_amount'];
                    }
                }

                $months_remaining = $kiosk['installment_months'] - $installments_paid;

                $kiosk_border_class = $months_remaining == 0 ? 'border-green-500 bg-green-100' : 'border-slate-900 bg-rose-200';
                ?>
                <div class="kiosk-container <?php echo $kiosk_border_class; ?> p-1 w-full border rounded-lg mb-4">
                    <div class="kiosk-header flex items-center gap-3" onclick="showPayment(<?php echo $index; ?>)">

                        <!-- Kiosk Id # Start -->
                        <div class="kiosk-id flex items-center justify-center border rounded-lg bg-red-400" style="width: 45px; height: 45px;">
                            <span class="text-xl"><?php echo $index + 1; ?></span>
                        </div>
                        <!-- Kiosk Id # End -->
                        <!-- Kiosk Info # Start -->
                        <div class="kiosk-info flex items-center gap-3">
                            <div class="flex flex-col">
                                <span>ชื่อเจ้าของตู้: <span class="text-red-600 text-md"> <?php echo htmlspecialchars($kiosk['owner_name']); ?></span></span>
                                <span>รหัสตู้: <span class="text-red-600 text-md"> <?php echo htmlspecialchars($kiosk['kiosk_code']); ?></span></span>
                                <span>ราคาตู้: <span class="text-red-600 text-md"><?php echo htmlspecialchars($kiosk['kiosk_price']); ?></span> บาท</span>
                                <span>เงินดาวน์: <span class="text-red-600 text-md"><?php echo htmlspecialchars($kiosk['down_payment']); ?></span> บาท</span>
                                <span>ผ่อนเดือนละ: <span class="text-red-600 text-md"><?php echo htmlspecialchars($kiosk['monthly_fixed_payment']) ?: 'N/A'; ?></span> บาท</span>
                                <span>ผ่อนทั้งหมด: <span class="text-red-600 text-md"><?php echo htmlspecialchars($kiosk['installment_months']); ?></span> เดือน</span>
                                <span>สถานที่: <span class="text-red-600 text-md"><?php echo htmlspecialchars($kiosk['owner_address']); ?></span></span>
                                <span>เบอร์โทรเจ้าของตู้: <span class="text-red-600 text-md"><?php echo $kiosk['owner_phone']; ?></span></span>
                                <span>เบอร์โทรเซลล์: <span class="text-red-600 text-md"><?php echo htmlspecialchars($kiosk['sales_phone']); ?></span></span>
                                <span>เบอร์โทรแอดมิน: <span class="text-red-600 text-md"><?php echo htmlspecialchars($kiosk['admin_phone']); ?></span></span>
                                <span>ยอดเงินผ่อนคงเหลือ: <span id="remainingAmount" class="text-red-600 text-md"><?php echo $remaining_amount; ?></span> บาท</span>
                                <span>เดือนที่ผ่อนคงเหลือ: <span id="monthsRemaining" class="text-red-600 text-md"><?php echo $months_remaining; ?></span> เดือน</span>
                                <span>ผ่อน 0% จำนวน: <span class="text-red-600 text-md"><?php echo htmlspecialchars($kiosk['installment_zero_percent_months']); ?></span> เดือน</span>
                                <span>คิดดอก: <span class="text-red-600 text-md"><?php echo htmlspecialchars($kiosk['installment_interest_percent']); ?></span> %</span>
                            </div>
                        </div>
                        <!-- Kiosk Info # End -->
                    </div>
                    <!-- Kiosk Show Month # Start -->
                    <div class="kiosk-showmonth fade mt-5">
                        <?php include '../api/kiosk_payment.php'; ?>

                        <?php
                        $current_kiosk_code = $kiosk['kiosk_code'];
                        $current_installments = isset($groupedInstallments[$current_kiosk_code]) ? $groupedInstallments[$current_kiosk_code] : [];
                        ?>

                        <?php if (!empty($current_installments)) : ?>
                            <table class="min-w-full rounded-lg bg-white">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2">งวดที่</th>
                                        <th class="px-4 py-2">วันเดือนปี</th>
                                        <th class="px-4 py-2">สถานะ</th>
                                        <th class="px-4 py-2">ยอดผ่อน</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($current_installments as $installment) : ?>
                                        <?php
                                        switch ($installment['status']) {
                                            case 'paid':
                                                $status_icon = 'fas fa-check status-paid';
                                                $status_text = 'ชำระแล้ว';
                                                $status_class = 'text-green-500'; // สีเขียว
                                                $clickable = false;
                                                break;
                                            case 'overdue':
                                                $status_icon = 'fas fa-exclamation-triangle status-overdue';
                                                $status_text = 'เกินกำหนดชำระ';
                                                $status_class = 'text-red-500'; // สีแดง
                                                $clickable = true;
                                                break;
                                            case 'pending':
                                            default:
                                                $status_icon = 'fas fa-clock status-pending';
                                                $status_text = 'รอชำระ';
                                                $status_class = 'text-red-500'; // สีแดง
                                                $clickable = true;
                                                break;
                                        }
                                        ?>
                                        <tr>
                                            <td class="border px-4 py-2"><?php echo htmlspecialchars($installment['installment_no']); ?></td>
                                            <td class="border px-4 py-2"><?php echo htmlspecialchars($installment['month_year']); ?></td>
                                            <td class="border px-4 py-2 <?php echo $status_class; ?>">
                                                <?php if ($clickable) : ?>
                                                    <span onclick="payment(<?php echo htmlspecialchars($installment['installment_no']); ?>, '<?php echo htmlspecialchars($kiosk['admin_phone']); ?>', '<?php echo htmlspecialchars($installment['remaining_amount']); ?>', '<?php echo htmlspecialchars($kiosk['kiosk_code']); ?>')">
                                                        <i class="<?php echo $status_icon; ?>"></i>
                                                        <?php echo htmlspecialchars($status_text); ?>
                                                    </span>
                                                <?php else : ?>
                                                    <i class="<?php echo $status_icon; ?>"></i>
                                                    <?php echo htmlspecialchars($status_text); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="border px-4 py-2"><?php echo htmlspecialchars($installment['remaining_amount']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else : ?>
                            <p>ไม่มีข้อมูลการผ่อนชำระ</p>
                        <?php endif; ?>
                    </div>
                    <!-- Kiosk Show Month # End -->
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <!-- Kiosk Show # End -->
</div>

<!-- Body # End -->

<!-- Script # Start -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/main.js"></script>

<script type="text/javascript" src="../js/jquery-3.4.1.min.js"></script>
<script type="text/javascript" src="../js/qrcode.min.js"></script>
<script type="text/javascript" src="../js/promptpay.js"></script>

<script src="../vendor/popper/popper.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.min.js"></script>
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>

<script src="../js/new-age.min.js"></script>

<script>
    function payment(installmentNo, adminPhone, remainingAmount, kioskNumber) {
        let countdownInterval;
        let countdownTime = 600;

        var paymentData = {
            installmentNo: installmentNo,
            adminPhone: adminPhone,
            remainingAmount: remainingAmount,
            kioskNumber: kioskNumber
        };

        function updateCountdownText() {
            var countdownTextElement = document.getElementById("countdownText");
            if (countdownTextElement) {
                var minutes = Math.floor(countdownTime / 60);
                var seconds = countdownTime % 60;
                countdownTextElement.textContent = `เหลือเวลา ${minutes}:${seconds < 10 ? '0' : ''}${seconds} นาที`;
            } else {
                console.error('Element with id "countdownText" not found.');
            }
        }

        function startCountdown() {
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
            countdownTime = 600; // Reset countdown time to 10 minutes
            updateCountdownText();
            countdownInterval = setInterval(() => {
                countdownTime--;
                updateCountdownText();

                if (countdownTime <= 0) {
                    clearInterval(countdownInterval);
                    Swal.fire({
                        icon: 'error',
                        title: 'เวลาหมด',
                        text: 'การทำรายการล้มเหลวเนื่องจากไม่ทำรายการภายในเวลาที่กำหนด',
                        timer: 2000,
                        timerProgressBar: true,
                        showConfirmButton: false,
                    }).then(() => {
                        const modal = document.getElementById("paymentModal");
                        if (modal) {
                            modal.style.display = "none";
                        }
                    });
                }
            }, 1000);
        }

        Swal.fire({
            html: `
            <span>ชำระเงินสำหรับตู้หมายเลข ${kioskNumber}</span>
        <div id="countdownText" class="text-center text-red-500 text-lg"></div>
        <div class="flex items-center justify-center">
            <div class="flex flex-col w-100">
                <div id="qrCodeContainer" class="flex flex-col items-center justify-center">
                     <img src="../img/PromptPay-logo.jpg" alt="พร้อมเพย์" style="max-width: 250px;margin-bottom: 10px;">
                    <div id="qrcode" style="width:250px; height:250px;"></div>
                    <span class="flex gap-1">ยอดชำระ: <div id="amount-show" style="text-align: center;"></div></span>
                <hr>
                    <span>โปรดบันทึก QR CODE</span>
                    <span>เพื่อทำการชำระเงิน ภายใน 10 นาที</span>
                <hr>
               <button id="downloadButton" type="button" class="w-100 focus:outline-none text-white bg-purple-700 hover:bg-purple-800 focus:ring-4 focus:ring-purple-300 font-medium rounded-lg text-sm px-5 py-2.5 mb-2 dark:bg-purple-600 dark:hover:bg-purple-700 dark:focus:ring-purple-800">ดาวโหลด</button>
                </div>
            </div>
            <div id="slipContainer" style="display:none;">
                <img id="imagePreview" src="#" alt="Image Preview">
            </div>
        </div>
        <form id="slipForm" enctype="multipart/form-data">
            <div class="file-input-container">
                <label for="slipFile" class="file-label">แนบสลิป:</label>
                <input type="file" id="slipFile" name="slipFile" accept="image/*" required>
            </div>
            <div class="flex flex-col">
                <button type="submit" class="focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 mb-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">ยืนยันชำระเงิน</button>
            </div>
        </form>
    `,
            showCloseButton: true,
            showConfirmButton: false,
            didOpen: () => {
                var qrcode = new QRCode(document.getElementById("qrcode"), {
                    width: 250,
                    height: 250,
                    correctLevel: QRCode.CorrectLevel.L
                });

                function makeCode() {
                    var ppID = `${adminPhone}`;
                    var amount = parseFloat(remainingAmount);

                    qrcode.makeCode(generatePayload(ppID, amount));
                    $("#pp-id-show").html(ppID);

                    if (amount > 0.0) {
                        $("#amount-show").html(Number(amount).toFixed(2) + " บาท");
                    } else {
                        $("#amount-show").html("");
                    }
                }

                makeCode();
                startCountdown();
            },
            didClose: () => {
                clearInterval(countdownInterval);
            }
        });

        document.getElementById("slipForm").onsubmit = async function(event) {
            event.preventDefault();

            var slipFile = document.getElementById("slipFile").files[0];

            if (!slipFile) {
                alert("กรุณาเลือกไฟล์ก่อนส่ง");
                return;
            }

            var allowedExtensions = ['jpg', 'jpeg', 'png', 'jfif', 'webp'];
            var fileExtension = slipFile.name.split('.').pop().toLowerCase();

            if (!allowedExtensions.includes(fileExtension)) {
                alert("ไฟล์ไม่ถูกต้อง โปรดอัปโหลดไฟล์ในรูปแบบ JPG, JPEG, PNG, JFIF หรือ WEBP");
                return;
            }

            var updateResult;
            try {
                updateResult = await updatePaymentStatus(paymentData.installmentNo, paymentData.kioskNumber);
            } catch (error) {
                console.error('Error updating payment status:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถอัปเดตสถานะการชำระเงินได้',
                });
                return;
            }

            if (!updateResult) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถอัปเดตสถานะการชำระเงินได้',
                });
                return;
            }

            var formData = new FormData();
            formData.append("slipFile", slipFile);
            formData.append("installmentNo", paymentData.installmentNo);
            formData.append("adminPhone", paymentData.adminPhone);
            formData.append("paymentAmount", paymentData.remainingAmount);
            formData.append("kioskNumber", paymentData.kioskNumber);
            formData.append("remainingAmount", updateResult.remainingAmount);
            formData.append("monthsRemaining", updateResult.monthsRemaining);

            try {
                let response = await fetch('../api/upload_slip.php', {
                    method: 'POST',
                    body: formData
                });

                let data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: 'การชำระเงินสำเร็จ',
                        timer: 2000,
                        timerProgressBar: true,
                        showConfirmButton: false,
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: data.message || 'ไม่สามารถอัปโหลดสลิปได้',
                    });
                }
            } catch (error) {
                console.error('Error uploading slip:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
                });
            }
        };

        document.getElementById("slipFile").onchange = function(event) {
            var file = event.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var qrCodeContainer = document.getElementById("qrCodeContainer");
                    var slipContainer = document.getElementById("slipContainer");
                    var imagePreview = document.getElementById("imagePreview");

                    qrCodeContainer.style.display = 'none';
                    slipContainer.style.display = 'block';
                    imagePreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        };

        document.getElementById("downloadButton").addEventListener("click", function() {
            var canvas = document.querySelector("#qrcode canvas");
            if (canvas) {
                var imageSrc = canvas.toDataURL("image/png");

                var link = document.createElement('a');
                link.href = imageSrc;
                link.download = 'qrcode.png';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                console.error('QR code canvas not found.');
            }
        });
    }

    function updatePaymentStatus(installment_no, kiosk_number) {
        return fetch('../api/payment_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    installment_no: installment_no,
                    kiosk_number: kiosk_number
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    return {
                        remainingAmount: data.remaining_amount,
                        monthsRemaining: data.months_remaining
                    };
                } else {
                    return null;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'ล้มเหลว',
                    text: 'เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์',
                });
                return null;
            });
    }


    function showPayment(index) {
        const showMonthDivs = document.querySelectorAll('.kiosk-showmonth');
        const selectedDiv = showMonthDivs[index];
        const isVisible = selectedDiv.classList.contains('show');

        showMonthDivs.forEach(div => {
            div.classList.remove('show');
            div.style.display = 'none';
        });

        if (!isVisible) {
            selectedDiv.style.display = 'block';
            selectedDiv.classList.add('show');
        }
    }
</script>
<!-- Script # End -->

<body>

</body>

</html>