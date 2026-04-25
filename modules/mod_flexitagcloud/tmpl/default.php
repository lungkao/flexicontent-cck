<?php // no direct access
defined('_JEXEC') or die('Restricted access'); 

?>
<ul id="flexicloud" class="mod_flexitagcloud<?php echo $params->get('moduleclass_sfx'); ?>">
<?php foreach ($list as $item) : ?>
	<li>
		<?php if (!$params->get('seo_mode', 1)) : ?>
		<span><?php echo $item->screenreader.' '; ?></span>
		<?php endif; ?>
		<a href="<?php echo htmlspecialchars($item->link, ENT_COMPAT, 'UTF-8'); ?>" class="tag<?php echo (int)$item->size; ?>"
			<?php echo (!$item->description ? '' : ' title="' . htmlspecialchars(flexicontent_html::striptagsandcut($item->description, 200), ENT_COMPAT, 'UTF-8')) . '" ';?>
		><?php echo htmlspecialchars($item->name, ENT_COMPAT, 'UTF-8'); ?></a>
	</li>
<?php endforeach; ?>
</ul>