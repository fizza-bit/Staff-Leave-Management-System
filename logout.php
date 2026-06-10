<?php
session_start();
session_destroy();
header("Location: index.html"); // Redirects to the new landing page
?>