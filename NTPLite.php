<?php
/**
 * NTPLite -- A basic SNTPv4 PHP implementation
 * 
 * The NTPLite class represents an SNTP message, to build simple SNTP client/server.
 * Not all RFC 2030 features are available, but it can easily read and write SNTP
 * messages.
 * 
 * Please note that, used in the case of a server, NTPLite does not perform any
 * computation (precision, delay, dispersion, etc.). It just reads and writes
 * messages, you then have to use them yourself.
 * 
 * It is recommended to read RFC 1305 and 2030 to understand the (S)NTP protocol.
 * This class is bundled with client and server sample scripts.
 * 
 * 
 * LICENSE:
 * 
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 * 
 * @package    NTPLite
 * @author     Jerome Marilleau
 * @copyright  2006-2013 MAJe / TenZenXen
 * @license    http://www.gnu.org/licenses/lgpl.txt    LGPL
 * @link       http://github.com/majetzx/ntplite
 * @version    1.4 (2006-08-02)
 */

/**
 * The NTPLite class represents an SNTP message.
 * 
 * Member variables comments come from the RFC 2030.
 * 
 * The 4 timestamps (Reference, Originate, Receive and Transmit) are not usual Unix
 * timestamps, they use the 01/01/1900 as epoch. They can be converted to and from
 * Unix timestamps using convertTsUnixToSntp() and convertTsSntpToUnix() methods.
 * 
 * @package NTPLite
 * @since   1.0
 */
class NTPLite
{
    /* CONSTANTS */
    
    /**
     * SNTP message without authentication, 48 bytes.
     * 
     * @since 1.0
     */
    const SNTP_MSG_NO_AUTH = 48;
    
    /**
     * SNTP message with authentication, 48 bytes + 20 bytes.
     * 
     * @since 1.3
     */
    const SNTP_MSG_AUTH = 68;
    
    /**
     * Number of seconds between the two timestamp references, 01/01/1900 to
     * 01/01/1970.
     * 
     * @since 1.0
     */
    const SNTP_TO_UNIX_TS_INTERVAL = 2208988800;
    
    
    /* PUBLIC MEMBERS, from RFC 2030 */
    
    /**
     * Leap indicator (LI).
     * 
     * This is a two-bit code warning of an impending leap second to be
     * inserted/deleted in the last minute of the current day:
     * 
     *   + <samp>0</samp> = no warning
     *   + <samp>1</samp> = last minute has 61 seconds
     *   + <samp>2</samp> = last minute has 59 seconds
     *   + <samp>3</samp> = alarm condition (clock no synchronized)
     * 
     * @var   integer
     * @since 1.0
     */
    public $leapIndicator = 0;
    
    /**
     * Version number (VN).
     * 
     * This is a three-bit integer indicating the NTP/SNTP version number. The
     * version number is 3 for Version 3 (IPv4 only) and 4 for Version 4 (IPv4, IPv6
     * and OSI).
     * 
     * @var   integer
     * @since 1.0
     */
    public $versionNumber = 0;
    
    /**
     * Mode.
     * 
     * This is a three-bit integer indicating the mode, with values defined as
     * follows:
     * 
     *   + <samp>0</samp> = reserved
     *   + <samp>1</samp> = symmetric active
     *   + <samp>2</samp> = symmetric passive
     *   + <samp>3</samp> = client
     *   + <samp>4</samp> = server
     *   + <samp>5</samp> = broadcast
     *   + <samp>6</samp> = reserved for NTP control message
     *   + <samp>7</samp> = reserved for private use
     * 
     * NTPLite supports modes 3 and 4, others may not work or be available.
     * 
     * @var   integer
     * @since 1.0
     */
    public $mode = 0;
    
    /**
     * Stratum.
     * 
     * This is a eight-bit unsigned integer indicating the stratum level of the
     * local clock, with values defined as follows:
     * 
     *   + <samp>0</samp> = unspecified or unavailable
     *   + <samp>1</samp> = primary reference (e.g., radio clock)
     *   + <samp>2-15</samp> = secondary reference (via NTP or SNTP)
     *   + <samp>16-255</samp> = reserved
     * 
     * @var   integer
     * @since 1.0
     */
    public $stratum = 0;
    
