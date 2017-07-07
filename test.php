<?php
// Include the Mailer Class
include 'yx-mailer.php';

// Initialize YX_Mailer
$mail = new YX_Mailer();

// Set "From" field
$mail->From = "mail@sender.com";
$mail->FromName = "Mail Test";

// Set Sender (if using Post Fix)
# $mail->Sender = "sender@sender.com";

/**
 * For any of the recipient types, you can add more than one address.
 */

// Adding Recipient
$mail->AddAddress( "address@mail.com", "User Name" );
# $mail->AddAddress( "addressalt@mail.com", "Alternative Name" );

// Add Carbon Copy (optional)
# $mail->AddCc( "copy@mail.comm", "Copy Kun" );

// Add Blind Carbon Copy (optional
# $mail->AddBcc( "blind@mail.com" );

// Set Subject
$mail->Subject = "Test Subject";

// Define the Body
$mail->Body = "<p>This is just a test body.</p>";

// Defining the pure text body
$mail->AltBody = 'This is just a text body.';

// Define Charset for the e-mail encoding
$mail->CharSet = "UTF-8";

/**
 * You can add as many attachments as possible, within the limit of 11mb.
 * 
 * The sending methods checks if the attachments are large, then stops sending.
 * 
 * It's important that you declare both the FULL path for the file, with file 
 * name includes, AND the file's original/intended name (as you can change the 
 * name while attaching), so the MIME types are correctly verified.
 */
// Add Attachments
# $mail->AddAttachment( "[fullpath]/img1.png", "pro1.png" );
# $mail->AddAttachment( "[fullpath]/img2.png", "pro2.png" );

// If the e-mail is to be sent in HTML or text only (default: TRUE)
$mail->IsHTML( true );

// Sends the e-mail
$send = $mail->Send();

/**
 * If the 'Send' method returns false, you can parse the error through the 
 * "$ErrorInfo" variable.
 */
if ( false === $send ) {
    // Echoes Errors
    echo $mail->ErrorInfo;
} else {
    // Clears all Attachments (safety reasons only)
    $mail->ClearAttachments();

    // Clears all Recipients (safety reasons only)
    $mail->ClearAllRecipients();
}
