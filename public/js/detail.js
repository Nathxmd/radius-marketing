(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        var tabs = document.querySelectorAll(".tab-btn");
        if (tabs.length === 0) return;

        function activate(tabId) {
            document.querySelectorAll(".tab-btn").forEach(function (btn) {
                btn.classList.toggle("is-active", btn.getAttribute("data-tab") === tabId);
            });
            document.querySelectorAll(".tab-panel").forEach(function (panel) {
                panel.classList.toggle("is-active", panel.id === "tab-" + tabId);
            });
        }

        tabs.forEach(function (btn) {
            btn.addEventListener("click", function () {
                activate(btn.getAttribute("data-tab"));
            });
        });

        // Buka tab sesuai hash di URL (mis. #tab-kecamatan)
        var hash = window.location.hash.replace("#", "");
        if (hash.indexOf("tab-") === 0) {
            activate(hash.slice(4));
        }
    });
})();