    /**
     * Poll interval.
     * 
     * This is an eight-bit signed integer indicating the maximum interval between
     * successive messages, in seconds to the nearest power of two. The values that
     * can appear in this field presently range from 4 (16 s) to 14 (16284 s);
     * however, most applications use only the sub-range 6 (64 s) to 10 (1024 s).
     * 
     * @var   integer
     * @since 1.0
     */
    public $pollInterval = 0;
    
    /**
     * Precision.
     * 
     * This is an eight-bit signed integer indicating the precision of the local
     * clock, in seconds to the nearest power of two. The values that normally
     * appear in this field range from -6 for mains-frequency clocks to -20 for
     * microsecond clocks found in some workstations.
     * 
     * @var   integer
     * @since 1.0
     */
    public $precision = 0;
    
    /**
     * Root delay.
     * 
     * This is a 32-bit signed fixed-point number indicating the total roundtrip
     * delay to the primary reference source, in seconds with fraction point between
     * bits 15 and 16. Note that this variable can take on both positive and
     * negative values, depending on the relative time and frequency offsets. The
     * values that normally appear in this field range from negative values of a few
     * milliseconds to positive values of several hundred milliseconds.
     * 
     * @var   integer
     * @since 1.0
     */
    public $rootDelay = 0;
    
    /**
     * Root dispersion.
     * 
     * This is a 32-bit unsigned fixed-point number indicating the nominal error
     * relative to the primary reference source, in seconds with fraction point
     * between bits 15 and 16. The values that normally appear in this field range
     * from 0 to several hundred milliseconds.
     * 
     * @var   integer
     * @since 1.0
     */
    public $rootDispersion = 0;
    
    /**
     * Reference identifier.
     * 
     * This is a 32-bit bitstring identifying the particular reference source. In
     * the case of NTP Version 3 or Version 4 stratum-0 (unspecified) or stratum-1
     * (primary) servers, this is a four-character ASCII string, left justified and
     * zero padded to 32 bits. In NTP Version 3 secondary servers, this is the
     * 32-bit IPv4 address of the reference source. In NTP Version 4 secondary
     * servers, this is the low order 32 bits of the latest transmit timestamp of
     * the reference source. NTP primary (stratum 1) servers should set this field
     * to a code identifying the external reference source according to [a list
     * available in RFC 2030]. If the external reference is one of those listed, the
     * associated code should be used. Codes for sources not listed can be contrived
     * as appropriate.
     * 
     * The complete code list is not given here, just an excerpt:
     *   + <samp>LOCL</samp> = uncalibrated local clock used as a primary reference
     *     for a subnet without external means of synchronization
     *   + <samp>PPS</samp> = atomic clock or other pulse-per-second source
     *     individually calibrated to national standards
     *   + <samp>ACTS</samp> = NIST dialup modem service
     *   + <samp>....</samp> = ...
     *   + <samp>GOES</samp> = Geostationary Orbit Environment Satellite 
     * 
     * @var   integer
     * @since 1.0
     */
    public $referenceIdentifier = 0;
    
    /**
     * Reference timestamp.
     * 
     * This is the time at which the local clock was last set or corrected, in
     * 64-bit timestamp format.
     * 
     * @var   integer
     * @since 1.0
     */
    public $referenceTimestamp = 0;
    
    /**
     * Originate timestamp.
     * 
     * This is the time at which the request departed the client for the server, in
     * 64-bit timestamp format.
     * 
     * @var   integer
     * @since 1.0
     */
    public $originateTimestamp = 0;
    
    /**
     * Receive timestamp.
     * 
     * This is the time at which the request arrived at the server, in 64-bit
     * timestamp format.
     * 
     * @var   integer
     * @since 1.0
     */
    public $receiveTimestamp = 0;
    
    /**
     * Transmit timestamp.
     * 
     * This is the time at which the reply departed the server for the client, in
     * 64-bit timestamp format.
     * 
     * @var   integer
     * @since 1.0
     */
    public $transmitTimestamp = 0;
    
    /**
     * Key identifier (optional).
     * 
     * When the NTP authentication scheme is implemented, the Key Identifier field
     * contain the message authentication code (MAC) information defined in Appendix
     * C of RFC-1305.
     * 
     * @var   integer
     * @since 1.3
     */
    public $keyIdentifier = 0;
    
