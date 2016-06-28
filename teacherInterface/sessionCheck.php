<?php

// server should keep session data for at least one hour
ini_set('session.gc_maxlifetime', 60*60);
// client should remember their session id for one hour
session_set_cookie_params(60*60);

session_name('coordinateur2');
session_start();

echo ( isset($_SESSION["userType"]) ? "1" : "0" );

?>
