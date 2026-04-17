/**
 * fc-card-anim.test.js
 *
 * Tests for applyGridFallback() in site/assets/js/fc-card-anim.js
 *
 * Run:
 *   cd tests/js
 *   npx jest fc-card-anim.test.js --testEnvironment jsdom
 *
 * Or from repo root:
 *   npx jest tests/js/fc-card-anim.test.js
 */

'use strict';

/* ── Helpers ──────────────────────────────────────────────────────── */

/**
 * Build a .featured-block.fc-items-block DOM element with n wrappers.
 *
 * @param {number}  total   Number of wrappers (= data-total value)
 * @param {string}  style   'hero' | 'overlay' | 'minimal'
 * @param {boolean} withImg Whether each wrapper contains a <figure>
 */
function makeFeaturedBlock(total, style = 'hero', withImg = true) {
  const block = document.createElement('div');
  block.className = `featured-block fc-items-block fc-feat-style-${style}`;

  for (let i = 0; i < total; i++) {
    const wrapper = document.createElement('div');
    wrapper.className = 'fc-item-block-featured-wrapper';
    wrapper.dataset.index = String(i);
    wrapper.dataset.total = String(total);

    const innerbox = document.createElement('div');
    innerbox.className = 'fc-item-block-featured-wrapper-innerbox';

    const article = document.createElement('article');
    article.className = 'fc-item-featured';

    if (withImg) {
      const figure = document.createElement('figure');
      figure.className = 'image_featured';
      article.appendChild(figure);
    }

    const header = document.createElement('header');
    header.className = 'tool';
    article.appendChild(header);

    innerbox.appendChild(article);
    wrapper.appendChild(innerbox);
    block.appendChild(wrapper);
  }

  return block;
}

/**
 * Build a #fc-yootheme-grid .fc-featured-section element.
 */
function makeYoothemeSection(total) {
  const root = document.createElement('div');
  root.id = 'fc-yootheme-grid';

  const section = document.createElement('div');
  section.className = 'fc-featured-section';

  for (let i = 0; i < total; i++) {
    const wrapper = document.createElement('div');
    wrapper.className = 'fc-feat-wrapper';
    wrapper.dataset.index = String(i);
    wrapper.dataset.total = String(total);
    section.appendChild(wrapper);
  }

  root.appendChild(section);
  return { root, section };
}

/* ── Mock CSS.supports ────────────────────────────────────────────── */

function disableHasSupport() {
  Object.defineProperty(window, 'CSS', {
    value: { supports: () => false },
    writable: true, configurable: true,
  });
}

function enableHasSupport() {
  Object.defineProperty(window, 'CSS', {
    value: { supports: () => true },
    writable: true, configurable: true,
  });
}

/* ── Load the module ─────────────────────────────────────────────── */

let applyGridFallback;

beforeAll(() => {
  /* Expose internal function for testing by injecting a test hook */
  global.__fcTestExports = {};
  /* We load the source and extract applyGridFallback via a patched copy */
  const fs = require('fs');
  const path = require('path');
  const src = fs.readFileSync(
    path.resolve(__dirname, '../../site/assets/js/fc-card-anim.js'),
    'utf8'
  );
  /* Wrap the IIFE to capture applyGridFallback before it closes */
  const patched = src.replace(
    'function applyGridFallback()',
    'global.__fcTestExports.applyGridFallback = applyGridFallback; function applyGridFallback()'
  );
  // eslint-disable-next-line no-new-func
  new Function('global', 'document', 'window', patched)(global, document, window);
  applyGridFallback = global.__fcTestExports.applyGridFallback;
});

beforeEach(() => {
  document.body.innerHTML = '';
  disableHasSupport(); /* default: :has() NOT supported → fallback should run */
  /* Default desktop viewport */
  Object.defineProperty(window, 'innerWidth', { value: 1280, writable: true, configurable: true });
});

/* ═══════════════════════════════════════════════════════════════════
   Guard: CSS.supports(:has(*)) → skip fallback
   ═══════════════════════════════════════════════════════════════════ */

test('does nothing when CSS :has() is natively supported', () => {
  enableHasSupport();
  const block = makeFeaturedBlock(3, 'hero');
  document.body.appendChild(block);
  applyGridFallback();
  expect(block.style.gridTemplateColumns).toBe('');
});

/* ═══════════════════════════════════════════════════════════════════
   Desktop — .featured-block.fc-items-block
   ═══════════════════════════════════════════════════════════════════ */