    /**
     * Message digest (optional).
     * 
     * When the NTP authentication scheme is implemented, the Message Digest field
     * contain the message authentication code (MAC) information defined in Appendix
     * C of RFC-1305.
     * 
     * @var   integer
     * @since 1.3
     */
    public $messageDigest = 0;
    
    
    /* PRIVATE MEMBERS, the message */
    
    /**
     * The SNTP message bytes.
     * 
     * @var   array
     * @since 1.0
     */
    private $_messageBytes = array();
    
    /**
     * The SNTP message size, depends whether authentication mode is used or not.
     * 
     * @var   integer
     * @since 1.3
     */
    private $_messageSize = 0;
    
    /**
     * The authentication mode, true if authenticated.
     * 
     * @var   boolean
     * @since 1.3
     */
    private $_authenticated = false;
    
    
    /* CONSTRUCTOR/DESTRUCTOR, et al. */
    
    /**
     * The SNTP message constructor.
     * 
     * Initiate the message, with or without authentication mode.
     * 
     * @param bool $authenticated true if authentication is used, false otherwise
     * 
     * @since 1.0
     */
    public function __construct( $authenticated = false )
    {
        $this->_authenticated = $authenticated;
        $this->_initMessage();
    }
    
    /**
     * Class destructor, destroy the message.
     * 
     * @since 1.0
     */
    public function __destruct()
    {
        unset($this->_message);
    }
    
    /**
     * Set accessor, to prevent user to set invalid members.
     * 
     * @param string $name  the name of the member to set
     * @param mixed  $value the value of the member to set
     * 
     * @since 1.4
     */
    public function __set($name, $value)
    {
        trigger_error("Set invalid member: $name", E_USER_NOTICE);
    }
    
    /**
     * Get accessor, to present user to get invalid members.
     * 
     * @param string $name the name of the member to get
     * 
     * @return null always returns null
     * 
     * @since 1.4
     */
    public function __get($name)
    {
        trigger_error("Get invalid member: $name", E_USER_NOTICE);
        return null;
    }
    
    /**
     * Initiates the message.
     * 
     * Sets the message size, fills it with zeros.
     * 
     * @since 1.0
     */
    private function _initMessage()
    {
        // Size
        $this->_messageSize = $this->_authenticated
                            ? self::SNTP_MSG_AUTH
                            : self::SNTP_MSG_NO_AUTH;
        
        // Bytes, zero-filled
        $this->_messageBytes = array_fill(0, $this->_messageSize, 0x00);
    }
    
    
    /* PUBLIC METHODS, to read and write messages */
    
    /**
     * Sets the internal members from an SNTP message.
     * 
     * @param string $message the message to read
     * 
     * @return bool false if bad-sized message, true otherwise
     * 
     * @since 1.0
     */
    public function readMessage($message)
    {
        // Check the message length
        $length = strlen($message);
        if (($length != self::SNTP_MSG_NO_AUTH)
         && ($length != self::SNTP_MSG_AUTH)) {
            return false;
        }
        
        // Empties internal message
        $this->_authenticated = ($length == self::SNTP_MSG_AUTH);
        $this->_initMessage();
        
        // Transforms the string into bytes
        for ($i = 0; $i < $this->_messageSize; $i++) {
            $this->_messageBytes[$i] = ord($message[$i]);
        }
        
        // Sets member values from the message
        $this->leapIndicator = ( $this->_messageBytes[0] & 0xc0 ) >> 6;
        $this->versionNumber = ( $this->_messageBytes[0] & 0x38 ) >> 3;
        $this->mode =            $this->_messageBytes[0] & 0x07;
        $this->stratum =         $this->_messageBytes[1];
        $this->pollInterval =    $this->_messageBytes[2];
        $this->precision =       $this->_messageBytes[3] | 0xffffff00;
        
        $t = 0x100 *
           ( 0x100 *
           ( 0x100 * $this->_messageBytes[4]
                   + $this->_messageBytes[5] )
                   + $this->_messageBytes[6] )
                   + $this->_messageBytes[7];
        $this->rootDelay = $t / 0x10000; // in seconds
        
        $u = 0x100 *
           ( 0x100 *
           ( 0x100 * $this->_messageBytes[8]
                   + $this->_messageBytes[9] )
                   + $this->_messageBytes[10] )
                   + $this->_messageBytes[11];
        $this->rootDispersion = $u / 0x10000; // in seconds
        
        $this->referenceIdentifier = $this->_readInArray(12, 4);
        
        $this->referenceTimestamp = $this->_readTimestamp(16);
        $this->originateTimestamp = $this->_readTimestamp(24);
        $this->receiveTimestamp   = $this->_readTimestamp(32);
        $this->transmitTimestamp  = $this->_readTimestamp(40);
        
        // In authenticated mode, reads the two optional fields
        if ($this->_authenticated) {
            $this->keyIdentifier = $this->_readInArray(48, 4);
            $this->messageDigest = $this->_readInArray(52, 16);
        }
        
        return true;
    }
    
