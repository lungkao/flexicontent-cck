/* FLEXIcontent Card Scroll Animations + Hero content wrapper */
(function(){
  'use strict';

  /* ── 1. Wrap hero content in .fc-content-col ─────────────────── */
  /* Hero layout needs content in a flex column wrapper */
  function wrapHeroContent(){
    var blocks = document.querySelectorAll('.featured-block.fc-feat-style-hero');
    blocks.forEach(function(block){
      var wrappers = block.querySelectorAll('.fc-item-block-featured-wrapper-innerbox');
      wrappers.forEach(function(box){
        if(box.querySelector('.fc-content-col')) return;
        var figure = box.querySelector('figure.image_featured');
        var wrap = document.createElement('div');
        wrap.className = 'fc-content-col';
        var children = Array.prototype.slice.call(box.children);
        children.forEach(function(child){
          if(child !== figure && child.tagName !== 'HEADER') wrap.appendChild(child);
        });
        if(wrap.children.length > 0) box.appendChild(wrap);
      });
    });
  }

  /* ── 2. Scroll animation via IntersectionObserver ─────────────── */
  function initAnimations(){
    if(!window.IntersectionObserver) return;

    function setupBlock(block, animAttr){
      var anim = block.getAttribute(animAttr);
      if(!anim) return;
      var items = block.querySelectorAll(
        animAttr === 'data-feat-anim'
          ? '.fc-item-block-featured-wrapper'
          : '.fc-item-block-standard-wrapper'
      );
      items.forEach(function(item, i){
        item.classList.add('fc-anim-ready', 'fc-anim-'+anim);
        item.style.transitionDelay = Math.min(i * 80, 400) + 'ms';
      });

      var obs = new IntersectionObserver(function(entries){
        entries.forEach(function(entry){
          if(entry.isIntersecting){
            entry.target.classList.add('fc-anim-visible');
            obs.unobserve(entry.target);
          }
        });
      }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

      items.forEach(function(item){ obs.observe(item); });
    }

    document.querySelectorAll('.featured-block.fc-items-block[data-feat-anim]')
      .forEach(function(b){ setupBlock(b, 'data-feat-anim'); });
    document.querySelectorAll('.standard-block.fc-items-block[data-std-anim]')
      .forEach(function(b){ setupBlock(b, 'data-std-anim'); });
  }

  /* ── Init ─────────────────────────────────────────────────────── */
  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', function(){
      wrapHeroContent();
      initAnimations();
    });
  } else {
    wrapHeroContent();
    initAnimations();
  }
})();
