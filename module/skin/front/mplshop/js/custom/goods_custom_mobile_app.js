/**
 * goods_custom_mobile_app.js
 * - 모바일/태블릿에서 앱 같은 UX 제공 (bottom sheet + bottom bar)
 * - CustomGoodsEditor(initCanvas)가 860px로 고정한 CSS 표시 크기를 "컨테이너 기준"으로 재설정
 *   (내부 캔버스 해상도/좌표계는 유지 -> 선택/터치 오차 최소화)
 * - 추가:
 *   1) 툴바 접기/펼치기(기본 접힘)
 *   2) 도구(이미지/텍스트/라이브러리/레이어) 접기/펼치기(기본 접힘, FAB 버튼)
 *   3) Bottom sheet 상/하 스와이프로 열기/닫기(핸들 영역에서만)
 */
(function () {
  function isMobileLike() {
    try {
      return window.matchMedia && window.matchMedia('(max-width: 1024px)').matches;
    } catch (e) {
      return false;
    }
  }

  function $(id) {
    return document.getElementById(id);
  }

  function pxVar(name) {
    try {
      var v = getComputedStyle(document.documentElement).getPropertyValue(name);
      var n = parseFloat(String(v || '').trim());
      return isNaN(n) ? 0 : n;
    } catch (e) {
      return 0;
    }
  }

  function syncMobileBarPrice() {
    var priceEl = $('summaryPrice');
    var out = $('mobileBarPrice');
    if (!out) return;
    var text = priceEl ? String(priceEl.textContent || '').trim() : '';
    out.textContent = text || '-';
  }

  function setSheetOpen(open) {
    if (!isMobileLike()) return;
    document.body.classList.toggle('sheet-open', !!open);
    document.body.classList.remove('sheet-dragging');

    try {
      var hint = document.querySelector('.mobile-bottom-bar__hint');
      if (hint) hint.textContent = open ? '터치하여 닫기' : '상세 옵션 보기';
    } catch (e) {}

    // ✅ 드래그 중 inline transform 복구
    try {
      var sheet = document.querySelector('.product-info');
      if (sheet) {
        sheet.style.transition = '';
        sheet.style.transform = '';
      }
    } catch (e) {}
  }

  function setToolbarCollapsed(collapsed) {
    if (!isMobileLike()) return;
    document.body.classList.toggle('toolbar-collapsed', !!collapsed);
    try {
      var btn = document.querySelector('.mobile-toolbar-toggle');
      if (btn) {
        btn.setAttribute('aria-expanded', String(!collapsed));
        btn.textContent = collapsed ? '툴바 ▼' : '툴바 ▲';
      }
    } catch (e) {}
  }

  function setOptionsCollapsed(collapsed) {
    if (!isMobileLike()) return;
    document.body.classList.toggle('options-collapsed', !!collapsed);
    try {
      var fab = $('mobileOptionsFab');
      if (fab) fab.setAttribute('aria-expanded', String(!collapsed));
    } catch (e) {}
  }

  function ensureToolbarToggleButton() {
    if (!isMobileLike()) return;
    var bar = document.querySelector('.custom-toolbar');
    if (!bar) return;

    var existing = bar.querySelector('.mobile-toolbar-toggle');
    if (existing) return;

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'mobile-toolbar-toggle';
    btn.textContent = document.body.classList.contains('toolbar-collapsed') ? '툴바 ▼' : '툴바 ▲';
    btn.setAttribute('aria-expanded', 'false');
    btn.addEventListener('click', function () {
      var collapsed = document.body.classList.contains('toolbar-collapsed');
      setToolbarCollapsed(!collapsed);
    });

    bar.insertBefore(btn, bar.firstChild);
  }

  function ensureOptionsFab() {
    if (!isMobileLike()) return;
    if ($('mobileOptionsFab')) return;

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.id = 'mobileOptionsFab';
    btn.className = 'mobile-options-fab';
    btn.textContent = '도구';
    btn.setAttribute('aria-expanded', 'false');
    btn.addEventListener('click', function () {
      var collapsed = document.body.classList.contains('options-collapsed');
      setOptionsCollapsed(!collapsed);
    });

    document.body.appendChild(btn);
  }

  function bindBottomSheetBasicUI() {
    var btn = $('btnToggleSheet');
    var backdrop = $('mobileSheetBackdrop');

    if (btn && !btn.__boundMobileApp) {
      btn.addEventListener('click', function () {
        var open = !document.body.classList.contains('sheet-open');
        setSheetOpen(open);
      });
      btn.__boundMobileApp = true;
    }

    if (backdrop && !backdrop.__boundMobileApp) {
      backdrop.addEventListener('click', function () {
        setSheetOpen(false);
      });
      backdrop.__boundMobileApp = true;
    }

    if (!document.__boundMobileAppEsc) {
      document.addEventListener('keydown', function (e) {
        if (e && e.key === 'Escape') setSheetOpen(false);
      });
      document.__boundMobileAppEsc = true;
    }
  }

  /**
   * ✅ 캔버스 CSS 표시 크기만 "컨테이너 기준"으로 조정한다.
   * - 내부 해상도(actualWidth/actualHeight)는 유지
   */
  function applyResponsiveCanvasCssSizing(editor) {
    if (!editor || !editor.canvas) return;
    if (!isMobileLike()) return;

    var wrap = document.querySelector('.canvas-container');
    if (!wrap) return;

    try {
      wrap.style.removeProperty('width');
      wrap.style.removeProperty('height');
    } catch (e) {}

    var rect = wrap.getBoundingClientRect();
    var displaySize = Math.floor(Math.min(rect.width, rect.height));
    if (!displaySize || displaySize < 10) return;

    try {
      var uc = editor.canvas.upperCanvasEl;
      var lc = editor.canvas.lowerCanvasEl;
      if (uc && uc.style) {
        uc.style.width = displaySize + 'px';
        uc.style.height = displaySize + 'px';
      }
      if (lc && lc.style) {
        lc.style.width = displaySize + 'px';
        lc.style.height = displaySize + 'px';
      }
      wrap.style.width = displaySize + 'px';
      wrap.style.height = displaySize + 'px';
    } catch (e) {}

    try {
      editor.canvas.calcOffset();
      editor.canvas.requestRenderAll();
    } catch (e) {}
  }

  function hookOrderSummaryForPriceSync() {
    try {
      if (typeof window.updateOrderSummary === 'function' && !window.updateOrderSummary.__mobileAppWrapped) {
        var orig = window.updateOrderSummary;
        var wrapped = function () {
          var r = orig.apply(this, arguments);
          try { syncMobileBarPrice(); } catch (e) {}
          return r;
        };
        wrapped.__mobileAppWrapped = true;
        window.updateOrderSummary = wrapped;
      }
    } catch (e) {}

    try {
      var priceEl = $('summaryPrice');
      if (priceEl && !priceEl.__mobileAppObserved) {
        var ob = new MutationObserver(function () {
          syncMobileBarPrice();
        });
        ob.observe(priceEl, { childList: true, characterData: true, subtree: true });
        priceEl.__mobileAppObserved = true;
      }
    } catch (e) {}
  }

  function waitForEditorReady(timeoutMs) {
    timeoutMs = timeoutMs || 4000;
    var start = Date.now();
    return new Promise(function (resolve) {
      (function tick() {
        var editor = window.customEditor;
        if (editor && editor.canvas) return resolve(editor);
        if (Date.now() - start > timeoutMs) return resolve(null);
        setTimeout(tick, 50);
      })();
    });
  }

  function bindResizeForCanvas(editor) {
    if (!editor || editor.__mobileAppResizeBound) return;
    editor.__mobileAppResizeBound = true;

    var timer = null;
    window.addEventListener('resize', function () {
      if (!isMobileLike()) return;
      clearTimeout(timer);
      timer = setTimeout(function () {
        applyResponsiveCanvasCssSizing(editor);
      }, 80);
    });
  }

  /**
   * ✅ BottomSheet 드래그: 위로 올리면 열림 / 아래로 내리면 닫힘
   * - 핸들 영역(상단 80px)에서만 시작
   * - 열림 상태에서도 sheet.scrollTop > 0 이면 드래그 시작 금지(내부 스크롤 우선)
   */
  function bindSheetDrag() {
    if (!isMobileLike()) return;
    var sheet = document.querySelector('.product-info');
    var backdrop = $('mobileSheetBackdrop');
    if (!sheet || sheet.__boundDrag) return;

    var dragging = false;
    var startY = 0;
    var startTranslate = 0;
    var closedTranslate = 0;
    var lastTranslate = 0; // ✅ 마지막 translateY 기록 (matrix 파싱 대신)

    function computeClosedTranslate() {
      var peek = pxVar('--sheet-peek');
      var safeB = pxVar('--safe-b');
      var h = sheet.getBoundingClientRect().height || sheet.offsetHeight || 0;
      closedTranslate = Math.max(0, h - peek - safeB);
    }

    function setBackdropOpacityByTranslate(t) {
      if (!backdrop) return;
      var alpha = 1 - (t / (closedTranslate || 1));
      alpha = Math.max(0, Math.min(1, alpha));
      backdrop.style.opacity = String(0.55 * alpha);
    }

    function onStart(clientY, target) {
      if (!isMobileLike()) return;
      var rect = sheet.getBoundingClientRect();
      // ✅ 드래그 시작 가능 영역 확대:
      // - 닫힘 상태: 120px
      // - 열림 상태: 220px (사용자가 더 쉽게 아래로 내려서 닫을 수 있도록)
      var limit = document.body.classList.contains('sheet-open') ? 220 : 120;
      var inHandleArea = (clientY - rect.top) <= limit;
      if (!inHandleArea) return;

      // ✅ 입력/버튼/링크 위에서 드래그 시작 금지 (오작동/오입력 방지)
      try {
        if (target && target.closest && target.closest('input,textarea,select,button,a')) {
          return;
        }
      } catch (e) {}

      if (document.body.classList.contains('sheet-open') && sheet.scrollTop > 0) {
        return;
      }

      computeClosedTranslate();
      dragging = true;
      startY = clientY;
      startTranslate = document.body.classList.contains('sheet-open') ? 0 : closedTranslate;
      lastTranslate = startTranslate;

      document.body.classList.add('sheet-dragging');
      try {
        if (backdrop) {
          backdrop.style.transition = 'none';
          backdrop.style.opacity = document.body.classList.contains('sheet-open') ? '0.55' : '0';
        }
      } catch (e) {}

      sheet.style.transition = 'none';
      sheet.style.willChange = 'transform';
    }

    function onMove(clientY) {
      if (!dragging) return;
      var dy = clientY - startY;
      var next = startTranslate + dy;
      if (next < 0) next = 0;
      if (next > closedTranslate) next = closedTranslate;

      lastTranslate = next;
      sheet.style.transform = 'translateY(' + next + 'px)';
      setBackdropOpacityByTranslate(next);
    }

    function onEnd() {
      if (!dragging) return;
      dragging = false;

      sheet.style.transition = '';
      sheet.style.willChange = '';

      try {
        if (backdrop) {
          backdrop.style.transition = '';
          backdrop.style.opacity = '';
        }
      } catch (e) {}

      // ✅ 더 쉽게 닫히도록 임계값 조정 (0.6 -> 0.45)
      var open = lastTranslate < (closedTranslate * 0.45);
      setSheetOpen(open);
    }

    sheet.addEventListener('touchstart', function (e) {
      if (!e || !e.touches || !e.touches[0]) return;
      onStart(e.touches[0].clientY, e.target);
    }, { passive: true });

    sheet.addEventListener('touchmove', function (e) {
      if (!dragging) return;
      if (!e || !e.touches || !e.touches[0]) return;
      try { e.preventDefault(); } catch (err) {}
      onMove(e.touches[0].clientY);
    }, { passive: false });

    sheet.addEventListener('touchend', function () {
      onEnd();
    }, { passive: true });

    sheet.__boundDrag = true;
  }

  document.addEventListener('DOMContentLoaded', function () {
    bindBottomSheetBasicUI();
    hookOrderSummaryForPriceSync();
    syncMobileBarPrice();

    // ✅ 기본 상태: sheet 닫힘, 툴바 접힘, 도구 접힘
    setSheetOpen(false);
    setToolbarCollapsed(true);
    setOptionsCollapsed(true);

    ensureToolbarToggleButton();
    ensureOptionsFab();
    bindSheetDrag();

    waitForEditorReady(6000).then(function (editor) {
      if (!editor) return;
      applyResponsiveCanvasCssSizing(editor);
      bindResizeForCanvas(editor);
    });
  });
})();

