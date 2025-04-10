<div class="navbar__inner navbar__inner--admin">
    <?php foreach ($categories as $key => $category) {
        if ($admin && isset($category['permission']) && in_array('admin', $category['permission']) && $key !== 'admin') {
    ?>
            <div class="navbar_item">
                <?php echo "<a href='" . $category['url'] . "'>" . $category['label'] . "</a>" ?>
            </div>
    <?php }
    } ?>
</div>