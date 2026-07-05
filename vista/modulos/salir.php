<?php
// Destruimos la sesión actual
session_destroy();

// Redirigimos mediante JavaScript al index para limpiar la URL
echo '<script>
    window.location = "index.php";
</script>';
?>