<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer.php';
require 'SMTP.php';
require 'Exception.php';

echo ''; // <- this holds the output message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize form inputs
    $first_name = htmlspecialchars(trim($_POST['first_name'] ?? ''));
    $last_name = htmlspecialchars(trim($_POST['last_name'] ?? ''));
    $city = htmlspecialchars(trim($_POST['city'] ?? ''));
    $position = htmlspecialchars(trim($_POST['position'] ?? ''));
    $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));

    // Validate required fields
    if (!$first_name || !$last_name || !$city || !$position || !$phone || !$email) {
        echo "❌ All fields are required!";
    } else {
        // Handle CV upload
        $cv = $_FILES['cv'] ?? null;
        $upload_dir = 'uploads/';
        $upload_file = '';

        if ($cv && $cv['error'] === 0) {
            $file_ext = strtolower(pathinfo($cv['name'], PATHINFO_EXTENSION));
            $allowed_types = ['pdf', 'doc', 'docx'];

            if (!in_array($file_ext, $allowed_types)) {
                echo "❌ Invalid file type. Only PDF, DOC, or DOCX allowed.";
            } else {
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $upload_file = $upload_dir . time() . '_' . basename($cv['name']);

                if (!move_uploaded_file($cv['tmp_name'], $upload_file)) {
                    echo "❌ Failed to upload CV.";
                } else {
                    // Send email with PHPMailer
                    $mail = new PHPMailer(true);

                    try {
                        $mail->isSMTP();
                        $mail->Host = 'odin.mk-host.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'info@superkitgo.mk';
                        $mail->Password = 'KitGo.12!';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        $mail->Port = 465;

                        $mail->setFrom('info@superkitgo.mk', 'SuperKitGo');
                        $mail->addAddress('superkitgomk@gmail.com');

                        $mail->isHTML(true);
                        $mail->Subject = "New Job Application from $first_name $last_name";
                        $mail->Body = "
                            <h3>New Job Application</h3>
                            <p><strong>Name:</strong> $first_name $last_name</p>
                            <p><strong>City:</strong> $city</p>
                            <p><strong>Position:</strong> $position</p>
                            <p><strong>Phone:</strong> $phone</p>
                            <p><strong>Email:</strong> $email</p>
                            <p><strong>CV:</strong> <a href='https://superkitgo.mk/$upload_file' target='_blank'>Download CV</a></p>
                        ";

                        $mail->addAttachment($upload_file, 'CV_' . $first_name . '_' . $last_name);
                        $mail->send();

                        echo "<p>✅ Your application has been submitted successfully!</p>";
                    } catch (Exception $e) {
                        echo "<strong>❌ Message could not be sent.</strong><br>Mailer Error: " . $mail->ErrorInfo;
                    }
                }
            }
        } else {
            echo "❌ Please upload your CV.";
        }
    }
}
?>
