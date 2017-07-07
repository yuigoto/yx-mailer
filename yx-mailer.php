<?php
/**
 * YX MAILER :: A very light PHP e-mail sending class
 * ============================================================
 * 
 * YX_Mailer is a very light PHP mail sending class, with support 
 * for attachments.
 * 
 * It works in a similar way as PHPMailer, but doesn't have SSL 
 * and SMTP authentication (hence "being light").
 * 
 * The method also has a mime-type checker, with fallbacks to 
 * older versions of PHP.
 * 
 * I built it because of lots of client-related reasons, so it 
 * might not be useful to everyone. :P
 * 
 * Nevertheless, it was really great to learn a few things! :)
 * 
 * ------------------------------------------------------------
 * @author      Fabio Y. Goto <lab@yuiti.com.br>
 * @version     1.1.0
 * @license     MIT License
 * @copyright   © 2016-2017 Fabio Y. Goto
 */
class YX_Mailer 
{
    /**
     * Sender e-mail address.
     * 
     * If using PostFix, this is used as the 'Reply-To' e-mail address.
     * 
     * Must be manually set.
     * 
     * @var string
     */
    public $From = '';
    
    /**
     * Sender's name (optional).
     * 
     * Must be manually set.
     * 
     * @var string
     */
    public $FromName = '';
    
    /**
     * This is the PostFix's sender e-mail. It's used if the server uses 
     * PostFix to send e-mails through the builtin 'mail()', function.
     * 
     * Usually, it MUST be an address belonging to the same domain as where 
     * this script is being executed.
     * 
     * Must be manually set.
     * 
     * @var string
     */
    public $Sender = '';
    
    /**
     * E-mail subject.
     * 
     * Must be manually set.
     * 
     * @var string
     */
    public $Subject = '';
    
    /**
     * HTML data to be sent inside the 'text/html' part of the e-mail's body.
     * 
     * Must be manually set.
     * 
     * @var string
     */
    public $Body = '';
    
    /**
     * Text to be sent inside the 'text/plain' part of the e-mail's body.
     * 
     * Must be manually set.
     * 
     * @var string
     */
    public $AltBody = '';
    
    /**
     * The Charset to be used for the e-mail. Default is 'UTF-8'.
     * 
     * @var string
     */
    public $CharSet = 'UTF-8';
    
    /**
     * Handle for error messages.
     * 
     * @var string
     */
    public $ErrorInfo = null;
    
    /**
     * Indicates if the e-mail will be sent with an HTML body or plain text 
     * body.
     * 
     * @var bool
     */
    private $SendHTML = true;
    
    /**
     * Array that hold the file location (or temporary location) and names for 
     * the attachments to be sent with the e-mail.
     * 
     * @var array
     */
    private $Attachment = array();
    
    /**
     * Associative array, holds three arrays with the names 'to', 'cc' and 
     * 'bcc', which holds the recipients of the e-mail to be sent.
     * 
     * Each array is by itself an associative array, where the keys are the 
     * e-mail addresses and the values are the names of the recipients.
     * 
     * The recipient names are optional, and can be left blank.
     * 
     * @var array
     */
    private $Address = array(
        'to'    => array(), 
        'cc'    => array(), 
        'bcc'   => array()
    );
    
    /**
     * Set by the constructor, is a randomly generated string to act as a 
     * separator between the main e-mail's body.
     * 
     * @var string
     */
    private $BoundaryMain = '';
    
    /**
     * Set by the constructor, is a randomly generated string to act as a 
     * separator between the e-mail's content body (text and HTML).
     * 
     * @var string
     */
    private $BoundarySubs = '';
    
    /** 
     * Class constructor.
     */
    public function __construct() 
    {
        // Defining the boundary hashsets
        $this->BoundaryMain = "messages-main-".md5( sha1( date( 'YmdHis' ) ) );
        $this->BoundarySubs = "messages-text-".md5( sha1( date( 'YmdHis' ) ) );
    }
    