    /**
     * Builds the SNTP message from the member values.
     * 
     * @return string the message as a string
     * 
     * @since 1.0
     */
    public function writeMessage()
    {
        // Empties internal message
        $this->_initMessage();
        
        // Fill in the message from the members
        $this->_messageBytes[0] = ($this->leapIndicator << 6)
                                | ($this->versionNumber << 3)
                                |  $this->mode;
        $this->_messageBytes[1] = $this->stratum;
        $this->_messageBytes[2] = $this->pollInterval;
        $this->_messageBytes[3] = $this->precision & 0x000000ff;
        
        $this->_writeInArray(4, $this->_intToArray($this->rootDelay      << 16, 4));
        $this->_writeInArray(8, $this->_intToArray($this->rootDispersion << 16, 8));
        
        // The Reference Identifier can be a 4-character string or a long
        if (is_string($this->referenceIdentifier)
           && (strlen($this->referenceIdentifier) <= 4)) {
            $binaryRefId = $this->_strToArray($this->referenceIdentifier);
        } else {
            $binaryRefId = $this->_intToArray($this->referenceIdentifier, 4);
        }
        $this->_writeInArray(12, $binaryRefId);
        
        $this->_writeTimestamp($this->referenceTimestamp, 16);
        $this->_writeTimestamp($this->originateTimestamp, 24);
        $this->_writeTimestamp($this->receiveTimestamp,   32);
        $this->_writeTimestamp($this->transmitTimestamp,  40);
        
        // In authenticated mode, writes the two optional fields
        if ($this->_authenticated) {
            $this->_writeInArray(48, $this->_intToArray($this->keyIdentifier, 4));
            $this->_writeInArray(52, $this->_intToArray($this->messageDigest, 16));
        }
        
        // Transforms the bytes into string
        $message = '';
        for ($i = 0; $i < $this->_messageSize; $i++) {
            $message .= pack('c', $this->_messageBytes[$i]);
        }
        
        return $message;
    }
    
    
    /* PRIVATE METHODS, to manipulate integers, strings and arrays */
    
    /**
     * Transforms an integer into an array of bytes. Puts one byte per array value.
     * 
     * @param integer $value the value to transform
     * @param integer $size  the size of the array to write in
     * 
     * @return array an array filled with the integer
     * 
     * @since 1.0
     */
    private function _intToArray($value, $size)
    {
        $hexSize = 2 * $size;
        $hexValue = sprintf('%0' . $hexSize . 'x', $value);
        $array = array_fill(0, $size, 0x00);
        
        for ($i = 0; $i < $size; $i++)
            $array[$i] = hexdec(substr($hexValue, $i * 2, 2));
        
        return $array;
    }
    
    /**
     * Transforms a string into an array of bytes. Puts one byte per array value.
     * The array size is the string's.
     * 
     * @param string $value the value to transform
     * 
     * @return array an array filled with the string's bytes
     * 
     * @since 1.0
     */
    private function _strToArray($string)
    {
        $stringSize = strlen($string);
        $array = array_fill(0, $stringSize, 0x00);
        
        for ($i = 0; $i < $stringSize; $i++)
            $array[$i] = ord($string[$i]);
        
        return $array;
    }
    
