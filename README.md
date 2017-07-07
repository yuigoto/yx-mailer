YX Mailer
=========

YX Mailer is a very light PHP mail sending class, with support for attachments.

It works in a similar way as PHPMailer, but doesn't have the SSL and SMTP 
authentication server (hence "being light").

The method also has a mime-type checker, with fallbacks to older versions of PHP.

I built it because of lots of client-related reasons, so it might not be useful 
to everyone. Please test before using it!

Nevertheless, it was really great to learn a few things! :)

-----

#### How to Use

This is a very simple example of the class. Its usage is mostly similar to PHPMailer:

```php
// Include the Mailer Class
include 'yx-mailer.php';

// Initialize SXMailer
$mail = new YX_Mailer();

// Set "From" field
$mail->From = "test@sender.com";
$mail->FromName = "Sender Name";

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
```

-----

#### License

This projects is licensed under the open source `MIT License`.

&copy; 2016-2017 Fabio Y. Goto

-----

#### Author

**Fabio Y. Goto** (_[lab@yuiti.com.br](mailto:lab@yuiti.com.br)_)