    /** 
     * Class destructor.
     * 
     * Resets all data after execution.
     */
    public function __destruct() 
    {
        // Clear From
        $this->From = '';
        $this->FromName = '';
        $this->Sender = '';
        
        // Clear subject
        $this->Subject = '';
        
        // Resets Charset
        $this->Charset = 'UTF-8';
        
        // Resets SendHTML
        $this->SendHTML = true;
        
        // Resets ErrorInfo
        $this->ErrorInfo = null;
        
        // Clear body
        $this->Body = '';
        $this->AltBody = '';
        
        // Rebuild boundaries
        $this->BoundaryMain = "messages-main-".md5( sha1( date( 'YmdHis' ) + 1 ) );
        $this->BoundarySubs = "messages-text-".md5( sha1( date( 'YmdHis' ) + 1 ) );
        
        // Clears all Attachments
        $this->ClearAttachments();

        // Clears all Recipients
        $this->ClearAllRecipients();
    }
    
    /**
     * Sends the e-mail, returning a boolean value informing the status and, also 
     * defines the error message for the 'ErrorInfo' variable.
     * 
     * @param bool $log 
     *      Optional, whether or not to log and save a copy of the sent mail 
     *      in a file, default: false 
     * @return bool 
     *      True, if successful, false if not
     */
    public function Send( $log = false ) 
    {
        // Set time limit to 0, to avoid timeouts
        set_time_limit(0);
        
        // Building the e-mail header
        $header = $this->BuildHeader();
        
        // If the header returns false (message is already set by BuildHeader)
        if ( false === $header ) return false;
        
        // Array to hold the body of the e-mail
        $body = array();
        
        // Flag that indicates if there are attachments or not
        $file = ( count( $this->Attachment ) > 0 ) ? true : false;
        
        // If has attachment, inserts the main separator here
        if ( $file ) {
            $body[] = '--'.$this->BoundaryMain;
            $body[] = "Content-type: multipart/alternative; boundary=\"{$this->BoundarySubs}\"\r\n";
        }
        
        // If sending a text-only e-mail, checks for the body
        if ( false === $this->SendHTML && '' == trim( $this->AltBody ) ) {
            // Defines error message
            $this->ErrorInfo = 'The plain text body of the e-mail is empty.';
            
            // Return
            return false;
        }
        
        // Adds the plain text part
        if ( '' != trim( $this->AltBody ) ) {
            // Section separator
            if ( $file ) {
                $body[] = "--{$this->BoundarySubs}";
            } else {
                $body[] = "--{$this->BoundaryMain}";
            }
            
            // Section header
            if ( $file || $this->SendHTML ) {
                $body[] = "Content-type: text/plain; Charset=\"{$this->CharSet}\"";
                $body[] = "Content-Transfer-Encoding: 7bit\r\n";
            }
            
            // Adding the AltBody
            $body[] = $this->AltBody."\r\n";
        }
        
        // Adding the HTML text
        if ( true === $this->SendHTML ) {
            // If the HTML content is empty, stops sending
            if ( '' == $this->Body ) {
                // Defines error message
                $this->ErrorInfo = "The HTML body of the e-mail is empty.";
                
                // Return
                return false;
            }
            
            // Section separator
            if ( $file ) {
                $body[] = "--{$this->BoundarySubs}";
            } else {
                $body[] = "--{$this->BoundaryMain}";
            }
            
            // Section header
            $body[] = "Content-type: text/html; Charset=\"{$this->CharSet}\"";
            $body[] = "Content-Transfer-Encoding: 7bit\r\n";
            
            // Defines content if everything's ok
            $body[] = $this->Body."\r\n";
        }
        
        // Section separator
        if ( $file ) {
            $body[] = "--{$this->BoundarySubs}--\r\n";
        }
        
        // Adding attachments
        if ( count( $this->Attachment ) > 0 ) {
            // Variable that will store the size of all attachments
            $size = 0;
            
            // Adding all attachments
            foreach ( $this->Attachment as $item ) {
                // Defining attachment ID
                $id = md5( sha1( $item['name'].date( 'YmdHis' ) ) );
                
                // Adds size
                $size += $item['size'];
                
                // Define attachment header
                $body[] = "--{$this->BoundaryMain}";
                $body[] = "Content-Location: CID:{$id}";
                $body[] = "Content-ID: {$id}";
                $body[] = "Content-Type: {$item[type]}";
                $body[] = "Content-Transfer-Encoding: base64";
                $body[] = "Content-Disposition: attachment; filename=\"{$item[name]}\"\r\n";
                
                // Getting file data
                $data = base64_encode( file_get_contents( $item['file'] ) );
                $body[] = chunk_split( $data );
            }
            
            // Maximum attachment is 11mb, if it passes, then returns false
            if ( $size > 11 ) {
                // Define error message
                $this->ErrorInfo = "Attachment size exceeds the 11mb limit.";
                
                // Returning
                return false;
            }
        }
        
        // Closing the body
        $body[] = "--{$this->BoundaryMain}--\r\n";
        $body = implode( "\r\n", $body );
        
        // Recipient list
        $list = array();
        
        // Building list
        foreach ( $this->Address['to'] as $mail => $name ) {
            $list[] = ( '' == trim( $name ) ) 
                    ? $mail 
                    : $name." <{$mail}>";
        }
        
        // Imploding list
        $list = implode( ", ", $list );
        
        // Sending mail through PostFix
        $send = mail( $list, $this->Subject, $body, $header, "-r".$this->Sender );
        
        // If PostFix doesn't work
        if ( false === $send ) {
            // Sending regular e-mail
            $send = mail( $list, $this->Subject, $body, $header );
            
            // Return
            return ( false === $send ) ? false : true;
        }
        
        // Logging file
        if ( $log === true ) {
            // Define name
            $fname = date("YmdHis")."-maildata.yx";
            if ( !is_dir( "logs/" ) ) mkdir( "logs/" );
            $fop = fopen( "logs/{$fname}", "w+" );
            fwrite( $fop, $header."\r\n".$body );
            fclose( $fop );
        }
        
        // If works, return
        return true;
    }
    
