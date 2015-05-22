<?php

include_once( "PPTConnection.php" ) ;

class PPTClient extends PPTConnection
{
    function __construct()
    {
    }

    function __destruct()
    {
    }

    /** initialize a connection to the OPeNDAP BES
     *
     * Whether tcp or udp this initializes the connection to the OPeNDAP
     * Back-End Server using the ppt handshake.
     *
     * @return null if succcessful otherwise an error message
     */
    private function initConnection()
    {
        $result = parent::send( "PPTCLIENT_TESTING_CONNECTION" ) ;
        if( $result !== null )
        {
            return $result ;
        }
        $data = "" ;
        $result = parent::receive( $data, 64 ) ;
        if( $result != null )
        {
            $msg = "handshake failed: received $result\n" ;
            return $msg ;
        }
        if( $data != "PPTSERVER_CONNECTION_OK" )
        {
            $msg = "handshake failed: received $data\n" ;
            return $msg ;
        }
        return null ;
    }

    /** initialize a tcp connection to the OPeNDAP BES
    *
    * @param string host hostname where the OPeNDAP BES is running
    * @param int port tcp port the OPeNDAP BES is listening on
    * @param int timeout number of seconds to stop trying to connect
    * @return null if successful otherwise an error message
    */
    public function initTCPConnection( $host, $port, $timeout )
    {
        $this->socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP ) ;
        if( $this->socket === false )
        {
            $msg = "socket_create() failed: reason: "
                   . socket_strerror( socket_last_error() ) . "\n" ;
            return $msg ;
        }
        $result = socket_connect( $this->socket, $host, $port ) ;
        if( $result === false )
        {
            $msg = "socket_connect() failed: reason: "
                   .  socket_strerror( socket_last_error() ) . "\n" ;
            return $msg ;
        }

        return $this->initConnection() ;
    }

    /** initialize a local udp connection to the OPeNDAP BES
     *
     * Not yet implemented
     *
     * @param string unixsocket path to the udp socket to connect to
     * @param int timeout number of seconds to stop trying to connect
     * @return null if successful otherwise an error message
     */
    public function initUnixCPConnection( $unixsocket, $timeout )
    {
        return "Not yet imlemented" ;
    }

    /** close the connection to the OPeNDAP BES
     *
     * Closes the connection by sending the exit extension chunk
     *
     */
    public function closeConnection()
    {
        if( $this->socket != null )
        {
            $result = $this->sendExit() ;
            socket_close( $this->socket ) ;
        }
    }
}

?>
