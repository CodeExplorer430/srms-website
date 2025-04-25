<?php
$menu_items = get_navigation_menu();
?>
<ul class="menu-link">
    <?php foreach($menu_items as $item): ?>
    <li>
        <a href="<?php echo $item['url']; ?>" class="sub-menu-link"><?php echo $item['name']; ?></a>
        <?php if(!empty($item['children'])): ?>
        <ul class="drop-down">
            <?php foreach($item['children'] as $child): ?>
            <li><a href="<?php echo $child['url']; ?>"><?php echo $child['name']; ?></a></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </li>
    <?php endforeach; ?>
</ul>