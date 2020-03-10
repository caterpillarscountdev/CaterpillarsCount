<?php

require_once('resources/Keychain.php');
require_once('Site.php');

class Plant
{
//PRIVATE VARS
	private $id;							//INT
	private $site;							//Site object
	private $circle;
	private $orientation;					//STRING			email that has been signed up for but not necessarilly verified
	private $code;
	private $species;
	
	private $deleted;

//FACTORY
	public static function create($site, $circle, $orientation) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		if(!$dbconn){
			return "Cannot connect to server.";
		}
		
		$site = self::validSite($dbconn, $site);
		$circle = self::validCircleFormat($dbconn, $circle);
		$orientation = self::validOrientationFormat($dbconn, $orientation);
		
		$failures = "";
		
		if($site === false){
			$failures .= "Invalid site. ";
		}
		if($circle === false){
			$failures .= "Enter a circle. ";
		}
		if($orientation === false){
			$failures .= "Enter an orientation. ";
		}
		if($failures == "" && is_object(self::findBySiteAndPosition($site, $circle, $orientation))){
			$failures .= "Enter a unique circle/orientation set for this site. ";
		}
		
		if($failures != ""){
			return $failures;
		}
		
		//DETERMINE ID MANUALLY TO FILL IN THE CRACKS OF DELETED CODES:
		$MIN_ID = 703;//corresponds to "AAA"
		$id = $MIN_ID;
		$query = mysqli_query($dbconn, "SELECT `ID` FROM `Plant` ORDER BY `ID` ASC LIMIT 1");
		if(mysqli_num_rows($query) == 1){
			$query = mysqli_query($dbconn, "SELECT t1.ID+1 AS NextID FROM `Plant` AS t1 LEFT JOIN `Plant` AS t2 ON t1.ID+1=t2.ID WHERE t2.ID IS NULL AND t1.ID+1>='$MIN_ID' ORDER BY t1.ID+1 ASC");
			while($row = mysqli_fetch_assoc($query)){
				$id = intval($row["NextID"]);
				while(mysqli_num_rows(mysqli_query($dbconn, "SELECT `ID` FROM `Plant` WHERE `ID`='" . $id . "' LIMIT 1")) == 0){
					if(mysqli_num_rows(mysqli_query($dbconn, "SELECT `ID` FROM `Plant` WHERE `Code`='" . self::IDToCode($id) . "' LIMIT 1")) == 0){
						break 2;
					}
					$id++;
				}
			}
		}
		
		mysqli_query($dbconn, "INSERT INTO Plant (`ID`, `SiteFK`, `Circle`, `Orientation`, `Species`) VALUES ('" . $id . "', '" . $site->getID() . "', '$circle', '$orientation', 'N/A')");
		//NOT NEEDED BECAUSE WE DETERMINE IDs MANUALLY TO FILL IN THE CRACKS OF DELETED CODES: $id = intval(mysqli_insert_id($dbconn));
		
		$code = self::IDToCode($id);
		mysqli_query($dbconn, "UPDATE Plant SET `Code`='$code' WHERE `ID`='$id'");
		mysqli_close($dbconn);
		
		return new Plant($id, $site, $circle, $orientation, $code, "N/A");
	}
	private function __construct($id, $site, $circle, $orientation, $code, $species) {
		$this->id = intval($id);
		$this->site = $site;
		$this->circle = $circle;
		$this->orientation = $orientation;
		$this->code = $code;
		$this->species = $species;
		
		$this->deleted = false;
	}

//FINDERS
	public static function findByID($id) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		$id = mysqli_real_escape_string($dbconn, htmlentities($id));
		$query = mysqli_query($dbconn, "SELECT * FROM `Plant` WHERE `ID`='$id' LIMIT 1");
		mysqli_close($dbconn);
		
		if(mysqli_num_rows($query) == 0){
			return null;
		}
		
		$plantRow = mysqli_fetch_assoc($query);
		
		$site = Site::findByID($plantRow["SiteFK"]);
		$circle = $plantRow["Circle"];
		$orientation = $plantRow["Orientation"];
		$code = $plantRow["Code"];
		$species = $plantRow["Species"];
		
		return new Plant($id, $site, $circle, $orientation, $code, $species);
	}
	
	public static function findByCode($code) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		$code = self::validCode($dbconn, $code);
		if($code === false){
			return null;
		}
		$query = mysqli_query($dbconn, "SELECT * FROM `Plant` WHERE `Code`='$code' LIMIT 1");
		mysqli_close($dbconn);
		
		if(mysqli_num_rows($query) == 0){
			return null;
		}
		
		$plantRow = mysqli_fetch_assoc($query);
		
		$id = $plantRow["ID"];
		$site = Site::findByID($plantRow["SiteFK"]);
		$circle = $plantRow["Circle"];
		$orientation = $plantRow["Orientation"];
		$species = $plantRow["Species"];
		
		return new Plant($id, $site, $circle, $orientation, $code, $species);
	}
	
	public static function findBySiteAndPosition($site, $circle, $orientation) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		$site = self::validSite($dbconn, $site);
		$circle = self::validCircleFormat($dbconn, $circle);
		$orientation = self::validOrientationFormat($dbconn, $orientation);
		if($site === false || $circle === false || $orientation === false){
			return null;
		}
		$query = mysqli_query($dbconn, "SELECT `ID` FROM `Plant` WHERE `SiteFK`='" . $site->getID() . "' AND `Circle`='$circle' AND `Orientation`='$orientation' LIMIT 1");
		mysqli_close($dbconn);
		if(mysqli_num_rows($query) == 0){
			return null;
		}
		return self::findByID(intval(mysqli_fetch_assoc($query)["ID"]));
	}
	
	public static function findPlantsBySite($site){
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT * FROM `Plant` WHERE `SiteFK`='" . $site->getID() . "' AND `Circle`>0");
		mysqli_close($dbconn);
		
		$plantsArray = array();
		while($plantRow = mysqli_fetch_assoc($query)){
			$id = $plantRow["ID"];
			$circle = $plantRow["Circle"];
			$orientation = $plantRow["Orientation"];
			$code = $plantRow["Code"];
			$species = $plantRow["Species"];
			$plant = new Plant($id, $site, $circle, $orientation, $code, $species);
			
			array_push($plantsArray, $plant);
		}
		return $plantsArray;
	}