    /**
     * Reads a slice of the internal array and converts it into an integer.
     * 
     * @param integer $start the position to start to read
     * @param integer $size  the slice size
     * 
     * @return integer the read slice, as an integer
     * 
     * @since 1.0
     */
    private function _readInArray($start, $size)
    {
        $slice = array_slice($this->_messageBytes, $start, $size);
        $value = 0;
        
        for ($i = 0; $i < $size; $i++)
            $value |= $slice[$i] << (8 * ($size - 1 - $i));
        
        return $value;
    }
    
    /**
     * Writes an array inside the internal array.
     * 
     * @param integer $start  the position to start to write
     * @param array   $values the array of values to write
     * 
     * @since 1.0
     */
    private function _writeInArray($start, $values)
    {
        $valuesSize = count($values);
        for ($i = 0; $i < $valuesSize; $i++)
            $this->_messageBytes[$start + $i] = $values[$i];
    }
    
    
    /* PRIVATE METHODS, to manipulate SNTP timestamps */
    
    /**
     * Reads a timestamp from the internal message.
     * 
     * @param integer $position the timestamp position in the message
     * 
     * @return integer the timestamp, in milliseconds from the 01/01/1900
     * 
     * @since 1.0
     */
    private function _readTimestamp($position)
    {
        // Timestamps are 8-byte long
        
        // The integer part, 4 first bytes
        $integerPart = 0;
        for ($i = 0; $i < 4; $i++)
            $integerPart = 256 * $integerPart + $this->_messageBytes[$position + $i];
        
        // The fraction part, 4 last bytes
        $fractionPart = 0;
        for ($i = 4; $i < 8; $i++)
            $fractionPart = 256 * $fractionPart + $this->_messageBytes[$position + $i];
        
        // Both values are in seconds, we convert them into milliseconds
        $integerPart  *= 1000;
        $fractionPart *= 1000;
        
        // Gather the two parts together to make the timestamp
        return $integerPart + ( $fractionPart / 4294967296 ); // 0x100000000
    }
    
    /**
     * Writes a timestamp into the internal message.
     * 
     * @param integer $timestamp the timestamp to write, in milliseconds from the
     *                           01/01/1900
     * @param integer $position  the timestamp position in the message
     * 
     * @since 1.0
     */
    private function _writeTimestamp($timestamp, $position)
    {
        // The timestamp is converted to seconds
        $seconds = $timestamp / 1000;
        
        // Separates the integer part and the fraction part
        $integerPart = floor($seconds);
        $fractionPart = $seconds - $integerPart;
        $fractionPart *= 4294967296; // 0x100000000
        
        // Transforms both into bytes array
        $integerBytes  = $this->_intToArray($integerPart,  4);
        $fractionBytes = $this->_intToArray($fractionPart, 4);
        
        // Writes bytes in the message
        $this->_writeInArray($position,     $integerBytes );
        $this->_writeInArray($position + 4, $fractionBytes );
    }
    
    
    /* PUBLIC STATIC METHODS, to do timestamp conversions */
    
    /**
     * Converts a Unix timestamp into an SNTP timestamp.
     * 
     * @param integer $uTimestamp the Unix timestamp
     * 
     * @return integer the SNTP timestamp
     * 
     * @since 1.0
     */
    public static function convertTsUnixToSntp($uTimestamp)
    {
        // Changes the timestamp base, and converts into milliseconds
        return ($uTimestamp + self::SNTP_TO_UNIX_TS_INTERVAL) * 1000;
    }
    
    /**
     * Converts an SNTP timestamp into a Unix timestamp.
     * 
     * @param integer $sTimestamp the SNTP timestamp
     * 
     * @return integer the Unix timestamp
     * 
     * @since 1.0
     */
    public static function convertTsSntpToUnix( $sTimestamp )
    {
        // Converts into seconds, and changes the timestamp base
        return ($sTimestamp / 1000) - self::SNTP_TO_UNIX_TS_INTERVAL;
    }
    
    
    /* METHODS for display */
    
