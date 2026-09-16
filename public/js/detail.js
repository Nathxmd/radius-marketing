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

// Tabel Insight Area Komersial: filter nama + pagination (client-side,
// karena seluruh nama kantor sudah dirender server-side dan jumlahnya
// ratusan per cabang).
(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        var table = document.getElementById("insight-table");
        var tbody = table ? table.querySelector("tbody") : null;
        if (!tbody) return;

        var perPage = parseInt(table.getAttribute("data-per-page"), 10) || 10;
        var searchInput = document.getElementById("insight-search");
        var countText = document.getElementById("insight-count-text");
        var emptyNote = document.getElementById("insight-empty");
        var pagination = document.getElementById("insight-pagination");

        var items = Array.prototype.slice.call(tbody.querySelectorAll("tr")).map(function (row) {
            var nameCell = row.querySelector(".insight-name");
            return {
                row: row,
                no: row.querySelector(".table-no"),
                // teks nama diambil dari DOM (bukan duplikat data-name) supaya
                // nama tidak tersimpan dua kali di halaman
                name: (nameCell ? nameCell.textContent : "").toLowerCase()
            };
        });

        var filtered = items;
        var page = 1;

        function buildPageButton(label, target, options) {
            options = options || {};
            var el;

            if (options.disabled) {
                el = document.createElement("span");
                el.className = "page-btn is-disabled";
            } else {
                el = document.createElement("button");
                el.type = "button";
                el.className = "page-btn" + (target === page ? " is-active" : "");
                el.addEventListener("click", function () {
                    page = target;
                    render();
                });
            }

            if (options.label) el.setAttribute("aria-label", options.label);
            el.textContent = label;
            return el;
        }

        function renderPagination(totalPages) {
            if (!pagination) return;
            pagination.innerHTML = "";
            if (totalPages <= 1) return;

            pagination.appendChild(buildPageButton("\u2039", page - 1, {
                disabled: page <= 1,
                label: "Halaman sebelumnya"
            }));

            // Jendela nomor halaman: selalu tampilkan halaman 1 & terakhir,
            // plus 2 halaman di kiri/kanan halaman aktif, sisanya dipotong "…"
            var numbers = [];
            for (var i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || Math.abs(i - page) <= 2) {
                    numbers.push(i);
                }
            }

            var previous = 0;
            numbers.forEach(function (number) {
                if (previous && number - previous > 1) {
                    pagination.appendChild(buildPageButton("\u2026", 0, { disabled: true }));
                }
                pagination.appendChild(buildPageButton(String(number), number));
                previous = number;
            });

            pagination.appendChild(buildPageButton("\u203a", page + 1, {
                disabled: page >= totalPages,
                label: "Halaman berikutnya"
            }));
        }

        function render() {
            var total = filtered.length;
            var totalPages = Math.max(1, Math.ceil(total / perPage));
            if (page > totalPages) page = totalPages;

            var start = (page - 1) * perPage;
            var end = Math.min(start + perPage, total);

            items.forEach(function (item) {
                item.row.hidden = true;
            });

            filtered.slice(start, end).forEach(function (item, index) {
                item.row.hidden = false;
                if (item.no) item.no.textContent = start + index + 1;
            });

            if (countText) {
                countText.textContent = total === 0
                    ? "Tidak ada kantor yang cocok"
                    : "Menampilkan " + (start + 1) + "\u2013" + end + " dari " + total + " kantor";
            }
            if (emptyNote) emptyNote.hidden = total > 0;

            table.hidden = total === 0;
            renderPagination(totalPages);
        }

        if (searchInput) {
            searchInput.addEventListener("input", function () {
                var query = searchInput.value.trim().toLowerCase();
                filtered = query === "" ? items : items.filter(function (item) {
                    return item.name.indexOf(query) !== -1;
                });
                page = 1;
                render();
            });
        }

        render();
    });
})();
