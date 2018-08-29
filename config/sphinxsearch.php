<?php
return array(
    'host'    => '127.0.0.1 ',
    'port'    => 9312,
    'timeout' => 30,
    'indexes' => array(
        env( 'SPHINXSEARCH_INDEX' ) => array ( 'table' => 'products', 'column' => 'id' ),
    )
);