<?php
require_once('includes/setup.php');

$page = 'settings';
$title = 'Settings';

require_once('includes/header.php');
?>

<div class="block">
    <div class="block__body">
        <div class="block__title">Accessibility Settings</div>

        <!-- Font Size Adjustment -->
        <div class="input">
            <label for="font-size" class="input__label">Font Size</label>
            <select id="font-size" class="input__control">
                <option value="14px">Small</option>
                <option value="16px" selected>Default</option>
                <option value="18px">Large</option>
                <option value="20px">Extra Large</option>
            </select>
        </div>

        <!-- Light/Dark Mode Switch -->
        <div class="input">
            <label class="input__label">Theme</label>
            <div class="theme-toggle">
                <button class="button button--primary" onclick="setTheme('light')">Light Mode</button>
                <button class="button button--dark" onclick="setTheme('dark')">Dark Mode</button>
            </div>
        </div>
    </div>
</div>

<?php
require_once('includes/footer.php');
?>