    /**
     * Adds an address to the recipient array.
     * 
     * @param string $mail 
     *      E-mail address to be added to the array
     * @param string $name
     *      Name of the recipient (optional)
     */
    public function AddAddress( $mail, $name = '' ) 
    {
        // Adds the e-mail to the To array
        $this->Address['to'][trim( $mail )] = trim( $name );
    }
    
    /**
     * Adds an address to the carbon copy array.
     * 
     * @param string $mail 
     *      E-mail address to be added to the array
     * @param string $name
     *      Name of the recipient (optional)
     */
    public function AddCC( $mail, $name = '' ) 
    {
        // Adds the e-mail to the Cc array
        $this->Address['cc'][trim( $mail )] = trim( $name );
    }
    
    /**
     * Adds an address to the blind carbon copy array.
     * 
     * @param string $mail 
     *      E-mail address to be added to the array
     * @param string $name
     *      Name of the recipient (optional)
     */
    public function AddBCC( $mail, $name = '' ) 
    {
        // Adds the e-mail to the Bcc array
        $this->Address['bcc'][trim( $mail )] = trim( $name );
    }
    
    /**
     * Adds an attachment to the attachments array.
     * 
     * Each attachment is an array by itself, with the 'file', 'name', 
     * 'size' and 'type' variables.
     * 
     * File size is defined in MB.
     * 
     * IMPORTANT:
     * Can't be used on PHP versions below 5.3, since it uses "finfo".
     * 
     * @param string $filepath 
     *      Full file path, with file name, of the file to be sent
     * @param string $filename 
     *      File name that will be used when displaying the attachment 
     *      and when saving the attachment
     * @return bool 
     *      Returns false if the file is invalid, true if valid
     */
    public function AddAttachment( $filepath, $filename ) 
    {
        // Checking PHP VERSION
        if ( version_compare( PHP_VERSION, '5.3.0', '<' ) ) {
            // "Kills"
            die( "This method can only be used on PHP 5.3.x or newer." );
        }
        
        // If the file is invalid, returns false
        if ( !file_exists( $filepath ) ) return false;
        
        // Checks for MIME type
        $info = $this->MIMETest( $filepath, $filename );
        
        // Getting filesize
        $size = filesize( $filepath ) / 1024 / 1024;
        
        // Adds file and path
        $this->Attachment[] = array(
            'file'  => $filepath, 
            'name'  => $filename, 
            'type'  => trim( $info ), 
            'size'  => number_format( $size, 2 )
        );
        
        // Return
        return true;
    }
    
