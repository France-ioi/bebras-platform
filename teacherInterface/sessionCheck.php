<?php

// server should keep session data for at least 30 secs
ini_set('session.gc_maxlifetime', 30);
// client should remember their session id for 30 secs
session_set_cookie_params(30);

session_name('coordinateur2');
session_start();

echo ( isset($_SESSION["userType"]) ? "1" : "0" );

?>
