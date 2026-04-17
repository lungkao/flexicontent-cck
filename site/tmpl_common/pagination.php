<?php if ($this->params->get('show_pagination', 2) != 0) : ?>

<nav class="fc-pagination-nav" aria-label="<?php echo \Joomla\CMS\Language\Text::_('JPAGER'); ?>">
	<ul class="pagination">
		<?php if ($this->params->get('show_pagination_results', 1)) : ?>
		<li class="pagination-counter"><span class="counter"><?php echo $this->pageNav->getPagesCounter(); ?></span></li>
		<?php endif; ?>
		<?php echo $this->pageNav->getPagesLinks(); ?>
	</ul>
</nav>

<?php endif; ?>
