<?php
/**
 * FLEXIcontent — YOOtheme Grid Template: Item View
 *
 * Wraps the standard modular item layout with UIkit 3 container + card,
 * and adds an optional image/content grid via uk-grid on wider viewports.
 *
 * Strategy: wrap, don't copy modular.php — avoids maintenance debt when
 * the core item layout changes.
 */
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\WebAsset\WebAssetManager;

/** @var \Joomla\CMS\Document\HtmlDocument $doc */
$doc = Factory::getDocument();

/* Inject UIkit-scoped item styles (only for this template) */
$doc->getWebAssetManager()->addInlineStyle('
/* ── FLEXIcontent yootheme_grid: item view ───────────────────────── */
#fc-yootheme-item-wrap {
  --fc-item-r: 8px;
}

/* Card wrapper */
#fc-yootheme-item-wrap .fc-item-card {
  background: #fff;
  border-radius: var(--fc-item-r);
  box-shadow: 0 2px 12px rgba(0,0,0,.08);
  padding: 2rem;
}

/* Article typography — mirrors uk-article */
#fc-yootheme-item-wrap #flexicontent {
  line-height: 1.6;
  font-size: 1rem;
}
#fc-yootheme-item-wrap #flexicontent h1,
#fc-yootheme-item-wrap #flexicontent h2 {
  font-weight: 700;
  line-height: 1.3;
  margin-top: 0;
}
#fc-yootheme-item-wrap #flexicontent p {
  margin-bottom: 1rem;
}

/* Featured image: full-width above content, rounded top corners */
#fc-yootheme-item-wrap .image_featured,
#fc-yootheme-item-wrap .flexi.image {
  margin: -2rem -2rem 1.5rem;
  border-radius: var(--fc-item-r) var(--fc-item-r) 0 0;
  overflow: hidden;
}
#fc-yootheme-item-wrap .image_featured img,
#fc-yootheme-item-wrap .flexi.image img {
  width: 100%;
  height: auto;
  display: block;
  object-fit: cover;
  aspect-ratio: 16 / 7;
}

/* Buttons row — uk-button style */
#fc-yootheme-item-wrap .buttons {
  display: flex;
  flex-wrap: wrap;
  gap: .5rem;
  margin-bottom: 1rem;
}

/* Metadata blocks */
#fc-yootheme-item-wrap .flexi.lineinfo {
  font-size: .875rem;
  color: #64748b;
  margin-bottom: .5rem;
}

/* uk-grid: image left + content right on desktop when .fc-item-has-image */
@media (min-width: 960px) {
  #fc-yootheme-item-wrap.fc-item-has-image .fc-item-grid {
    display: grid;
    grid-template-columns: 2fr 3fr;
    gap: 2rem;
    align-items: start;
  }
  #fc-yootheme-item-wrap.fc-item-has-image .image_featured,
  #fc-yootheme-item-wrap.fc-item-has-image .flexi.image {
    margin: 0;
    border-radius: var(--fc-item-r);
  }
  #fc-yootheme-item-wrap.fc-item-has-image .image_featured img,
  #fc-yootheme-item-wrap.fc-item-has-image .flexi.image img {
    aspect-ratio: 4 / 3;
    border-radius: var(--fc-item-r);
  }
}
', 'fc-yootheme-item');

/* Detect if item has a featured image (for grid layout class) */
$hasFeatImg = isset($this->item) && !empty($this->item->fields['image']->display ?? '');
$wrapClass  = 'uk-container uk-container-small uk-margin-top uk-margin-bottom'
            . ($hasFeatImg ? ' fc-item-has-image' : '');
?>

<div id="fc-yootheme-item-wrap" class="<?php echo $wrapClass; ?>" uk-scrollspy="target: .fc-item-card; cls: uk-animation-fade; delay: 100">

  <div class="fc-item-card">
    <div class="fc-item-grid">

      <?php
      /**
       * Include core modular layout unchanged.
       * All Joomla event plugins, edit buttons, and field rendering
       * are handled by modular.php — we only add the UIkit wrapper.
       */
      include(JPATH_SITE . DS . 'components' . DS . 'com_flexicontent'
            . DS . 'tmpl_common' . DS . 'item_layouts' . DS . 'modular.php');
      ?>

    </div><!-- /.fc-item-grid -->
  </div><!-- /.fc-item-card -->

</div><!-- /#fc-yootheme-item-wrap -->
