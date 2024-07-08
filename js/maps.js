const {OverlayView} = await google.maps.importLibrary("maps")
/**
 * A customized popup on the map.
 */
class PopupClass extends OverlayView {
  position;
  containerDiv;
  offset;
  clickable;
  
  constructor(position, content, clickable, offset) {
    super();
    this.position = position;
    this.offset = offset;
    this.clickable = clickable;
    
    content.classList.add("popup-bubble");
    
    // This zero-height div is positioned at the bottom of the bubble.
    const bubbleAnchor = document.createElement("div");
    
    bubbleAnchor.classList.add("popup-bubble-anchor");
    bubbleAnchor.appendChild(content);
    
    // This zero-height div is positioned at the bottom of the tip.
    this.containerDiv = document.createElement("div");
    this.containerDiv.classList.add("popup-container");
    this.containerDiv.appendChild(bubbleAnchor);
    
    // Optionally stop clicks, etc., from bubbling up to the map.
    PopupClass.preventMapHitsAndGesturesFrom(this.containerDiv);
  }
  
  /** Called when the popup is added to the map. */
  onAdd() {
    let pane = this.clickable ? this.getPanes().floatPane : this.getPanes().overlayLayer;
    pane.appendChild(this.containerDiv);
  }
  
  /** Called when the popup is removed from the map. */
  onRemove() {
    if (this.containerDiv.parentElement) {
      this.containerDiv.parentElement.removeChild(this.containerDiv);
    }
  }
  
  /** Called each frame when the popup needs to draw itself. */
  draw() {
    const divPosition = this.getProjection().fromLatLngToDivPixel(
      this.position
    );
    
    // Hide the popup when it is far out of view.
    const display =
          Math.abs(divPosition.x) < 4000 && Math.abs(divPosition.y) < 4000
          ? "block"
          : "none";
    
    if (display === "block") {
      this.containerDiv.style.left = divPosition.x + "px";
      this.containerDiv.style.top = divPosition.y - this.offset + "px";
    }
    
    if (this.containerDiv.style.display !== display) {
      this.containerDiv.style.display = display;
    }
  }
}

window.MapPopUp = PopupClass;

function getCurrentPosition() {
  return new Promise( (resolve, reject) => {
    navigator.geolocation.getCurrentPosition(
      position => resolve(position),
      error => reject(error)
    )
  })
}

function createMapButton(map, label, action, position) {
  if (!position) {
    position = google.maps.ControlPosition.TOP_CENTER;
  }
  const button = document.createElement("div");
  button.setAttribute('class', 'map-button');
  button.innerHTML = label
  button.onclick = action;
  const wrap = document.createElement("div");
  wrap.setAttribute("class", "map-button-wrap")
  wrap.appendChild(button);
  map.controls[position].push(wrap);
  return button;
}


window.MapFindMeButton = function (map) {
  return createMapButton(map, `\u{1F78B} Here`, async () => {
    let position = await getCurrentPosition();
    console.log(position.coords);
    map.panTo(new google.maps.LatLng(position.coords.latitude, position.coords.longitude));
  })
}

window.MapFindSiteButton = function (map) {
  if (!siteLocation) {
    console.log('Find Site with no siteLocation');
    return
  };
  return createMapButton(map, `\u{1F78B} Site`, () => {
    map.panTo(new google.maps.LatLng(siteLocation.lat, siteLocation.lng));    
  });
}

window.MapFullscreenButton = function(map) {
  let button = createMapButton(
    map,
    '<img style="height: 18px; width: 18px;" src="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20viewBox%3D%220%200%2018%2018%22%3E%3Cpath%20fill%3D%22%23666%22%20d%3D%22M0%200v6h2V2h4V0H0zm16%200h-4v2h4v4h2V0h-2zm0%2016h-4v2h6v-6h-2v4zM2%2012H0v6h6v-2H2v-4z%22/%3E%3C/svg%3E" alt="">',
    (ev) => {
      let dataset = ev.target.closest('.map-button').dataset;

      let full = dataset.full;
      let el = map.getDiv();

      if (full) {
        el.style.height = "";
        el.style.width = "";
        el.style.position = 'relative';
        el.style.left = '';

        dataset.full = ''
      } else {
        el.style.height = "90%";
        el.style.width = "100%";
        el.style.position = 'absolute';
        el.style.left = '0px';

        dataset.full = 'full'

        let y = el.closest('.plant').getBoundingClientRect().y + window.scrollY;
        window.scroll({
          top: y});
      }
    },
    google.maps.ControlPosition.TOP_RIGHT
  );
  button.style.minWidth = '1em';
}
