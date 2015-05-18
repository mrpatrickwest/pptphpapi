<?php

class PPTConnection
{
    protected $socket = null ;

    function __construct( $timeout )
    {
    }

    function __destruct()
    {
    }

    /** write the specifed buffer to the socket
     *
     * @param string buffer buffer to send
     * @return string null if successful otherwise an error message
     */
    public function send( $buffer )
    {
        print( "send $buffer\n" ) ;
        $result = socket_write( $this->socket, $buffer, strlen( $buffer ) ) ;
        if( $result === false )
        {
            $msg = "socket_write() failed: reason: "
                   . socket_strerror( socket_last_error() ) . "\n" ;
            return $msg ;
        }
        return null ;
    }

    /** send the message building the header and sending the data or extensions
     *
     * @param string buffer the data to send, null if end of message
     * @param array extensions key value pairs to send to the server
     * @return string null if successful otherwise an error message
     */
    private function sendChunk( $buffer, $extensions )
    {
        if( $extensions != null )
        {
            $this->sendExtensions( $extensions ) ;
        }
        $len = 0 ;
        if( $buffer != null ) $len = strlen( $buffer ) ;
        $header = sprintf( "%'07x", $len ) . "d" ;
        if( $buffer != null )
        {
            $msg = $header . $buffer ;
        }
        else
        {
            $msg = $header ;
        }
        return $this->send( $msg ) ;
    }

    public function sendWithExtensions( $buffer, $extensions )
    {
        print( "sendWithExtensions\n" ) ;
        $this->sendChunk( $buffer, $extensions ) ;
        if( $buffer != null )
        {
            $this->sendChunk( null, null ) ;
        }
    }

    /** send just the extensions to the server
     *
     * This function sends just the extensions to the server. It
     * builds the msg to look like "key1=value1;key2;". Each extension
     * ends with a semicolon (;).
     *
     * First builds the header, first 7 hex of length, then x for
     * extensions.
     *
     * @param array extensions array of key value pairs to send
     * @return string null if successful otherwise error message
     */
    public function sendExtensions( $extensions )
    {
        print( "sendExtensions\n" ) ;
        $ext = "" ;
        foreach( $extensions as $key => $value )
        {
            $ext .= $key ;
            if( $value != null )
            {
                $ext .= "=" . $value ;
            }
            $ext .= ";" ;
        }
        $header = sprintf( "%'07x", strlen( $ext ) ) . "x" ;
        $msg = $header . $ext ;
        $this->send( $msg ) ;
    }

    /** called by the client to send a BES request
     *
     * @param string request the BES request xml document
     * @return string null if successful otherwise an error message
     */
    public function sendRequest( $request )
    {
        return $this->sendWithExtensions( $request, null ) ;
    }

    /** sends the exit message to the server terminating the connection
     *
     * The message is empty and the extension is status=PPT_EXIT_NOW
     *
     * @return string null if successful otherwise an error message
     */
    public function sendExit()
    {
        print( "sendExit\n" ) ;
        $extensions = array( "status" => "PPT_EXIT_NOW" ) ;
        return $this->sendWithExtensions( null, $extensions ) ;
    }

    /** receive extensions from the server
     *
     * We already know the message contains extensions and the length of
     * the message. The string received from the server is like
     * "key1=value1;key2;".
     *
     * @param array extensions an out array to store the extensions
     * @param int len the length of the message to read from the server
     * @return string null if successful otherwise an error message
     */
    private function receiveExtensions( &$extensions, $len )
    {
        $extstr = "" ;
        $result = $this->receive( $extstr, $len ) ;
        if( $result != null )
        {
            return $result ;
        }
        if( strlen( $extstr ) != $len )
        {
            $msg = "failed to read extensions" ;
            return $msg ;
        }
        $ext = explode( $extstr, ";" ) ;
        for( $e = 0; $e < count( $ext ); $e++ )
        {
            $pos = strpos( $ext[$e], "=" ) ;
            if( $pos )
            {
                $key = substr( $ext[$e], 0, $pos ) ;
                $value = substr( $ext[$e], $pos+1,
                                 strlen( $ext[$e] ) - $pos - 1 ) ;
            }
            else
            {
                $key = $ext[$e] ;
                $value = null ;
            }
            $extensions[$key] = $value ;
        }
        return null ;
    }

    /** receive data from the server
     *
     * We already know that it's data and we already know the length.
     * This information was read from the header of the message.
     *
     * @param string data out variable containing the data read from the
     * server
     * @param int len the length of the data to read from the server
     * @return string null if successful otherwise an error string
     */
    private function receiveData( &$data, $len )
    {
        return $this->receive( $data, $len ) ;
    }

    /** receive a response to a BES request
     *
     * continues to read from the server until the terminating header is
     * received.
     * @param data string out variable to store the data read from BES
     * @param array extensions out variable to store any extensions sent
     * by the server.
     * @return string null if successful otherwise an error message.
     */
    public function receiveResponse( &$data, &$extensions )
    {
        $done = false ;
        while( !$done )
        {
            $result = $this->receiveChunk( $data, $extensions ) ;
            if( $result != null )
            {
                if( $result == "done" )
                {
                    $result = null ;
                    $done = true ;
                }
                else
                {
                    break ;
                }
            }
        }
        return $result ;
    }

    /** read a chunk of data from the server
     *
     * A response could contain a set of extensions and then more data.
     Keep reading until the terminating header is received.
     *
     * @param string data out variable to store the data read from BES
     * @param array extensions out variable to store any extensions sent
     * by the server
     * @return string "done" when read the terminating header, null to
     * continue reading, and a string if an error message
     */
    private function receiveChunk( &$data, &$extensions )
    {
        $header = "" ;
        $result = $this->receive( $header, 8 ) ;
        if( $result != null )
        {
            return $result ;
        }
        if( strlen( $header ) != 8 )
        {
            $msg = "bad header" ;
            return $msg ;
        }
        $lenstr = substr( $header, 0, 7 ) ;
        $len = hexdec( $lenstr ) ;
        $type = substr( $header, 7, 1 ) ;
        if( $len == 0 )
        {
            $result = "done" ;
        }
        else if( $type == "x" )
        {
            $result = $this->receiveExtensions( $extensions, $len ) ;
        }
        else if( $type == "d" )
        {
            $result = $this->receiveData( $data, $len ) ;
        }
        else
        {
            $result = "Bad type" ;
        }
        return $result ;
    }

    /** read data from the socket
     *
     * @param string data out variable to store the data read
     * @param int len amount of data to read
     * @return string null if successful otherwise an error message
     */
    public function receive( &$data, $len )
    {
        print( "receive\n" ) ;
        $data = socket_read( $this->socket, $len ) ;
        if( $data === false )
        {
            $msg = "socket_read() failed: reason: "
                   .  socket_strerror( socket_last_error() ) . "\n" ;
            return $msg ;
        }
        echo "received $data\n" ;
        return null ;
    }
}

?>
