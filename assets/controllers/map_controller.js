import { Controller } from '@hotwired/stimulus';
import L from "leaflet";
import "leaflet/dist/leaflet.css";

// Fix icons
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: require('leaflet/dist/images/marker-icon-2x.png'),
    iconUrl: require('leaflet/dist/images/marker-icon.png'),
    shadowUrl: require('leaflet/dist/images/marker-shadow.png'),
});

export default class extends Controller {
    static targets = ["container"];
    static values = { lat: Number, lng: Number, zoom: Number, marker: Boolean }

    connect() {
        this.map = L.map(this.containerTarget);

        this.map.setView([this.latValue, this.lngValue], this.zoomValue);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);

        if (this.hasMarkerValue && true === this.markerValue) {
            L.marker([this.latValue, this.lngValue]).addTo(this.map);
        }
    }
}
