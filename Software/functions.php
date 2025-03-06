<?php
function redirect($path)
{
    header('Location: ' . $path);
    die();
}

function redirectToReferer()
{
    redirect($_SERVER['HTTP_REFERER']);
}



function flash($name, $message = null)
{
    if (!$message) {
        $message = $_SESSION['flash'][$name] ?? null;
        unset($_SESSION['flash'][$name]);
        return $message;
    }

    $_SESSION['flash'][$name] = $message;
}
