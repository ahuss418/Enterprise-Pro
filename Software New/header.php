<?php

$successMessage = flash('success');
$warningMessage = flash('warning');
$errorMessage = flash('error');

?>

<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?> | Bradford Council</title>

    <link href='https://fonts.googleapis.com/css?family=Roboto:400,300,500,700,500italic,400italic,300italic,700italic,900,900italic' rel='stylesheet' type='text/css' />
    <link rel="stylesheet" href="/static/main.css">
    <link rel="icon" type="image/png" href="/static/favicon.png">

</head>


<body>
    <div class="wrapper">
        <header class="header">
            <div class="header__section header__section--top">
                <div class="header__container">
                    <div class="header__inner">
                        <div class="header__brand">
                            <img src="/static/images/logo.png" alt="Bradford Council">
                        </div>
                        <div class="dropdown-content">
                            <button onclick="dropNav()" class="drop_menu">
                                <img src="/static/images/DD_menu.png">
                            </button>
                        </div>
                        <div class="header__links">
                            <?php if ($loggedInUser) { ?>
                                <a href="/logout.php" style="color: #005192; text-decoration: none;">Sign Out</a>
                            <?php } else { ?>
                                <a href="/login.php" style="color: #005192; text-decoration: none;">Sign In</a>
                            <?php } ?>
                            <div class="header__search">
                                <!-- TODO Make search do something -->
                                <form action="#" method="GET">
                                    <div class="input input--search">
                                        <input type="text" class="input__control" name="query" placeholder="Search this site" value="<?php echo htmlspecialchars($_GET['query'] ?? '') ?>" autocomplete="off">
                                        <button type="submit" class="input__button">
                                            <i class="la la-search"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
        </header>
        <?php
        $admin = isset($loggedInUser) ? $loggedInUser["admin"] : false;
        $verified = isset($loggedInUser) ? $loggedInUser["verified"] : false;
        ?>
        <!-- Desktop Navbar -->
        <navbar class="navbar">
            <div class="navbar__container">
                <div class="navbar__inner">
                    <?php foreach ($categories as $key => $category) {
                        if (
                            !isset($category['hidden']) || $category['hidden'] === false // Skip hidden categories
                        ) {
                            if (
                                !isset($category['permission']) ||
                                (isset($category['permission']) && (
                                    (in_array('admin', $category['permission']) && $admin) ||
                                    (in_array('verified', $category['permission']) && $verified)
                                ))
                            ) {
                    ?>
                                <div class="navbar_item">
                                    <a href="<?php echo $category['url']; ?>">
                                        <?php echo htmlspecialchars($category['label']); ?>
                                    </a>
                                </div>
                    <?php
                            }
                        }
                    } ?>
                </div>
            </div>
        </navbar>

        <!-- Mobile Responsiveness -->
        <navbar>
            <div class="dropNav">
                <?php foreach ($categories as $key => $category) {
                    if (!isset($category['hidden']) || $category['hidden'] === false) { ?>
                        <div class="dropbar_item">
                            <a href="<?php echo $category['url']; ?>">
                                <?php echo htmlspecialchars($category['label']); ?>
                            </a>
                        </div>
                <?php }
                } ?>
            </div>
        </navbar>

        <main class="main">
            <div class="main__container">
                <h1 class="heading-left"><?php echo $title ?></h1>
                <?php if ($successMessage) { ?>
                    <div class="block block--success">
                        <div class="block__body">
                            <?php echo $successMessage ?>
                        </div>
                    </div>
                <?php } ?>
                <?php if ($errorMessage) { ?>
                    <div class="block block--danger">
                        <div class="block__body">
                            <?php echo $errorMessage ?>
                        </div>
                    </div>
                <?php } ?>
                <?php if ($warningMessage) { ?>
                    <div class="block block--warning">
                        <div class="block__body">
                            <?php echo $warningMessage ?>
                        </div>
                    </div>
                <?php } ?>