    /**
     * Returns a string representation of the SNTP message.
     * 
     * It uses the member values, not the internal message.
     * 
     * @return string the SNTP message
     * 
     * @since 1.0
     */
    public function __toString()
    {
        $string = '';
        
        // Leap indicator
        $string .= "LI=$this->leapIndicator, ";
        
        // Version number
        $string .= "VN=$this->versionNumber, ";
        
        // Mode
        switch ($this->mode) {
            case 3:  $mode = 'client'; break;
            case 4:  $mode = 'server'; break;
            default: $mode = 'other';  break;
        }
        $string .= "Mode=$mode, ";
        
        // Stratum
        if ($this->stratum == 0)
            $stratum = 'unspec./unav.';
        else if ($this->stratum == 1)
            $stratum = 'primary';
        else if (($this->stratum > 1) && ($this->stratum < 16))
            $stratum = 'secondary';
        else
            $stratum = 'reserved';
        $string .= "Stratum=$this->stratum ($stratum), ";
        
        // Poll interval
        $string .= "PollInter=$this->pollInterval (" . pow(2, $this->pollInterval)
                .  ' sec), ';
        
        $string .= "\n";
        
        // Precision
        $string .= "Precision=$this->precision ("
                .  number_format(pow(2, $this->precision), 6) . ' sec), ';
        
        // Root delay
        $string .= 'RootDelay=' . sprintf('%.4f', $this->rootDelay) . ' sec, ';
        
        // Root dispersion
        $string .= 'RootDispersion=' . sprintf('%.4f', $this->rootDispersion)
                .  ' sec, ';
        
        $string .= "\n";
        
        // Reference identifier
        $string .= "ReferenceIdentifier=$this->referenceIdentifier (";
        if ($this->referenceIdentifier == 'LOCL')
            $string .= 'uncalibrated local clock';
        else
            $string .= long2ip($this->referenceIdentifier);
        $string .= '), ';
        
        $string .= "\n";
        
        // Reference timestamp
        $string .= 'ReferenceTS='
                .  sprintf('%-24s', $this->timestampToString($this->referenceTimestamp))
                .  '    ';
        
        // Originate timestamp
        $string .= 'OriginateTS='
                .  sprintf('%-24s', $this->timestampToString($this->originateTimestamp))
                .  "\r\n";
        
        // Receive timestamp
        $string .= '  ReceiveTS='
                .  sprintf('%-24s', $this->timestampToString($this->receiveTimestamp))
                .  '    ';
        
        // Transmit timestamp
        $string .= ' TransmitTS='
                .  sprintf('%-24s', $this->timestampToString($this->transmitTimestamp))
                .  "\r\n";
        
        // Authentication: Key identifier & Message digest
        if ($this->_authenticated) {
            $string .= 'Key identifier=' . sprintf('%08x', $this->keyIdentifier)
                    .  ', Message digest=' . sprintf('%032x', $this->messageDigest)
                    .  "\r\n";
        }
        
        return $string;
    }
    
    /**
     * Returns a string representation of an SNTP timestamp.
     * 
     * The date()-function format is 'Y-m-d H:i:s', for the integer part.
     * The fraction part is sprintf()-formatted with '%04d'.
     * For example: "2006-08-02 08:39:04.8996".
     * 
     * If the timestamp is null, it will return the string "NULL".
     * 
     * @param integer $sTimestamp the timestamp to convert
     * 
     * @return string the timestamp as a string
     * 
     * @since 1.0
     */
    private function timestampToString($sTimestamp)
    {
        // If the timestamp is null, returns "NULL"
        if ($sTimestamp == 0) {
            return 'NULL';
        } else {
            // Converts the SNTP timestamp to a Unix timestamp
            $uTimestamp = NTPLite::convertTsSntpToUnix($sTimestamp);
            
            // Separates the integer part (seconds) and the fraction part (milliseconds)
            $integerPart = floor($uTimestamp);
            $fractionPart = round(($uTimestamp - $integerPart) * 10000);
            
            // Timestamps are UTC, so use gmdate
            return gmdate('Y-m-d H:i:s', $integerPart) . '.'
                 . sprintf('%04d', $fractionPart) . '';
        }
    }
    
    /**
     * Dumps the internal message on the standard output in hexadecimal.
     * 
     * @since 1.4
     */
    public function dump()
    {
        for ($i = 0; $i < $this->_messageSize; $i++)
            printf('%02x', $this->_messageBytes[$i]);
    }
}
?>