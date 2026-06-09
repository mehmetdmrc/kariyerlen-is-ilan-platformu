<?php
session_name('kariyer_admin');
    session_start();
session_destroy();
header("Location: ../giris.php");
exit;
?>
