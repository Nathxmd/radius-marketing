(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        var mapEl = document.getElementById("map");
        if (!mapEl) return;

        var lat = parseFloat(mapEl.getAttribute("data-lat"));
        var lon = parseFloat(mapEl.getAttribute("data-lon"));

        if (isNaN(lat) || isNaN(lon)) {
            return; // placeholder ditampilkan oleh view
        }

        var map = L.map("map").setView([lat, lon], 13);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "&copy; OpenStreetMap contributors",
            maxZoom: 19
        }).addTo(map);

        // Marker cabang (custom SVG pin, primary-600)
        var iconHtml = '<svg width="30" height="40" viewBox="0 0 30 40" xmlns="http://www.w3.org/2000/svg">' +
            '<path d="M15 1C7.8 1 2 6.8 2 14c0 9.2 13 25 13 25s13-15.8 13-25C28 6.8 22.2 1 15 1z" fill="#0F6E56" stroke="#FFFFFF" stroke-width="2"/>' +
            '<circle cx="15" cy="14" r="6" fill="#FFFFFF"/></svg>';

        var icon = L.divIcon({
            html: iconHtml,
            className: "branch-marker",
            iconSize: [30, 40],
            iconAnchor: [15, 40],
            popupAnchor: [0, -36]
        });

        var marker = L.marker([lat, lon], { icon: icon }).addTo(map);
        marker.bindTooltip(mapEl.getAttribute("data-name") || "Cabang");

        // Lingkaran radius 5km (accent-600 stroke, accent-50 fill 25%)
        var radius = parseInt(mapEl.getAttribute("data-radius") || "5000", 10);
        L.circle([lat, lon], {
            radius: radius,
            color: "#993C1D",
            weight: 2,
            fillColor: "#FAECE7",
            fillOpacity: 0.25
        }).addTo(map);
    });
})();
