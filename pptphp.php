<?php

include_once( "PPTClient.php" ) ;

$client = new PPTClient() ;
$result = $client->initTCPConnection( "localhost", 10022, 0 ) ;
if( $result != null )
{
    echo $result ;
    $client->closeConnection() ;
}
else
{
    echo "All is well\n" ;
    $cmd = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<request reqID=\"some_unique_value\" >
    <showVersion />
</request>
" ;
    $result = $client->sendRequest( $cmd ) ;
    if( $result != null )
    {
        echo $result ;
    }
    else
    {
        $data = "" ;
        $extensions = array() ;
        $result = $client->receiveResponse( $data, $extensions ) ;
        if( $result != null )
        {
            echo $result ;
        }
    }
    $client->closeConnection() ;
}
?>
