<?php
    require_once 'utilclass.php';
        
    $utils = new UtilClass;

    $utils->updateSpreadSheet();
    $message = $utils->messageMaker();
    $utils->speakGoogleHome($message);
?>