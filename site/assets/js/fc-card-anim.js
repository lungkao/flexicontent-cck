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

  /* ── 2. CSS :has() grid fallback (Firefox < 121, older browsers) ─ */
  /*
   * Replicates these CSS :has() patterns that control grid layout:
   *   flexi_frontend_modern.css  — 22 occurrences
   *
   * Patterns covered:
   *   A) .featured-block.fc-items-block — total=2 (1fr 1fr)
   *                                      — total=3 (magazine or 3-col by style)
   *                                      — total=4/5 (3-col or 4-col by style)
   *   B) #fc-yootheme-grid .fc-featured-section — same totals, UIkit context
   *   C) article:has(figure) header.tool — border-radius cosmetic fix
   *   D) innerbox:not(:has(figure))      — mark for no-image CSS class
   */
  function applyGridFallback(){
    if(window.CSS && CSS.supports && CSS.supports('selector(:has(*))')) return;

    var vw = window.innerWidth;
    var isMobile = vw <= 640;
    var isTablet = vw <= 768;

    /* ── A. Grid template: .featured-block.fc-items-block ──────── */
    document.querySelectorAll('.featured-block.fc-items-block').forEach(function(block){
      var wrapper = block.querySelector('[data-total]');
      if(!wrapper) return;
      var total = parseInt(wrapper.dataset.total, 10);
      if(!total || total < 2) return;

      var isOverlay = block.classList.contains('fc-feat-style-overlay');
      var isMinimal = block.classList.contains('fc-feat-style-minimal');
      var first = block.querySelector('[data-index="0"]');

      /* Reset previous inline overrides */
      if(first){ first.style.gridColumn = ''; first.style.gridRow = ''; }

      /* Mobile (≤640px): single column — matches @media(max-width:640px) */
      if(isMobile){
        block.style.display = 'grid';
        block.style.gridTemplateColumns = '1fr';
        block.querySelectorAll('[data-index]').forEach(function(w){
          w.style.gridColumn = ''; w.style.gridRow = '';
        });
        return;
      }

      block.style.display = 'grid';

      if(total === 2){
        /* All styles: 2-col side-by-side */
        block.style.gridTemplateColumns = '1fr 1fr';
        /* No first-item span for total=2 */

      }else if(total === 3){
        if(isOverlay){
          /* overlay: 3-col desktop, 2-col tablet (L1388 + @media768 !important L1736) */
          block.style.gridTemplateColumns = isTablet ? '1fr 1fr' : 'repeat(3,1fr)';
          /* overlay resets first-item span (L1395-1398) */
          if(first){ first.style.gridColumn = 'unset'; first.style.gridRow = 'unset'; }
        }else if(isMinimal){
          /* minimal: 3-col desktop, 2-col tablet (L1423 + @media768 !important L1736) */
          block.style.gridTemplateColumns = isTablet ? '1fr 1fr' : 'repeat(3,1fr)';
        }else{
          /* hero/default: magazine — first spans full row (L1225 + L898-904) */
          block.style.gridTemplateColumns = '1fr 1fr';
          if(first) first.style.gridColumn = '1/-1';
        }

      }else if(total === 4 || total === 5){
        if(isOverlay){
          /* overlay: 4-col desktop, 2-col tablet (L1391 + @media768 !important L1737) */
          block.style.gridTemplateColumns = isTablet ? '1fr 1fr' : 'repeat(4,1fr)';
          if(first){ first.style.gridColumn = 'unset'; first.style.gridRow = 'unset'; }
        }else if(isMinimal){
          /* minimal: 4-col desktop, 2-col tablet (L1426 + @media768 !important L1737) */
          block.style.gridTemplateColumns = isTablet ? '1fr 1fr' : 'repeat(4,1fr)';
        }else{
          /* hero/default: repeat(3,1fr), first spans all (L1228 + L933) */
          block.style.gridTemplateColumns = isTablet ? '1fr 1fr' : 'repeat(3,1fr)';
          if(first) first.style.gridColumn = '1/-1';
        }
      }

      /* ── C. article:has(figure) header.tool — cosmetic fix ───── */
      block.querySelectorAll('article.fc-item-featured, article.fc-item-standard')
        .forEach(function(art){
          if(art.querySelector('figure')){
            var hdr = art.querySelector('header.tool');
            if(hdr) hdr.style.borderRadius = 'var(--r,8px) var(--r,8px) 0 0';
          }
        });

      /* ── D. innerbox:not(:has(figure)) — add helper class ────── */
      block.querySelectorAll('.fc-item-block-featured-wrapper-innerbox')
        .forEach(function(box){
          if(!box.querySelector('figure')) box.classList.add('fc-no-image');
          else box.classList.remove('fc-no-image');
        });
    });

    /* ── B. YOOtheme grid: #fc-yootheme-grid .fc-featured-section ─ */
    document.querySelectorAll('#fc-yootheme-grid .fc-featured-section')
      .forEach(function(section){
        var wrapper = section.querySelector('[data-total]');
        if(!wrapper) return;
        var total = parseInt(wrapper.dataset.total, 10);
        if(!total || total < 2) return;

        var first = section.querySelector('[data-index="0"]');
        if(first){ first.style.gridColumn = ''; first.style.gridRow = ''; }

        /* Mobile: 1 column */
        if(isMobile){
          section.style.gridTemplateColumns = '1fr';
          section.querySelectorAll('[data-index]').forEach(function(w){
            w.style.gridColumn = ''; w.style.gridRow = '';
          });
          return;
        }

        if(total === 2){
          /* L234 */
          section.style.gridTemplateColumns = '1fr 1fr';

        }else if(total === 3){
          /* L237 + L243-245 */
          section.style.gridTemplateColumns = '1fr 1fr';
          if(isTablet){
            /* L401-408: tablet resets first to full-width */
            if(first){ first.style.gridRow = 'auto'; first.style.gridColumn = '1/-1'; }
            section.querySelectorAll('[data-index="1"],[data-index="2"]')
              .forEach(function(w){ w.style.gridColumn = 'unset'; });
          }else{
            /* L243-245: first=tall left col, 1+2 stacked right */
            if(first){ first.style.gridRow = '1/3'; first.style.gridColumn = '1'; }
            section.querySelectorAll('[data-index="1"],[data-index="2"]')
              .forEach(function(w){ w.style.gridColumn = '2'; });
          }

        }else if(total === 4){
          /* L240 + L246 */
          section.style.gridTemplateColumns = isTablet ? '1fr 1fr' : 'repeat(3,1fr)';
          if(first){
            first.style.gridColumn = '1/-1';
            if(isTablet) first.style.gridRow = 'auto';
          }
        }
      });
  }

  /* ── 3. Scroll animation via IntersectionObserver ─────────────── */
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

  /* ── Debounced resize handler ─────────────────────────────────── */
  var _resizeTimer;
  function onResize(){
    clearTimeout(_resizeTimer);
    _resizeTimer = setTimeout(applyGridFallback, 120);
  }

  /* ── Init ─────────────────────────────────────────────────────── */
  function init(){
    wrapHeroContent();
    applyGridFallback();
    initAnimations();
    window.addEventListener('resize', onResize);
  }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
