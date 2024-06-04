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

function createMapButton(map, label, action) {
  const button = document.createElement("div");
  button.setAttribute('class', 'map-button');
  button.textContent = label
  button.onclick = action;
  const wrap = document.createElement("div");
  wrap.setAttribute("class", "map-button-wrap")
  wrap.appendChild(button);
  map.controls[google.maps.ControlPosition.TOP_CENTER].push(wrap);
  
}


window.MapFindMeButton = function (map) {
  return createMapButton(map, 'Find Me', async () => {
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
  return createMapButton(map, 'Find Site', () => {
    map.panTo(new google.maps.LatLng(siteLocation.lat, siteLocation.lng));    
  });
}
