// MC Builder 论坛 JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // 初始化功能
    initSmoothScroll();
    initImageLazyLoad();
    initTooltips();
    initNotifications();
    
    console.log('🎮 MC Builder 论坛已加载完成！');
});

// 平滑滚动
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').