describe('desktop (vw=1280)', () => {
  test.each([
    ['hero',    2, '1fr 1fr'],
    ['overlay', 2, '1fr 1fr'],
    ['minimal', 2, '1fr 1fr'],
  ])('%s total=%i → gridTemplateColumns=%s', (style, total, expected) => {
    const block = makeFeaturedBlock(total, style);
    document.body.appendChild(block);
    applyGridFallback();
    expect(block.style.gridTemplateColumns).toBe(expected);
  });

  test.each([
    ['hero',    3, '1fr 1fr'],
    ['overlay', 3, 'repeat(3,1fr)'],
    ['minimal', 3, 'repeat(3,1fr)'],
  ])('%s total=3 → gridTemplateColumns=%s', (style, total, expected) => {
    const block = makeFeaturedBlock(total, style);
    document.body.appendChild(block);
    applyGridFallback();
    expect(block.style.gridTemplateColumns).toBe(expected);
  });

  test.each([
    ['hero',    4, 'repeat(3,1fr)'],
    ['overlay', 4, 'repeat(4,1fr)'],
    ['minimal', 4, 'repeat(4,1fr)'],
  ])('%s total=4 → gridTemplateColumns=%s', (style, total, expected) => {
    const block = makeFeaturedBlock(total, style);
    document.body.appendChild(block);
    applyGridFallback();
    expect(block.style.gridTemplateColumns).toBe(expected);
  });

  test('sets display:grid on block', () => {
    const block = makeFeaturedBlock(3, 'hero');
    document.body.appendChild(block);
    applyGridFallback();
    expect(block.style.display).toBe('grid');
  });
});

/* ═══════════════════════════════════════════════════════════════════
   First-item grid-column spanning
   ═══════════════════════════════════════════════════════════════════ */

describe('first-item grid-column span', () => {
  test('hero total=3: first item spans 1/-1', () => {
    const block = makeFeaturedBlock(3, 'hero');
    document.body.appendChild(block);
    applyGridFallback();
    const first = block.querySelector('[data-index="0"]');
    expect(first.style.gridColumn).toBe('1/-1');
  });

  test('hero total=4: first item spans 1/-1', () => {
    const block = makeFeaturedBlock(4, 'hero');
    document.body.appendChild(block);
    applyGridFallback();
    const first = block.querySelector('[data-index="0"]');
    expect(first.style.gridColumn).toBe('1/-1');
  });

  test('hero total=2: first item does NOT span', () => {
    const block = makeFeaturedBlock(2, 'hero');
    document.body.appendChild(block);
    applyGridFallback();
    const first = block.querySelector('[data-index="0"]');
    expect(first.style.gridColumn).toBe('');
  });

  test('overlay total=3: first item is NOT spanned (overlay resets)', () => {
    const block = makeFeaturedBlock(3, 'overlay');
    document.body.appendChild(block);
    applyGridFallback();
    const first = block.querySelector('[data-index="0"]');
    expect(first.style.gridColumn).toBe('unset');
  });

  test('overlay total=4: first item is NOT spanned', () => {
    const block = makeFeaturedBlock(4, 'overlay');
    document.body.appendChild(block);
    applyGridFallback();
    const first = block.querySelector('[data-index="0"]');
    expect(first.style.gridColumn).toBe('unset');
  });
});

/* ═══════════════════════════════════════════════════════════════════
   Tablet (641 < vw ≤ 768)
   ═══════════════════════════════════════════════════════════════════ */

describe('tablet (vw=768)', () => {
  beforeEach(() => {
    Object.defineProperty(window, 'innerWidth', { value: 768, writable: true, configurable: true });
  });

  /* CSS @media(max-width:768px) uses !important → overrides ALL styles for total=3,4 */
  test.each([
    ['hero',    3, '1fr 1fr'],
    ['overlay', 3, '1fr 1fr'],
    ['minimal', 3, '1fr 1fr'],
    ['hero',    4, '1fr 1fr'],
    ['overlay', 4, '1fr 1fr'],
    ['minimal', 4, '1fr 1fr'],
  ])('%s total=%i collapses to 1fr 1fr at tablet', (style, total, expected) => {
    const block = makeFeaturedBlock(total, style);
    document.body.appendChild(block);
    applyGridFallback();
    expect(block.style.gridTemplateColumns).toBe(expected);
  });

  test('hero total=2 stays 1fr 1fr', () => {
    const block = makeFeaturedBlock(2, 'hero');
    document.body.appendChild(block);
    applyGridFallback();
    expect(block.style.gridTemplateColumns).toBe('1fr 1fr');
  });
});

/* ═══════════════════════════════════════════════════════════════════
   Mobile (vw ≤ 640)
   ═══════════════════════════════════════════════════════════════════ */

describe('mobile (vw=640)', () => {
  beforeEach(() => {
    Object.defineProperty(window, 'innerWidth', { value: 640, writable: true, configurable: true });
  });

  test.each(['hero', 'overlay', 'minimal'])('%s: always 1fr on mobile', (style) => {
    const block = makeFeaturedBlock(4, style);
    document.body.appendChild(block);
    applyGridFallback();
    expect(block.style.gridTemplateColumns).toBe('1fr');
  });

  test('mobile: first-item grid-column is reset', () => {
    const block = makeFeaturedBlock(4, 'hero');
    document.body.appendChild(block);
    applyGridFallback();
    const first = block.querySelector('[data-index="0"]');
    expect(first.style.gridColumn).toBe('');
  });
});

/* ═══════════════════════════════════════════════════════════════════
   article:has(figure) header.tool — border-radius cosmetic fix
   ═══════════════════════════════════════════════════════════════════ */

