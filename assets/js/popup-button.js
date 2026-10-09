/**
 * CWF Floating Button & Popup
 */
(function() {
    'use strict';

    /**
     * Fix mobile viewport height when browser address bar shows/hides.
     * Uses window.innerHeight instead of 100vh to avoid bottom gap on scroll.
     */
    function setViewportHeight() {
        document.documentElement.style.setProperty(
            '--vh',
            (window.innerHeight * 0.01) + 'px'
        );
    }

    setViewportHeight();
    window.addEventListener('resize', setViewportHeight);
    window.addEventListener('orientationchange', setViewportHeight);
    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', setViewportHeight);
        window.visualViewport.addEventListener('scroll', setViewportHeight);
    }

    document.addEventListener('DOMContentLoaded', function() {

        const popup = document.getElementById('cwf-admin-popup');
        const openBtn = document.getElementById('cwf-open-admin-popup');
        const closeBtn = document.querySelector('.cwf-admin-popup-close');

        if (!popup || !openBtn || !closeBtn) {
            return;
        }

        function disableBodyScroll() {
            document.body.classList.add('cwf-popup-open');
        }

        function enableBodyScroll() {
            document.body.classList.remove('cwf-popup-open');
        }

        function closePopup() {
            popup.classList.remove('active');
            enableBodyScroll();
        }

        openBtn.addEventListener('click', function(e) {
            e.preventDefault();
            popup.classList.add('active');
            disableBodyScroll();
        });

        closeBtn.addEventListener('click', closePopup);

        popup.addEventListener('click', function(e) {
            if (e.target === popup) {
                closePopup();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && popup.classList.contains('active')) {
                closePopup();
            }
        });

//         const sideButton = openBtn.closest('.cwf-admin-side-button');
//         const mqMobile = window.matchMedia('(max-width: 991px)');
//         /** How far down the page (px) before the button docks to the bottom on mobile */
//         const SCROLL_DOCK_PX = 100;
//         /** Extra wait after paint so layout (fonts, toolbar) settles before transitions apply */
//         const MOTION_READY_DELAY_MS = 120;

//         function updateSideButtonDock() {
//             if (!sideButton) {
//                 return;
//             }
//             if (!mqMobile.matches) {
//                 sideButton.classList.remove('cwf-admin-side-button--scroll-docked');
//                 return;
//             }
//             var y = window.scrollY || document.documentElement.scrollTop || 0;
//             if (y > SCROLL_DOCK_PX) {
//                 sideButton.classList.add('cwf-admin-side-button--scroll-docked');
//             } else {
//                 sideButton.classList.remove('cwf-admin-side-button--scroll-docked');
//             }
//         }

//         /** Enable CSS transitions only after first layout — prevents initial jump / stray animation */
//         function enableSideButtonMotionReady() {
//             if (!sideButton || !mqMobile.matches) {
//                 return;
//             }
//             sideButton.classList.remove('cwf-admin-side-button--motion-ready');
//             window.requestAnimationFrame(function() {
//                 window.requestAnimationFrame(function() {
//                     window.setTimeout(function() {
//                         if (!sideButton || !mqMobile.matches) {
//                             return;
//                         }
//                         sideButton.classList.add('cwf-admin-side-button--motion-ready');
//                     }, MOTION_READY_DELAY_MS);
//                 });
//             });
//         }

//         var dockScrollTicking = false;
//         function onScrollOrResizeForDock() {
//             if (!dockScrollTicking) {
//                 window.requestAnimationFrame(function() {
//                     updateSideButtonDock();
//                     dockScrollTicking = false;
//                 });
//                 dockScrollTicking = true;
//             }
//         }

//         function onMqMobileChange() {
//             updateSideButtonDock();
//             if (mqMobile.matches) {
//                 enableSideButtonMotionReady();
//             } else if (sideButton) {
//                 sideButton.classList.remove('cwf-admin-side-button--motion-ready');
//             }
//         }

//         updateSideButtonDock();
//         enableSideButtonMotionReady();
//         window.addEventListener('scroll', onScrollOrResizeForDock, { passive: true });
//         window.addEventListener('resize', updateSideButtonDock);
//         if (typeof mqMobile.addEventListener === 'function') {
//             mqMobile.addEventListener('change', onMqMobileChange);
//         } else {
//             mqMobile.addListener(onMqMobileChange);
//         }

    });

})();