    /** 
     * Clear all the recipients stored in the $Address array.
     */
    public function ClearAllRecipients() 
    {
        $this->Address = array(
            'to'    => array(), 
            'cc'    => array(), 
            'bcc'   => array()
        );
    }
    
    /**
     * Clear all the attachments in the $Attachment array.
     */
    public function ClearAttachments() 
    {
        $this->Attachment = array();
    }
    
    /**
     * Defines if the e-mail will be sent in the HTML format, or just 
     * plain text.
     * 
     * @param bool $isHTML 
     *      True (default) if the e-mail will be sent as HTML, false if not
     */
    public function IsHTML( $isHTML = true ) 
    {
        // Defines how the e-mail will be primarily sent
        $this->SendHTML = ( $isHTML === false ) ? false : true;
    }
    
    /**
     * Builds the e-mail header to be used when sending the e-mail, with 
     * all the previously declared information.
     * 
     * @return string 
     *      The e-mail header, with all proper addresses and information
     */
    private function BuildHeader() 
    {
        // Header array
        $header = array();
        
        // Message boundary
        $boundary = $this->BoundaryMain;
        
        // Checking From
        if ( false === $this->MailTest( $this->From ) ) {
            // Defines error message
            $this->ErrorInfo = "The senders' e-mail address is invalid.";
            
            // Return false
            return false;
        }
            
        // Adding From
        $header[] = ( $this->FromName != '' ) 
                  ? "From: {$this->FromName} <{$this->From}>" 
                  : "From: {$this->From}";
            
        // If there's no Address to send e-mails
        if ( count( $this->Address['to'] ) < 1 ) {
            // Defines error message
            $this->ErrorInfo = "No e-mail addresses to send the e-mail.";
            
            // Return false
            return false;
        }
        
        /*
        // The TO field causes the e-mail to be sent doubled sometimes 
        // in some servers, so it's commented out for the header.
        
        // Building To
        $To = array();
            
        foreach ( $this->Address['to'] as $mail => $name ) {
            // Checks if name was declared
            $To[] = ( $name != '' ) 
                  ? "{$name} <{$mail}>" 
                  : $mail;
        }
        
        // Adding to field to the header
        $header[] = "To: ".implode( ', ', $To );
        */
        
        // Building Cc
        if ( count( $this->Address['cc'] ) ) {
            $Cc = array();
        
            foreach ( $this->Address['cc'] as $mail => $name ) {
                // Checks if name was declared
                $Cc[] = ( $name != '' ) 
                      ? "{$name} <{$mail}>" 
                      : $mail;
            }
                // Adding cc field to the header
            $header[] = "Cc: ".implode( ', ', $Cc );
        }
            
        // Building Bcc
        if ( count( $this->Address['bcc'] ) ) {
            $Bcc = array();
        
            foreach ( $this->Address['bcc'] as $mail => $name ) {
                // Checks if name was declared
                $Bcc[] = ( $name != '' ) 
                       ? "{$name} <{$mail}>" 
                       : $mail;
            }

            // Adding bcc field to the header
            $header[] = "Bcc: ".implode( ', ', $Bcc );
        }
        
        // Building Return-Path
        if ( $this->Sender ) {
            // Testing the Postfix e-mail address
            if ( false === $this->MailTest( $this->Sender ) ) {
                // Defines error message
                $this->ErrorInfo = "The Postfix e-mail address is invalid.";

                // Return false
                return false;
            }
            
            // If using a Postfix-based sender
            $header[] = "Return-Path: ".$this->Sender;
        } else {
            // If Postfix isn't needed
            $header[] = "Return-Path: ".$this->From;
        }
        
        // Add Reply-To
        $header[] = "Reply-To: ".$this->From;
        
        // Checks for empty subject
        if ( '' == $this->Subject ) {
            // Defines error message
            $this->ErrorInfo = "No subject defined for the e-mail.";
            
            // Return false
            return false;
        }
        
        // Define encoding preferences for the subject charset
        $preferences = array(
            'input-charset'     => 'UTF-8', 
            'output-charset'    => 'UTF-8'
        );
        
        // Encoding the subject
        $subjects = iconv_mime_encode( 'Subject', $this->Subject, $preferences );
        
        // Removing "Subject: ", from the encoded data
        $subjects = preg_replace( 
            "#^(S|s)ubject:\s*#", 
            "",  
            trim( $subjects ) 
        );
        
        // Add the subject
        $header[] = "Subject: ".$subjects;
        
        // Add MIME Version
        $header[] = "MIME-Version: 1.0";
        
        // Defining Content-type
        if ( count( $this->Attachment ) > 0 ) {
            $header[] = "Content-Type: multipart/related; boundary=".$boundary;
        } else {
            if ( $this->SendHTML ) {
                $header[] = "Content-Type: multipart/alternative; boundary=".$boundary;
            } else {
                $header[] = "Content-Type: text/plain; boundary=".$boundary;
            }
        }
    
        // Returning the imploded header block
        return implode( "\r\n", $header );
    }
    