describe('header.tool border-radius fix', () => {
  test('header inside article WITH figure gets border-radius', () => {
    const block = makeFeaturedBlock(2, 'hero', true);
    document.body.appendChild(block);
    applyGridFallback();
    const header = block.querySelector('article.fc-item-featured header.tool');
    expect(header.style.borderRadius).toMatch(/var\(--r/);
  });

  test('header inside article WITHOUT figure is NOT affected', () => {
    const block = makeFeaturedBlock(2, 'hero', false);
    document.body.appendChild(block);
    applyGridFallback();
    const header = block.querySelector('article.fc-item-featured header.tool');
    expect(header.style.borderRadius).toBe('');
  });
});

/* ═══════════════════════════════════════════════════════════════════
   innerbox:not(:has(figure)) — fc-no-image class
   ═══════════════════════════════════════════════════════════════════ */

describe('fc-no-image class on innerbox', () => {
  test('innerbox WITHOUT figure gets fc-no-image class', () => {
    const block = makeFeaturedBlock(2, 'hero', false);
    document.body.appendChild(block);
    applyGridFallback();
    const innerboxes = block.querySelectorAll('.fc-item-block-featured-wrapper-innerbox');
    innerboxes.forEach(box => expect(box.classList.contains('fc-no-image')).toBe(true));
  });

  test('innerbox WITH figure does NOT get fc-no-image class', () => {
    const block = makeFeaturedBlock(2, 'hero', true);
    document.body.appendChild(block);
    applyGridFallback();
    const innerboxes = block.querySelectorAll('.fc-item-block-featured-wrapper-innerbox');
    innerboxes.forEach(box => expect(box.classList.contains('fc-no-image')).toBe(false));
  });
});

/* ═══════════════════════════════════════════════════════════════════
   YOOtheme grid: #fc-yootheme-grid .fc-featured-section
   ═══════════════════════════════════════════════════════════════════ */

describe('#fc-yootheme-grid .fc-featured-section', () => {
  test('total=2 → 1fr 1fr', () => {
    const { root, section } = makeYoothemeSection(2);
    document.body.appendChild(root);
    applyGridFallback();
    expect(section.style.gridTemplateColumns).toBe('1fr 1fr');
  });

  test('total=3 → 1fr 1fr, first spans gridRow 1/3 col 1', () => {
    const { root, section } = makeYoothemeSection(3);
    document.body.appendChild(root);
    applyGridFallback();
    expect(section.style.gridTemplateColumns).toBe('1fr 1fr');
    const first = section.querySelector('[data-index="0"]');
    expect(first.style.gridRow).toBe('1/3');
    expect(first.style.gridColumn).toBe('1');
  });

  test('total=4 → repeat(3,1fr), first spans all columns', () => {
    const { root, section } = makeYoothemeSection(4);
    document.body.appendChild(root);
    applyGridFallback();
    expect(section.style.gridTemplateColumns).toBe('repeat(3,1fr)');
    const first = section.querySelector('[data-index="0"]');
    expect(first.style.gridColumn).toBe('1/-1');
  });

  test('mobile total=3 → 1fr, grid-column reset', () => {
    Object.defineProperty(window, 'innerWidth', { value: 375, writable: true, configurable: true });
    const { root, section } = makeYoothemeSection(3);
    document.body.appendChild(root);
    applyGridFallback();
    expect(section.style.gridTemplateColumns).toBe('1fr');
    const first = section.querySelector('[data-index="0"]');
    expect(first.style.gridColumn).toBe('');
  });

  test('tablet (768) total=3 → first spans 1/-1 (full width)', () => {
    Object.defineProperty(window, 'innerWidth', { value: 768, writable: true, configurable: true });
    const { root, section } = makeYoothemeSection(3);
    document.body.appendChild(root);
    applyGridFallback();
    const first = section.querySelector('[data-index="0"]');
    expect(first.style.gridColumn).toBe('1/-1');
  });
});

/* ═══════════════════════════════════════════════════════════════════
   Edge cases
   ═══════════════════════════════════════════════════════════════════ */

describe('edge cases', () => {
  test('block with no [data-total] wrapper is ignored', () => {
    const block = document.createElement('div');
    block.className = 'featured-block fc-items-block';
    document.body.appendChild(block);
    expect(() => applyGridFallback()).not.toThrow();
    expect(block.style.gridTemplateColumns).toBe('');
  });

  test('block with total=1 is ignored (no multi-col needed)', () => {
    const block = makeFeaturedBlock(1, 'hero');
    document.body.appendChild(block);
    applyGridFallback();
    expect(block.style.gridTemplateColumns).toBe('');
  });

  test('multiple blocks are all processed independently', () => {
    const b1 = makeFeaturedBlock(2, 'hero');
    const b2 = makeFeaturedBlock(4, 'overlay');
    document.body.appendChild(b1);
    document.body.appendChild(b2);
    applyGridFallback();
    expect(b1.style.gridTemplateColumns).toBe('1fr 1fr');
    expect(b2.style.gridTemplateColumns).toBe('repeat(4,1fr)');
  });
});
