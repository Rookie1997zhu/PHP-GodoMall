/**
 * 견적문의(1:1 request1 + mypageFl): 비로그인 시 extra-menu 링크 클릭 → 로그인 페이지로 이동
 * (scroll_banner_left 는 템플릿에서 분기 처리)
 */
(function () {
    function buildLoginHref() {
        var path = window.__DBOOK_QA_ESTIMATE_RETURN__ || '/board/list.php?bdId=request1&mypageFl=y';
        return '../member/login.php?returnUrl=' + encodeURIComponent(path);
    }

    $(document).on(
        'click',
        '#category .extra-menu a[href*="bdId=request1"]',
        function (e) {
            if (window.__DBOOK_IS_LOGIN__) {
                return;
            }
            e.preventDefault();
            alert('로그인하셔야 본 서비스를 이용하실 수 있습니다.');
            window.location.href = buildLoginHref();
        }
    );
})();