    /**
     * Checks if an e-mail address is valid, returning bool true, if valid, or 
     * false, if invalid.
     * 
     * It doesn't validate the e-mail address' existence, instead it checks if 
     * the address has a proper e-mail structure ([name]@[domain]) and, also, 
     * tests if the address' domain has a MX DNS record (indicating that it has, 
     * indeed, an e-mail server pointed to the domain).
     * 
     * @param string $mail 
     *      E-mail address to be tested
     * @return bool 
     *      True if the e-mail and domain are valid, false if invalid
     */
    private function MailTest( $mail ) 
    {
        // Trimming the address
        $mail = trim( $mail );
        
        // If the e-mail address is an empty value, returns false
        if ( '' == $mail ) return false;
        
        // REGEX alphanumeric flag (to make the lines a bit shorter)
        $an = 'A-Za-z0-9';
        
        // REGEX flag for testing the e-mail address
        $flag = "^([{$an}\-\_]+)((\.|\+)([{$an}\-\_]+))*@";
        $flag.= "[{$an}\-\_]+(\.[{$an}]+)*(\.[{$an}]{2,7})$";
        
        // Testing the address
        $test = ( !preg_match( "#{$flag}#", $mail ) ) ? false : true;
        
        // Splitting the e-mail to extract the domain
        $frag = explode( "@", $mail );
        
        // If there's no suffix (after the @), returns false
        if ( !isset( $frag[1] ) || '' == trim( $frag[1] ) ) return false;
        
        // Checking the MX records
        $mx = ( !checkdnsrr( $frag[1], 'MX' ) ) ? false : true;
        
        // Returning value
        return ( $test && $mx ) ? true : false;
    }
    
