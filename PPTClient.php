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

    private function initConnection()
    {
        print( "initConnection\n" ) ;
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

    public function initTCPConnection( $host, $port, $timeout )
    {
        print( "initTCPConnection\n" ) ;
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

    public function initUnixCPConnection( $unixsocket, $timeout )
    {
        print( "initUnixConnection\n" ) ;
    }

    public function closeConnection()
    {
        print( "closeConnection\n" ) ;
        if( $this->socket != null )
        {
            $result = $this->sendExit() ;
            socket_close( $this->socket ) ;
        }
    }
}

?>