//GETTERS
	public function getID() {
		if($this->deleted){return null;}
		return intval($this->id);
	}
	
	public function getSite() {
		if($this->deleted){return null;}
		return $this->site;
	}
	
	public function getSpecies() {
		if($this->deleted){return null;}
		return $this->species;
	}
	
	public function getCircle() {
		if($this->deleted){return null;}
		return intval($this->circle);
	}
	
	public function getOrientation() {
		if($this->deleted){return null;}
		return $this->orientation;
	}
	
	public function getColor(){
		if($this->deleted){return null;}
		if($this->orientation == "A"){
			return "#ff7575";//red
		}
		else if($this->orientation == "B"){
			return "#75b3ff";//blue
		}
		else if($this->orientation == "C"){
			return "#5abd61";//green
		}
		else if($this->orientation == "D"){
			return "#ffc875";//orange
		}
		else if($this->orientation == "E"){
			return "#9175ff";//purple
		}
		return false;
	}
	
	public function getCode() {
		if($this->deleted){return null;}
		return $this->code;
	}
	
//SETTERS
	public function setSpecies($species) {
		if(!$this->deleted){
			$species = rawurldecode($species);
			if($this->species == $species){return true;}
			$species = self::validSpecies("NO DBCONN NEEDED", $species);
			if($this->species == $species){return true;}
			
			//Update only if needed
			if($species !== false){
				$dbconn = (new Keychain)->getDatabaseConnection();
				mysqli_query($dbconn, "UPDATE Plant SET `Species`='$species' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->species = $species;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setCode($code){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$code = self::validCode($dbconn, $code);
			if($code !== false){
				mysqli_query($dbconn, "UPDATE Plant SET `Code`='$code' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->code = $code;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setCircle($circle){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$circle = self::validCircleFormat($dbconn, $circle);
			if($circle !== false){
				mysqli_query($dbconn, "UPDATE Plant SET `Circle`='$circle' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->circle = $circle;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
//REMOVER
	public function permanentDelete()
	{
		if(!$this->deleted)
		{
			$dbconn = (new Keychain)->getDatabaseConnection();
			mysqli_query($dbconn, "DELETE FROM `Plant` WHERE `ID`='" . $this->id . "'");
			$this->deleted = true;
			mysqli_close($dbconn);
			return true;
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

//validity ensurance
	public static function validSite($dbconn, $site){
		if(is_object($site) && get_class($site) == "Site"){
			return $site;
		}
		return false;
	}
	
	public static function validCircleFormat($dbconn, $circle){
		$circle = intval(preg_replace("/[^0-9-]/", "", rawurldecode($circle)));
		if($circle !== 0){
			return $circle;
		}
		return false;
	}
	
	public static function validOrientationFormat($dbconn, $orientation){
		if(in_array($orientation, array("A", "B", "C", "D", "E"))){
			return $orientation;
		}
		return false;
	}
	
	public static function validCode($dbconn, $code){
		$code = mysqli_real_escape_string($dbconn, str_replace("0", "O", preg_replace('/\s+/', '', strtoupper(htmlentities(rawurldecode($code))))));
		
		if($code == ""){
			return false;
		}
		return $code;
	}
	
	public static function validSpecies($dbconn, $species){
		$species = rawurldecode($species);
		if(preg_replace('/\s+/', '', $species) == "" || trim(strtoupper($species)) == "N/A"){return false;}
		
		$species = trim($species);
		$speciesList = array(array("Pacific silver fir", "Abies amabilis"), array("Balsam fir", "Abies balsamea"), array("Bristlecone fir", "Abies bracteata"), array("White fir", "Abies concolor"), array("Fraser fir", "Abies fraseri"), array("Grand fir", "Abies grandis"), array("Subalpine fir", "Abies lasiocarpa"), array("California red fir", "Abies magnifica"), array("Noble fir", "Abies procera"), array("Fir spp.", "Abies spp."), array("Sweet acacia", "Acacia farnesiana"), array("Catclaw acacia", "Acacia greggii"), array("Acacia spp.", "Acacia spp."), array("Florida maple", "Acer barbatum"), array("Trident maple", "Acer buergerianum"), array("Hedge maple", "Acer campestre"), array("Horned maple", "Acer diabolicum"), array("Southern sugar maple", "Acer floridanum"), array("Amur maple", "Acer ginnala"), array("Rocky Mountain maple", "Acer glabrum"), array("Bigtooth maple", "Acer grandidentatum"), array("Chalk maple", "Acer leucoderme"), array("Bigleaf maple", "Acer macrophyllum"), array("Boxelder", "Acer negundo"), array("Black maple", "Acer nigrum"), array("Japanese maple", "Acer palmatum"), array("Striped maple", "Acer pensylvanicum"), array("Norway maple", "Acer platanoides"), array("Red maple", "Acer rubrum"), array("Silver maple", "Acer saccharinum"), array("Sugar maple", "Acer saccharum"), array("Mountain maple", "Acer spicatum"), array("Maple spp.", "Acer spp."), array("Freeman maple", "Acer X freemanii"), array("Everglades palm", "Acoelorraphe wrightii"), array("California buckeye", "Aesculus californica"), array("Yellow buckeye", "Aesculus flava"), array("Ohio buckeye", "Aesculus glabra"), array("Horse chestnut", "Aesculus hippocastanum"), array("Bottlebrush buckeye", "Aesculus parviflora"), array("Red buckeye", "Aesculus pavia"), array("Buckeye spp.", "Aesculus spp."), array("Painted buckeye", "Aesculus sylvatica"), array("Ailanthus", "Ailanthus altissima"), array("Mimosa", "Albizia julibrissin"), array("European alder", "Alnus glutinosa"), array("Speckled alder", "Alnus incana"), array("Arizona alder", "Alnus oblongifolia"), array("White alder", "Alnus rhombifolia"), array("Red alder", "Alnus rubra"), array("Hazel alder", "Alnus serrulata"), array("Alder spp.", "Alnus spp."), array("Common serviceberry", "Amelanchier arborea"), array("Roundleaf serviceberry", "Amelanchier sanguinea"), array("Serviceberry spp.", "Amelanchier spp."), array("Sea torchwood", "Amyris elemifera"), array("Pond-apple", "Annona glabra"), array("Arizona madrone", "Arbutus arizonica"), array("Pacific madrone", "Arbutus menziesii"), array("Madrone spp.", "Arbutus spp."), array("Texas madrone", "Arbutus xalapensis"), array("Dwarf pawpaw", "Asimina pygmea"), array("Pawpaw", "Asimina triloba"), array("Black-mangrove", "Avicennia germinans"), array("Eastern baccharis", "Baccharis halimifolia"), array("Yellow birch", "Betula alleghaniensis"), array("Sweet birch", "Betula lenta"), array("River birch", "Betula nigra"), array("Water birch", "Betula occidentalis"), array("Paper birch", "Betula papyrifera"), array("Gray birch", "Betula populifolia"), array("Birch spp.", "Betula spp."), array("Virginia roundleaf birch", "Betula uber"), array("Northwestern paper birch", "Betula x utahensis"), array("Gumbo limbo", "Bursera simaruba"), array("American beautyberry", "Callicarpa americana"), array("Alaska cedar", "Callitropsis nootkatensis"), array("Incense-cedar", "Calocedrus decurrens"), array("Camellia spp.", "Camellia spp."), array("American hornbeam", "Carpinus caroliniana"), array("Mockernut hickory", "Carya alba"), array("Water hickory", "Carya aquatica"), array("Southern shagbark hickory", "Carya carolinae-septentrionalis"), array("Bitternut hickory", "Carya cordiformis"), array("Scrub hickory", "Carya floridana"), array("Pignut hickory", "Carya glabra"), array("Pecan", "Carya illinoinensis"), array("Shellbark hickory", "Carya laciniosa"), array("Nutmeg hickory", "Carya myristiciformis"), array("Red hickory", "Carya ovalis"), array("Shagbark hickory", "Carya ovata"), array("Sand hickory", "Carya pallida"), array("Hickory spp.", "Carya spp."), array("Black hickory", "Carya texana"), array("Mockernut hickory", "Carya tomentosa"), array("American chestnut", "Castanea dentata"), array("Chinese chestnut", "Castanea mollissima"), array("Allegheny chinquapin", "Castanea pumila"), array("Chestnut spp.", "Castanea spp."), array("Gray sheoak", "Casuarina glauca"), array("Belah", "Casuarina lepidophloia"), array("Sheoak spp.", "Casuarina spp."), array("Southern catalpa", "Catalpa bignonioides"), array("Northern catalpa", "Catalpa speciosa"), array("Catalpa spp.", "Catalpa spp."), array("Sugarberry", "Celtis laevigata"), array("Hackberry", "Celtis occidentalis"), array("Hackberry spp.", "Celtis spp."), array("Eastern redbud", "Cercis canadensis"), array("Curlleaf mountain-mahogany", "Cercocarpus ledifolius"), array("Port-Orford-cedar", "Chamaecyparis lawsoniana"), array("White-cedar spp.", "Chamaecyparis spp."), array("Atlantic white-cedar", "Chamaecyparis thyoides"), array("Fragrant wintersweet", "Chimonanthus praecox"), array("Fringetree", "Chionanthus virginicus"), array("Giant chinkapin", "Chrysolepis chrysophylla"), array("Camphortree", "Cinnamomum camphora"), array("Spiny fiddlewood", "Citharexylum spinosum"), array("Citrus spp.", "Citrus spp."), array("Kentucky yellowwood", "Cladrastis kentukea"), array("Tietongue", "Coccoloba diversifolia"), array("Florida silver palm", "Coccothrinax argentata"), array("Coconut palm", "Cocos nucifera"), array("Soldierwood", "Colubrina elliptica"), array("Bluewood", "Condalia hookeri"), array("Buttonwood-mangrove", "Conocarpus erectus"), array("Anacahuita", "Cordia boissieri"), array("Largeleaf geigertree", "Cordia sebestena"), array("Alternate-leaf dogwood", "Cornus alternifolia"), array("Flowering dogwood", "Cornus florida"), array("Stiff dogwood", "Cornus foemina"), array("Kousa dogwood", "Cornus kousa"), array("Big-leaf dogwood", "Cornus macrophylla"), array("Cornelian-cherry dogwood", "Cornus mas"), array("Pacific dogwood", "Cornus nuttallii"), array("Redosier dogwood", "Cornus sericea"), array("Dogwood spp.", "Cornus spp."), array("American hazelnut", "Corylus americana"), array("Beaked hazel", "Corylus cornuta"), array("Hazelnut", "Corylus spp."), array("Smoketree", "Cotinus obovatus"), array("Brainerd's hawthorn", "Crataegus brainerdii"), array("Pear hawthorn", "Crataegus calpodendron"), array("Fireberry hawthorn", "Crataegus chrysocarpa"), array("Cockspur hawthorn", "Crataegus crus-galli"), array("Broadleaf hawthorn", "Crataegus dilatata"), array("Fanleaf hawthorn", "Crataegus flabellata"), array("Downy hawthorn", "Crataegus mollis"), array("Oneseed hawthorn", "Crataegus monogyna"), array("Scarlet hawthorn", "Crataegus pedicellata"), array("Washington hawthorn", "Crataegus phaenopyrum"), array("Hawthorn spp.", "Crataegus spp."), array("Fleshy hawthorn", "Crataegus succulenta"), array("Dwarf hawthorn", "Crataegus uniflora"), array("Carrotwood", "Cupaniopsis anacardioides"), array("Arizona cypress", "Cupressus arizonica"), array("Modoc cypress", "Cupressus bakeri"), array("Tecate cypress", "Cupressus guadalupensis"), array("MacNab's cypress", "Cupressus macnabiana"), array("Monterey cypress", "Cupressus macrocarpa"), array("Sargent's cypress", "Cupressus sargentii"), array("Cypress spp.", "Cupressus spp."), array("Persimmon spp.", "Diospyros spp."), array("Texas persimmon", "Diospyros texana"), array("Common persimmon", "Diospyros virginiana"), array("Blackbead ebony", "Ebenopsis ebano"), array("Anacua knockaway", "Ehretia anacua"), array("Russian olive", "Elaeagnus angustifolia"), array("Autumn olive", "Elaeagnus umbellata"), array("River redgum", "Eucalyptus camaldulensis"), array("Tasmanian bluegum", "Eucalyptus globulus"), array("Grand eucalyptus", "Eucalyptus grandis"), array("Swampmahogany", "Eucalyptus robusta"), array("Eucalyptus spp.", "Eucalyptus spp."), array("Red stopper", "Eugenia rhombea"), array("European spindletree", "Euonymus europaeus"), array("Hamilton's spindletree", "Euonymus hamiltonianus"), array("Butterbough", "Exothea paniculata"), array("American beech", "Fagus grandifolia"), array("Beech spp.", "Fagus spp."), array("European beech", "Fagus sylvatica"), array("Florida strangler fig", "Ficus aurea"), array("Wild banyantree", "Ficus citrifolia"), array("Forsythia spp.", "Forsythia spp."), array("White ash", "Fraxinus americana"), array("Berlandier ash", "Fraxinus berlandieriana"), array("Carolina ash", "Fraxinus caroliniana"), array("Oregon ash", "Fraxinus latifolia"), array("Black ash", "Fraxinus nigra"), array("Green ash", "Fraxinus pennsylvanica"), array("Pumpkin ash", "Fraxinus profunda"), array("Blue ash", "Fraxinus quadrangulata"), array("Ash spp.", "Fraxinus spp."), array("Texas ash", "Fraxinus texensis"), array("Velvet ash", "Fraxinus velutina"), array("Black huckleberry", "Gaylussacia baccata"), array("Huckleberry spp.", "Gaylussacia spp."), array("Ginkgo", "Ginkgo biloba"), array("Waterlocust", "Gleditsia aquatica"), array("Honeylocust spp.", "Gleditsia spp."), array("Honeylocust", "Gleditsia triacanthos"), array("Loblolly-bay", "Gordonia lasianthus"), array("Beeftree", "Guapira discolor"), array("Kentucky coffeetree", "Gymnocladus dioicus"), array("Carolina silverbell", "Halesia carolina"), array("Two-wing silverbell", "Halesia diptera"), array("Little silverbell", "Halesia parviflora"), array("Silverbell spp.", "Halesia spp."), array("American witch-hazel", "Hamamelis virginiana"), array("Rose of sharon", "Hibiscus syriacus"), array("Manchineel", "Hippomane mancinella"), array("Oakleaf hydrangea", "Hydrangea quercifolia"), array("Hydrangea spp.", "Hydrangea spp."), array("Possumhaw", "Ilex decidua"), array("Mountain holly", "Ilex montana"), array("American holly", "Ilex opaca"), array("Winterberry", "Ilex verticillata"), array("Yaupon", "Ilex vomitoria"), array("Southern California black walnut", "Juglans californica"), array("Butternut", "Juglans cinerea"), array("Northern California black walnut", "Juglans hindsii"), array("Arizona walnut", "Juglans major"), array("Texas walnut", "Juglans microcarpa"), array("Black walnut", "Juglans nigra"), array("Walnut spp.", "Juglans spp."), array("Ashe juniper", "Juniperus ashei"), array("California juniper", "Juniperus californica"), array("Redberry juniper", "Juniperus coahuilensis"), array("Alligator juniper", "Juniperus deppeana"), array("Drooping juniper", "Juniperus flaccida"), array("Oneseed juniper", "Juniperus monosperma"), array("Western juniper", "Juniperus occidentalis"), array("Utah juniper", "Juniperus osteosperma"), array("Pinchot juniper", "Juniperus pinchotii"), array("Rocky Mountain juniper", "Juniperus scopulorum"), array("Redcedar/juniper spp.", "Juniperus spp."), array("Eastern redcedar", "Juniperus virginiana"), array("Mountain laurel", "Kalmia latifolia"), array("Castor aralia", "Kalopanax septemlobus"), array("Golden rain tree", "Koelreuteria elegans"), array("Crepe myrtle spp.", "Lagerstroemia spp."), array("White-mangrove", "Laguncularia racemosa"), array("Tamarack", "Larix laricina"), array("Subalpine larch", "Larix lyallii"), array("Western larch", "Larix occidentalis"), array("Larch spp.", "Larix spp."), array("Great leucaene", "Leucaena pulverulenta"), array("Japanese privet", "Ligustrum japonicum"), array("Privet spp.", "Ligustrum spp."), array("Northern spicebush", "Lindera benzoin"), array("Sweetgum", "Liquidambar styraciflua"), array("Tuliptree", "Liriodendron tulipifera"), array("Tanoak", "Lithocarpus densiflorus"), array("Japanese honeysuckle", "Lonicera japonica"), array("Honeysuckle spp.", "Lonicera spp."), array("Tatarian honeysuckle", "Lonicera tatarica"), array("False tamarind", "Lysiloma latisiliquum"), array("Osage orange", "Maclura pomifera"), array("Cucumbertree", "Magnolia acuminata"), array("Fraser magnolia", "Magnolia fraseri"), array("Southern magnolia", "Magnolia grandiflora"), array("Loebner magnolia", "Magnolia kobus x stellata"), array("Bigleaf magnolia", "Magnolia macrophylla"), array("Pyramid magnolia", "Magnolia pyramidata"), array("Magnolia spp.", "Magnolia spp."), array("Umbrella magnolia", "Magnolia tripetala"), array("Sweetbay", "Magnolia virginiana"), array("Cucumber magnolia", "Magulia acuminata"), array("Southern crab apple", "Malus angustifolia"), array("Siberian crabapple", "Malus baccata"), array("Sweet crab apple", "Malus coronaria"), array("Oregon crab apple", "Malus fusca"), array("Prairie crab apple", "Malus ioensis"), array("Sargent's apple", "Malus sargentii"), array("Apple spp.", "Malus spp."), array("Mango", "Mangifera indica"), array("Melaleuca", "Melaleuca quinquenervia"), array("Chinaberry", "Melia azedarach"), array("Florida poisontree", "Metopium toxiferum"), array("Southern bayberry", "Morella caroliniensis"), array("Wax myrtle", "Morella cerifera"), array("White mulberry", "Morus alba"), array("Texas mulberry", "Morus microphylla"), array("Black mulberry", "Morus nigra"), array("Red mulberry", "Morus rubra"), array("Mulberry spp.", "Morus spp."), array("Water tupelo", "Nyssa aquatica"), array("Swamp tupelo", "Nyssa biflora"), array("Ogeechee tupelo", "Nyssa ogeche"), array("Tupelo spp.", "Nyssa spp."), array("Blackgum", "Nyssa sylvatica"), array("Desert ironwood", "Olneya tesota"), array("Eastern hophornbeam", "Ostrya virginiana"), array("Sourwood", "Oxydendrum arboreum"), array("Persian ironwood", "Parrotia persica"), array("Paulownia empress-tree", "Paulownia tomentosa"), array("Avocado", "Persea americana"), array("Redbay", "Persea borbonia"), array("Bay spp.", "Persea spp."), array("Norway spruce", "Picea abies"), array("Brewer spruce", "Picea breweriana"), array("Engelmann spruce", "Picea engelmannii"), array("White spruce", "Picea glauca"), array("Black spruce", "Picea mariana"), array("Blue spruce", "Picea pungens"), array("Red spruce", "Picea rubens"), array("Sitka spruce", "Picea sitchensis"), array("Spruce spp.", "Picea spp."), array("Whitebark pine", "Pinus albicaulis"), array("Bristlecone pine", "Pinus aristata"), array("Arizona pine", "Pinus arizonica"), array("Knobcone pine", "Pinus attenuata"), array("Foxtail pine", "Pinus balfouriana"), array("Jack pine", "Pinus banksiana"), array("Mexican pinyon pine", "Pinus cembroides"), array("Sand pine", "Pinus clausa"), array("Lodgepole pine", "Pinus contorta"), array("Coulter pine", "Pinus coulteri"), array("Border pinyon", "Pinus discolor"), array("Shortleaf pine", "Pinus echinata"), array("Common pinyon", "Pinus edulis"), array("Slash pine", "Pinus elliottii"), array("Apache pine", "Pinus engelmannii"), array("Limber pine", "Pinus flexilis"), array("Spruce pine", "Pinus glabra"), array("Jeffrey pine", "Pinus jeffreyi"), array("Sugar pine", "Pinus lambertiana"), array("Chihuahua pine", "Pinus leiophylla"), array("Great Basin bristlecone pine", "Pinus longaeva"), array("Singleleaf pinyon", "Pinus monophylla"), array("Western white pine", "Pinus monticola"), array("Bishop pine", "Pinus muricata"), array("Austrian pine", "Pinus nigra"), array("Longleaf pine", "Pinus palustris"), array("Ponderosa pine", "Pinus ponderosa"), array("Table Mountain pine", "Pinus pungens"), array("Parry pinyon pine", "Pinus quadrifolia"), array("Monterey pine", "Pinus radiata"), array("Papershell pinyon pine", "Pinus remota"), array("Red pine", "Pinus resinosa"), array("Pitch pine", "Pinus rigida"), array("California foothill pine", "Pinus sabiniana"), array("Pond pine", "Pinus serotina"), array("Pine spp.", "Pinus spp."), array("Southwestern white pine", "Pinus strobiformis"), array("Eastern white pine", "Pinus strobus"), array("Scotch pine", "Pinus sylvestris"), array("Loblolly pine", "Pinus taeda"), array("Torrey pine", "Pinus torreyana"), array("Virginia pine", "Pinus virginiana"), array("Washoe pine", "Pinus washoensis"), array("Fishpoison tree", "Piscidia piscipula"), array("Water-elm planertree", "Planera aquatica"), array("American sycamore", "Platanus occidentalis"), array("California sycamore", "Platanus racemosa"), array("Sycamore spp.", "Platanus spp."), array("Arizona sycamore", "Platanus wrightii"), array("Silver poplar", "Populus alba"), array("Narrowleaf cottonwood", "Populus angustifolia"), array("Balsam poplar", "Populus balsamifera"), array("Eastern cottonwood", "Populus deltoides"), array("Fremont cottonwood", "Populus fremontii"), array("Bigtooth aspen", "Populus grandidentata"), array("Swamp cottonwood", "Populus heterophylla"), array("Lombardy poplar", "Populus nigra"), array("Cottonwood and poplar spp.", "Populus spp."), array("Quaking aspen", "Populus tremuloides"), array("Honey mesquite", "Prosopis glandulosa"), array("Screwbean mesquite", "Prosopis pubescens"), array("Mesquite spp.", "Prosopis spp."), array("Velvet mesquite", "Prosopis velutina"), array("Allegheny plum", "Prunus alleghaniensis"), array("American plum", "Prunus americana"), array("Chickasaw plum", "Prunus angustifolia"), array("Sweet cherry", "Prunus avium"), array("Sour cherry", "Prunus cerasus"), array("European plum", "Prunus domestica"), array("Bitter cherry", "Prunus emarginata"), array("Mahaleb cherry", "Prunus mahaleb"), array("Beach plum", "Prunus maritima"), array("Japanese apricot", "Prunus mume"), array("Canada plum", "Prunus nigra"), array("Pin cherry", "Prunus pensylvanica"), array("Peach", "Prunus persica"), array("Black cherry", "Prunus serotina"), array("Cherry and plum spp.", "Prunus spp."), array("Weeping cherry", "Prunus subhirtella"), array("Chokecherry", "Prunus virginiana"), array("Chinese quince", "Pseudocydonia sinensis"), array("Bigcone Douglas-fir", "Pseudotsuga macrocarpa"), array("Douglas-fir", "Pseudotsuga menziesii"), array("Douglas-fir spp.", "Pseudotsuga spp."), array("Buffalo nut", "Pyrularia pubera"), array("Pear spp.", "Pyrus spp."), array("California live oak", "Quercus agrifolia"), array("White oak", "Quercus alba"), array("Arizona white oak", "Quercus arizonica"), array("Scrub oak", "Quercus berberidifolia"), array("Swamp white oak", "Quercus bicolor"), array("Buckley oak", "Quercus buckleyi"), array("Canyon live oak", "Quercus chrysolepis"), array("Scarlet oak", "Quercus coccinea"), array("Blue oak", "Quercus douglasii"), array("Northern pin oak", "Quercus ellipsoidalis"), array("Emory oak", "Quercus emoryi"), array("Engelmann oak", "Quercus engelmannii"), array("Southern red oak", "Quercus falcata"), array("Gambel oak", "Quercus gambelii"), array("Oregon white oak", "Quercus garryana"), array("Chisos oak", "Quercus graciliformis"), array("Graves oak", "Quercus gravesii"), array("Gray oak", "Quercus grisea"), array("Silverleaf oak", "Quercus hypoleucoides"), array("Scrub oak", "Quercus ilicifolia"), array("Shingle oak", "Quercus imbricaria"), array("Bluejack oak", "Quercus incana"), array("California black oak", "Quercus kelloggii"), array("Lacey oak", "Quercus laceyi"), array("Turkey oak", "Quercus laevis"), array("Laurel oak", "Quercus laurifolia"), array("California white oak", "Quercus lobata"), array("Overcup oak", "Quercus lyrata"), array("Bur oak", "Quercus macrocarpa"), array("Sand post oak", "Quercus margarettiae"), array("Blackjack oak", "Quercus marilandica"), array("Swamp chestnut oak", "Quercus michauxii"), array("Dwarf live oak", "Quercus minima"), array("Chestnut oak", "Quercus montana"), array("Chinkapin oak", "Quercus muehlenbergii"), array("Chinese evergreen oak", "Quercus myrsinifolia"), array("Water oak", "Quercus nigra"), array("Mexican blue oak", "Quercus oblongifolia"), array("Oglethorpe oak", "Quercus oglethorpensis"), array("Cherrybark oak", "Quercus pagoda"), array("Pin oak", "Quercus palustris"), array("Willow oak", "Quercus phellos"), array("Mexican white oak", "Quercus polymorpha"), array("Dwarf chinkapin oak", "Quercus prinoides"), array("Northern red oak", "Quercus rubra"), array("Netleaf oak", "Quercus rugosa"), array("Shumard oak", "Quercus shumardii"), array("Delta post oak", "Quercus similis"), array("Bastard oak", "Quercus sinuata"), array("Oak spp.", "Quercus spp."), array("Post oak", "Quercus stellata"), array("Texas red oak", "Quercus texana"), array("Black oak", "Quercus velutina"), array("Live oak", "Quercus virginiana"), array("Interior live oak", "Quercus wislizeni"), array("Common buckthorn", "Rhamnus cathartica"), array("Buckthorn", "Rhamnus spp."), array("American mangrove", "Rhizophora mangle"), array("Coastal azalea", "Rhododendron atlanticum"), array("Florida azalea", "Rhododendron austrinum"), array("Piedmont azalea", "Rhododendron canescens"), array("Catawba rhododendron", "Rhododendron catawbiense"), array("Great rhododendron", "Rhododendron maximum"), array("Plumleaf azalea", "Rhododendron prunifolium"), array("Rhododendron spp.", "Rhododendron spp."), array("Smooth sumac", "Rhus glabra"), array("Sumac spp.", "Rhus spp."), array("New Mexico locust", "Robinia neomexicana"), array("Black locust", "Robinia pseudoacacia"), array("Royal palm spp.", "Roystonea spp."), array("Mexican palmetto", "Sabal mexicana"), array("Cabbage palmetto", "Sabal palmetto"), array("White willow", "Salix alba"), array("Peachleaf willow", "Salix amygdaloides"), array("Bebb willow", "Salix bebbiana"), array("Bonpland willow", "Salix bonplandiana"), array("Coastal plain willow", "Salix caroliniana"), array("Black willow", "Salix nigra"), array("Balsam willow", "Salix pyrifolia"), array("Scouler's willow", "Salix scouleriana"), array("Weeping willow", "Salix sepulcralis"), array("Willow spp.", "Salix spp."), array("Red elderberry", "Sambucus racemosa"), array("Elderberry", "Sambucus spp."), array("Western soapberry", "Sapindus saponaria"), array("Sassafras", "Sassafras albidum"), array("Octopus tree", "Schefflera actinophylla"), array("Redwood", "Sequoia sempervirens"), array("Giant sequoia", "Sequoiadendron giganteum"), array("False mastic", "Sideroxylon foetidissimum"), array("Chittamwood", "Sideroxylon lanuginosum"), array("White bully", "Sideroxylon salicifolium"), array("Paradisetree", "Simarouba glauca"), array("Texas sophora", "Sophora affinis"), array("American mountain-ash", "Sorbus americana"), array("European mountain-ash", "Sorbus aucuparia"), array("Northern mountain-ash", "Sorbus decora"), array("Mountain-ash spp.", "Sorbus spp."), array("American bladdernut", "Staphylea trifolia"), array("Upright stewartia", "Stewartia rostrata"), array("Japanese snowbell", "Styrax japonicus"), array("West Indian mahogany", "Swietenia mahagoni"), array("Common sweetleaf", "Symplocos tinctoria"), array("Lilac spp.", "Syringa spp."), array("Java plum", "Syzygium cumini"), array("Tamarind", "Tamarindus indica"), array("Saltcedar", "Tamarix spp."), array("Pond cypress", "Taxodium ascendens"), array("Bald cypress", "Taxodium distichum"), array("Montezuma baldcypress", "Taxodium mucronatum"), array("Bald cypress spp.", "Taxodium spp."), array("Pacific yew", "Taxus brevifolia"), array("Florida yew", "Taxus floridana"), array("Yew spp.", "Taxus spp."), array("Key thatch palm", "Thrinax morrisii"), array("Florida thatch palm", "Thrinax radiata"), array("Northern white-cedar", "Thuja occidentalis"), array("Western redcedar", "Thuja plicata"), array("Thuja spp.", "Thuja spp."), array("American basswood", "Tilia americana"), array("Littleleaf linden", "Tilia cordata"), array("Basswood spp.", "Tilia spp."), array("Common linden", "Tilia X europaea"), array("California nutmeg", "Torreya californica"), array("Torreya spp.", "Torreya spp."), array("Florida nutmeg", "Torreya taxifolia"), array("Chinese tallowtree", "Triadica sebifera"), array("Eastern hemlock", "Tsuga canadensis"), array("Carolina hemlock", "Tsuga caroliniana"), array("Western hemlock", "Tsuga heterophylla"), array("Mountain hemlock", "Tsuga mertensiana"), array("Hemlock spp.", "Tsuga spp."), array("Winged elm", "Ulmus alata"), array("American elm", "Ulmus americana"), array("Cedar elm", "Ulmus crassifolia"), array("Russian elm", "Ulmus laevis"), array("Siberian elm", "Ulmus pumila"), array("Slippery elm", "Ulmus rubra"), array("September elm", "Ulmus serotina"), array("Elm spp.", "Ulmus spp."), array("Rock elm", "Ulmus thomasii"), array("California-laurel", "Umbellularia californica"), array("New Jersey blueberry", "Vaccinium caesariense"), array("Highbush blueberry", "Vaccinium corymbosum"), array("Blueberry spp.", "Vaccinium spp."), array("Deerberry", "Vaccinium stamineum"), array("Tungoil tree", "Vernicia fordii"), array("Mapleleaf viburnum", "Viburnum acerifolium"), array("Arrowwood", "Viburnum dentatum"), array("Linden arrowwood", "Viburnum dilatatum"), array("Nannyberry", "Viburnum lentago"), array("Possumhaw viburnum", "Viburnum nudum"), array("Japanese snowball", "Viburnum plicatum"), array("Blackhaw", "Viburnum prunifolium"), array("Rusty viburnum", "Viburnum rufidulum"), array("American cranberrybush", "Viburnum trilobum"), array("Joshua tree", "Yucca brevifolia"));
		for($i = 0; $i < count($speciesList); $i++){
			$speciesList[$i][0] = trim(preg_replace('!\s+!', ' ', $speciesList[$i][0]));
			$speciesList[$i][1] = trim(preg_replace('!\s+!', ' ', $speciesList[$i][1]));
			if(strtolower($species) == strtolower($speciesList[$i][1]) || strtolower($species) == strtolower($speciesList[$i][0])){
				return $speciesList[$i][0];
			}
		}
		return ucfirst(strtolower(trim(preg_replace('!\s+!', ' ', $species))));
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

//FUNCTIONS
	public static function IDToCode($id){
		$chars = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
		
		//get the length of the code we will be returning
		$codeLength = 0;
		$previousIterations = 0;
		while(true){
			$nextIterations = pow(count($chars), ++$codeLength);
			if($id <= $previousIterations + $nextIterations){
				break;
			}
			$previousIterations += $nextIterations;
		}
		
		//and, for every character that will be in the code...
		$code = "";
		$index = $id - 1;
		$iterationsFromPreviousSets = 0;
		for($i = 0; $i < $codeLength; $i++){
			//generate the character from the id
			if($i > 0){
				$iterationsFromPreviousSets += pow(count($chars), $i);
			}
			$newChar = $chars[floor(($index - $iterationsFromPreviousSets) / pow(count($chars), $i)) % count($chars)];
			
			//and add it to the code
			$code = $newChar . $code;
		}
		
		//then, return a sanitized version of the full code that is safe to use with a MySQL query
		$dbconn = (new Keychain)->getDatabaseConnection();
		$code = mysqli_real_escape_string($dbconn, htmlentities($code));
		mysqli_close($dbconn);
		return $code;
	}
}		
?>