    /**
     * Checks and returns the MIME type for a file.
     * 
     * This method has a fallback for PHP versions earlier than 5.3.x or for 
     * those installations that don't have the 'finfo' class or the 
     * 'mime_content_type' (from the 'mime_magic' extension) installed.
     * 
     * The fallback method REQUIRES the 'MIMEMake' method, so it can generate 
     * a MIME magic types file.
     * 
     * @param string $location 
     *      File path with full file name, for checking, it's necessary for it 
     *      to have the full system path
     * @param string $filename 
     *      The original file name only. This used when the location contains 
     *      only a temporary file (like when using the $_FILES variable), and 
     *      is used for the fallback methods
     * @return string 
     *      File MIME type or just a generic type (application/octet-stream)
     */
    private function MIMETest( $location, $filename ) 
    {
        // First, checks if 'finfo' exists
        if ( true ===class_exists( 'finfo' ) ) {
            // Start finfo object
            $info = new finfo( FILEINFO_MIME );
            // Pull and split the file information
            $info = split( ";", $info->file( $file ) );
            // Return the information
            return trim( $info[0] );
        }
        
        // If class finfo doesn't exists, checks for 'mime_content_type'
        if ( true === function_exists( 'mime_content_type' ) ) {
            // Returns the mime type
            return trim( mime_content_type( $filepath ) );
        }
        
        // Before the last fallback, test for the MIME reference file
        $this->MIMEMake();
        
        // Opening MIME file
        $list = unserialize( file_get_contents( 'mime.yx' ) );
        
        // Extracts the file extension
        $type = trim( strtolower( strrpos( $filename, '.' ) ) );
        
        // Checks if type exists in array
        return ( isset( $list[ $type ] ) ) 
               ? $list[ $type ] 
               : 'application/octet-stream';
    }
    
    /**
     * Builds file extention and media types array file, based on the Apache 
     * server MIME type reference file, hosted on: "http://svn.apache.org/repos
     * /asf/httpd/httpd/trunk/docs/conf/mime.types".
     * 
     * The file is saved as a text file with an unserialized associative array 
     * where the extensions are the keys and the values are the mime types, the 
     * location of the file is the same as this class'.
     */
    private function MIMEMake() 
    {
        // MIME file name
        $file = "mime.yx";

        // Checks if file exists or not
        if ( false === file_exists( $file ) ) {
            // Apache MIME Type List File URL
            $mime = "http://svn.apache.org/repos/asf/httpd/"
                  . "httpd/trunk/docs/conf/mime.types";
            // Exploding file
            $pull = explode( "\r\n", file_get_contents( $mime ) );
            // If the data pulled didn't become an array
            if ( count( $file ) < 2 ) {
                // Explodes by new line flag only
                $pull = explode( "\n", $pull[0] );
            }

            // Defines the extension/MIME file array
            $list = array();

            // Building file list
            foreach ( $pull as $line ) {
                // Checks if line isn't commented
                if ( 
                    preg_match( "#^([\#\s]+)#", $line ) === 0 
                    && '' != trim( $line ) 
                ) {
                    // Replacing all tabs and spaces with simple space
                    $line = preg_replace( "([\s\t]+)", " ", $line, 1 );
                    // Exploding line (first element is MIME, rest is extensions)
                    $line = explode( " ", $line );
                    // If there's more than one extension
                    if ( count( $line ) > 2 ) {
                        // Define flag for mime type
                        $type = trim( $line[0] );
                        // Checks all extensions
                        for ( $i = 1; $i < count( $line ); $i++ ) {
                            // If not empty
                            if ( trim( $line[$i] ) != '' ) {
                                // Add extension and mimetype
                                $list[ ( string ) trim( $line[$i] ) ] = $type;
                            }
                        }
                    } else {
                        // Add extension and mimetype
                        $list[ trim( $line[1] ) ] = trim( $line[0] );
                    }
                }
            }

            // Generate file
            $open = fopen( $file, "w+" );
            fwrite( $open, serialize( $list ) );
            fclose( $open );
        }
    }
}
