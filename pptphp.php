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
    <showContainers />
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
        $done = false ;
        while( !$done )
        {
            $extensions = array() ;
            $result = $client->receiveChunk( $data, $extensions ) ;
            if( $result != null )
            {
                if( $result == "done" )
                {
                    $result = null ;
                    $done = true ;
                }
                else
                {
                    print( "$result\n" ) ;
                    break ;
                }
            }
            else
            {
                print( "$data" ) ;
            }
        }
    }
    $client->closeConnection() ;
}
?>
