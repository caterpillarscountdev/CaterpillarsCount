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
		if(mysqli_num_rows($query) == 1 && intval(mysqli_fetch_assoc($query)["ID"]) <= $MIN_ID){
			$query = mysqli_query($dbconn, "SELECT t1.ID+1 AS NextID FROM `Plant` AS t1 LEFT JOIN `Plant` AS t2 ON t1.ID+1=t2.ID WHERE t2.ID IS NULL");
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
		$id = mysqli_real_escape_string($dbconn, $id);
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
		$query = mysqli_query($dbconn, "SELECT `ID` FROM `Plant` WHERE `Code`='$code' LIMIT 1");
		mysqli_close($dbconn);
		if(mysqli_num_rows($query) == 0){
			return null;
		}
		return self::findByID(intval(mysqli_fetch_assoc($query)["ID"]));
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
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

//validity insurance
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
		$code = mysqli_real_escape_string($dbconn, str_replace("0", "O", preg_replace('/\s+/', '', strtoupper(rawurldecode($code)))));
		
		if($code == ""){
			return false;
		}
		return $code;
	}
	
	public static function validSpecies($dbconn, $species){
		$species = rawurldecode($species);
		if(preg_replace('/\s+/', '', $species) == "" || trim(strtoupper($species)) == "N/A"){return false;}
		
		$species = trim($species);
		$speciesList = array(array("Pacific silver fir", "Abies amabilis"), array("balsam fir", "Abies balsamea"), array("bristlecone fir", "Abies bracteata"), array("white fir", "Abies concolor"), array("Fraser fir", "Abies fraseri"), array("grand fir", "Abies grandis"), array("subalpine fir", "Abies lasiocarpa"), array("California red fir", "Abies magnifica"), array("noble fir", "Abies procera"), array("fir spp.", "Abies spp."), array("sweet acacia", "Acacia farnesiana"), array("catclaw acacia", "Acacia greggii"), array("acacia spp.", "Acacia spp."), array("Florida maple", "Acer barbatum"), array("trident maple", "Acer buergerianum"), array("hedge maple", "Acer campestre"), array("horned maple", "Acer diabolicum"), array("southern sugar maple", "Acer floridanum"), array("Amur maple", "Acer ginnala"), array("Rocky Mountain maple", "Acer glabrum"), array("bigtooth maple", "Acer grandidentatum"), array("chalk maple", "Acer leucoderme"), array("bigleaf maple", "Acer macrophyllum"), array("boxelder", "Acer negundo"), array("black maple", "Acer nigrum"), array("Japanese maple", "Acer palmatum"), array("striped maple", "Acer pensylvanicum"), array("Norway maple", "Acer platanoides"), array("red maple", "Acer rubrum"), array("silver maple", "Acer saccharinum"), array("sugar maple", "Acer saccharum"), array("mountain maple", "Acer spicatum"), array("maple spp.", "Acer spp."), array("Freeman maple", "Acer X freemanii"), array("Everglades palm", "Acoelorraphe wrightii"), array("California buckeye", "Aesculus californica"), array("yellow buckeye", "Aesculus flava"), array("Ohio buckeye", "Aesculus glabra"), array("Horse chestnut", "Aesculus hippocastanum"), array("Bottlebrush buckeye", "Aesculus parviflora"), array("red buckeye", "Aesculus pavia"), array("buckeye spp.", "Aesculus spp."), array("painted buckeye", "Aesculus sylvatica"), array("ailanthus", "Ailanthus altissima"), array("mimosa", "Albizia julibrissin"), array("European alder", "Alnus glutinosa"), array("speckled alder", "Alnus incana"), array("Arizona alder", "Alnus oblongifolia"), array("white alder", "Alnus rhombifolia"), array("red alder", "Alnus rubra"), array("hazel alder", "Alnus serrulata"), array("alder spp.", "Alnus spp."), array("common serviceberry", "Amelanchier arborea"), array("roundleaf serviceberry", "Amelanchier sanguinea"), array("serviceberry spp.", "Amelanchier spp."), array("sea torchwood", "Amyris elemifera"), array("pond-apple  ", "Annona glabra"), array("Arizona madrone", "Arbutus arizonica"), array("Pacific madrone", "Arbutus menziesii"), array("madrone spp.", "Arbutus spp."), array("Texas madrone", "Arbutus xalapensis"), array("dwarf pawpaw", "Asimina pygmea"), array("pawpaw", "Asimina triloba"), array("black-mangrove", "Avicennia germinans"), array("eastern baccharis", "Baccharis halimifolia"), array("yellow birch", "Betula alleghaniensis"), array("sweet birch", "Betula lenta"), array("river birch", "Betula nigra"), array("water birch", "Betula occidentalis"), array("paper birch", "Betula papyrifera"), array("gray birch", "Betula populifolia"), array("birch spp.", "Betula spp."), array("Virginia roundleaf birch", "Betula uber"), array("northwestern paper birch", "Betula x utahensis"), array("gumbo limbo  ", "Bursera simaruba"), array("American beautyberry", "Callicarpa americana"), array("Alaska cedar", "Callitropsis nootkatensis"), array("incense-cedar", "Calocedrus decurrens"), array("camellia spp.", "Camellia spp."), array("American hornbeam", "Carpinus caroliniana"), array("mockernut hickory", "Carya alba"), array("water hickory", "Carya aquatica"), array("southern shagbark hickory", "Carya carolinae-septentrionalis"), array("bitternut hickory", "Carya cordiformis"), array("scrub hickory", "Carya floridana"), array("pignut hickory", "Carya glabra"), array("pecan", "Carya illinoinensis"), array("shellbark hickory", "Carya laciniosa"), array("nutmeg hickory", "Carya myristiciformis"), array("red hickory", "Carya ovalis"), array("shagbark hickory", "Carya ovata"), array("sand hickory", "Carya pallida"), array("hickory spp.", "Carya spp."), array("black hickory", "Carya texana"), array("mockernut hickory", "Carya tomentosa"), array("American chestnut", "Castanea dentata"), array("Chinese chestnut", "Castanea mollissima"), array("Allegheny chinquapin", "Castanea pumila"), array("chestnut spp.", "Castanea spp."), array("gray sheoak", "Casuarina glauca"), array("belah", "Casuarina lepidophloia"), array("sheoak spp.", "Casuarina spp."), array("southern catalpa", "Catalpa bignonioides"), array("northern catalpa", "Catalpa speciosa"), array("catalpa spp.", "Catalpa spp."), array("sugarberry", "Celtis laevigata"), array("hackberry", "Celtis occidentalis"), array("hackberry spp.", "Celtis spp."), array("eastern redbud", "Cercis canadensis"), array("curlleaf mountain-mahogany", "Cercocarpus ledifolius"), array("Port-Orford-cedar", "Chamaecyparis lawsoniana"), array("white-cedar spp.", "Chamaecyparis spp."), array("Atlantic white-cedar", "Chamaecyparis thyoides"), array("fragrant wintersweet", "Chimonanthus praecox"), array("fringetree", "Chionanthus virginicus"), array("giant chinkapin", "Chrysolepis chrysophylla"), array("camphortree", "Cinnamomum camphora"), array("spiny fiddlewood", "Citharexylum spinosum"), array("citrus spp.", "Citrus spp."), array("Kentucky yellowwood", "Cladrastis kentukea"), array("tietongue", "Coccoloba diversifolia"), array("Florida silver palm", "Coccothrinax argentata"), array("coconut palm  ", "Cocos nucifera"), array("soldierwood", "Colubrina elliptica"), array("bluewood", "Condalia hookeri"), array("buttonwood-mangrove", "Conocarpus erectus"), array("anacahuita", "Cordia boissieri"), array("largeleaf geigertree", "Cordia sebestena"), array("alternate-leaf dogwood", "Cornus alternifolia"), array("flowering dogwood", "Cornus florida"), array("stiff dogwood", "Cornus foemina"), array("Kousa dogwood", "Cornus kousa"), array("big-leaf dogwood", "Cornus macrophylla"), array("Cornelian-cherry dogwood", "Cornus mas"), array("Pacific dogwood", "Cornus nuttallii"), array("redosier dogwood", "Cornus sericea"), array("dogwood spp.", "Cornus spp."), array("American hazelnut", "Corylus americana"), array("beaked hazel", "Corylus cornuta"), array("hazelnut", "Corylus spp."), array("smoketree", "Cotinus obovatus"), array("Brainerd's hawthorn", "Crataegus brainerdii"), array("pear hawthorn", "Crataegus calpodendron"), array("fireberry hawthorn", "Crataegus chrysocarpa"), array("cockspur hawthorn", "Crataegus crus-galli"), array("broadleaf hawthorn", "Crataegus dilatata"), array("fanleaf hawthorn", "Crataegus flabellata"), array("downy hawthorn", "Crataegus mollis"), array("oneseed hawthorn", "Crataegus monogyna"), array("scarlet hawthorn", "Crataegus pedicellata"), array("Washington hawthorn", "Crataegus phaenopyrum"), array("hawthorn spp.", "Crataegus spp."), array("fleshy hawthorn", "Crataegus succulenta"), array("dwarf hawthorn", "Crataegus uniflora"), array("carrotwood", "Cupaniopsis anacardioides"), array("Arizona cypress", "Cupressus arizonica"), array("Modoc cypress", "Cupressus bakeri"), array("Tecate cypress", "Cupressus guadalupensis"), array("MacNab's cypress", "Cupressus macnabiana"), array("Monterey cypress", "Cupressus macrocarpa"), array("Sargent's cypress", "Cupressus sargentii"), array("cypress spp.", "Cupressus spp."), array("persimmon spp.", "Diospyros spp."), array("Texas persimmon", "Diospyros texana"), array("common persimmon", "Diospyros virginiana"), array("blackbead ebony", "Ebenopsis ebano"), array("Anacua knockaway", "Ehretia anacua"), array("Russian olive", "Elaeagnus angustifolia"), array("autumn olive", "Elaeagnus umbellata"), array("river redgum", "Eucalyptus camaldulensis"), array("Tasmanian bluegum", "Eucalyptus globulus"), array("grand eucalyptus", "Eucalyptus grandis"), array("swampmahogany", "Eucalyptus robusta"), array("eucalyptus spp.", "Eucalyptus spp."), array("red stopper", "Eugenia rhombea"), array("European spindletree", "Euonymus europaeus"), array("Hamilton's spindletree", "Euonymus hamiltonianus"), array("butterbough", "Exothea paniculata"), array("American beech", "Fagus grandifolia"), array("beech spp.", "Fagus spp."), array("European beech", "Fagus sylvatica"), array("Florida strangler fig", "Ficus aurea"), array("wild banyantree", "Ficus citrifolia"), array("forsythia spp.", "Forsythia spp."), array("white ash", "Fraxinus americana"), array("Berlandier ash", "Fraxinus berlandieriana"), array("Carolina ash", "Fraxinus caroliniana"), array("Oregon ash", "Fraxinus latifolia"), array("black ash", "Fraxinus nigra"), array("green ash", "Fraxinus pennsylvanica"), array("pumpkin ash", "Fraxinus profunda"), array("blue ash", "Fraxinus quadrangulata"), array("ash spp.", "Fraxinus spp."), array("Texas ash", "Fraxinus texensis"), array("velvet ash", "Fraxinus velutina"), array("black huckleberry", "Gaylussacia baccata"), array("huckleberry spp.", "Gaylussacia spp."), array("ginkgo", "Ginkgo biloba"), array("waterlocust", "Gleditsia aquatica"), array("honeylocust spp.", "Gleditsia spp."), array("honeylocust", "Gleditsia triacanthos"), array("loblolly-bay", "Gordonia lasianthus"), array("beeftree", "Guapira discolor"), array("Kentucky coffeetree", "Gymnocladus dioicus"), array("Carolina silverbell", "Halesia carolina"), array("two-wing silverbell", "Halesia diptera"), array("little silverbell", "Halesia parviflora"), array("silverbell spp.", "Halesia spp."), array("American witch-hazel", "Hamamelis virginiana"), array("rose of sharon", "Hibiscus syriacus"), array("manchineel", "Hippomane mancinella"), array("oakleaf hydrangea", "Hydrangea quercifolia"), array("hydrangea spp.", "Hydrangea spp."), array("possumhaw", "Ilex decidua"), array("mountain holly", "Ilex montana"), array("American holly", "Ilex opaca"), array("Winterberry", "Ilex verticillata"), array("yaupon", "Ilex vomitoria"), array("southern California black walnut", "Juglans californica"), array("butternut", "Juglans cinerea"), array("northern California black walnut", "Juglans hindsii"), array("Arizona walnut", "Juglans major"), array("Texas walnut", "Juglans microcarpa"), array("black walnut", "Juglans nigra"), array("walnut spp.", "Juglans spp."), array("Ashe juniper", "Juniperus ashei"), array("California juniper", "Juniperus californica"), array("redberry juniper", "Juniperus coahuilensis"), array("alligator juniper", "Juniperus deppeana"), array("drooping juniper", "Juniperus flaccida"), array("oneseed juniper", "Juniperus monosperma"), array("western juniper", "Juniperus occidentalis"), array("Utah juniper", "Juniperus osteosperma"), array("Pinchot juniper", "Juniperus pinchotii"), array("Rocky Mountain juniper", "Juniperus scopulorum"), array("redcedar/juniper spp.", "Juniperus spp."), array("eastern redcedar", "Juniperus virginiana"), array("mountain laurel", "Kalmia latifolia"), array("Castor aralia", "Kalopanax septemlobus"), array("golden rain tree", "Koelreuteria elegans"), array("crepe myrtle spp.", "Lagerstroemia spp."), array("white-mangrove", "Laguncularia racemosa"), array("tamarack", "Larix laricina"), array("subalpine larch", "Larix lyallii"), array("western larch", "Larix occidentalis"), array("larch spp.", "Larix spp."), array("great leucaene", "Leucaena pulverulenta"), array("Japanese privet", "Ligustrum japonicum"), array("privet spp.", "Ligustrum spp."), array("northern spicebush", "Lindera benzoin"), array("sweetgum", "Liquidambar styraciflua"), array("tuliptree", "Liriodendron tulipifera"), array("tanoak", "Lithocarpus densiflorus"), array("Japanese honeysuckle", "Lonicera japonica"), array("honeysuckle spp.", "Lonicera spp."), array("Tatarian honeysuckle", "Lonicera tatarica"), array("false tamarind", "Lysiloma latisiliquum"), array("Osage orange", "Maclura pomifera"), array("cucumbertree", "Magnolia acuminata"), array("Fraser magnolia", "Magnolia fraseri"), array("southern magnolia", "Magnolia grandiflora"), array("Loebner magnolia", "Magnolia kobus x stellata"), array("bigleaf magnolia", "Magnolia macrophylla"), array("pyramid magnolia", "Magnolia pyramidata"), array("magnolia spp.", "Magnolia spp."), array("umbrella magnolia", "Magnolia tripetala"), array("sweetbay", "Magnolia virginiana"), array("cucumber magnolia", "Magulia acuminata"), array("southern crab apple", "Malus angustifolia"), array("Siberian crabapple", "Malus baccata"), array("sweet crab apple", "Malus coronaria"), array("Oregon crab apple", "Malus fusca"), array("prairie crab apple", "Malus ioensis"), array("Sargent's apple", "Malus sargentii"), array("apple spp.", "Malus spp."), array("mango", "Mangifera indica"), array("melaleuca", "Melaleuca quinquenervia"), array("chinaberry", "Melia azedarach"), array("Florida poisontree", "Metopium toxiferum"), array("southern bayberry", "Morella caroliniensis"), array("wax myrtle", "Morella cerifera"), array("white mulberry", "Morus alba"), array("Texas mulberry", "Morus microphylla"), array("black mulberry", "Morus nigra"), array("red mulberry", "Morus rubra"), array("mulberry spp.", "Morus spp."), array("water tupelo", "Nyssa aquatica"), array("swamp tupelo", "Nyssa biflora"), array("Ogeechee tupelo", "Nyssa ogeche"), array("tupelo spp.", "Nyssa spp."), array("blackgum", "Nyssa sylvatica"), array("desert ironwood", "Olneya tesota"), array("eastern hophornbeam", "Ostrya virginiana"), array("sourwood", "Oxydendrum arboreum"), array("Persian ironwood", "Parrotia persica"), array("paulownia  empress-tree", "Paulownia tomentosa"), array("avocado", "Persea americana"), array("redbay", "Persea borbonia"), array("bay spp.", "Persea spp."), array("Norway spruce", "Picea abies"), array("Brewer spruce", "Picea breweriana"), array("Engelmann spruce", "Picea engelmannii"), array("white spruce", "Picea glauca"), array("black spruce", "Picea mariana"), array("blue spruce", "Picea pungens"), array("red spruce", "Picea rubens"), array("Sitka spruce", "Picea sitchensis"), array("spruce spp.", "Picea spp."), array("whitebark pine", "Pinus albicaulis"), array("bristlecone pine", "Pinus aristata"), array("Arizona pine", "Pinus arizonica"), array("knobcone pine", "Pinus attenuata"), array("foxtail pine", "Pinus balfouriana"), array("jack pine", "Pinus banksiana"), array("Mexican pinyon pine", "Pinus cembroides"), array("sand pine", "Pinus clausa"), array("lodgepole pine", "Pinus contorta"), array("Coulter pine", "Pinus coulteri"), array("border pinyon", "Pinus discolor"), array("shortleaf pine", "Pinus echinata"), array("common pinyon", "Pinus edulis"), array("slash pine", "Pinus elliottii"), array("Apache pine", "Pinus engelmannii"), array("limber pine", "Pinus flexilis"), array("spruce pine", "Pinus glabra"), array("Jeffrey pine", "Pinus jeffreyi"), array("sugar pine", "Pinus lambertiana"), array("Chihuahua pine", "Pinus leiophylla"), array("Great Basin bristlecone pine", "Pinus longaeva"), array("singleleaf pinyon", "Pinus monophylla"), array("western white pine", "Pinus monticola"), array("bishop pine", "Pinus muricata"), array("Austrian pine", "Pinus nigra"), array("longleaf pine", "Pinus palustris"), array("ponderosa pine", "Pinus ponderosa"), array("Table Mountain pine", "Pinus pungens"), array("Parry pinyon pine", "Pinus quadrifolia"), array("Monterey pine", "Pinus radiata"), array("papershell pinyon pine", "Pinus remota"), array("red pine", "Pinus resinosa"), array("pitch pine", "Pinus rigida"), array("California foothill pine", "Pinus sabiniana"), array("pond pine", "Pinus serotina"), array("pine spp.", "Pinus spp."), array("southwestern white pine ", "Pinus strobiformis"), array("eastern white pine", "Pinus strobus"), array("Scotch pine", "Pinus sylvestris"), array("loblolly pine", "Pinus taeda"), array("Torrey pine", "Pinus torreyana"), array("Virginia pine", "Pinus virginiana"), array("Washoe pine", "Pinus washoensis"), array("fishpoison tree", "Piscidia piscipula"), array("water-elm  planertree", "Planera aquatica"), array("American sycamore", "Platanus occidentalis"), array("California sycamore", "Platanus racemosa"), array("sycamore spp.", "Platanus spp."), array("Arizona sycamore", "Platanus wrightii"), array("silver poplar", "Populus alba"), array("narrowleaf cottonwood", "Populus angustifolia"), array("balsam poplar", "Populus balsamifera"), array("eastern cottonwood", "Populus deltoides"), array("Fremont cottonwood", "Populus fremontii"), array("bigtooth aspen", "Populus grandidentata"), array("swamp cottonwood", "Populus heterophylla"), array("Lombardy poplar", "Populus nigra"), array("cottonwood and poplar spp.", "Populus spp."), array("quaking aspen", "Populus tremuloides"), array("honey mesquite ", "Prosopis glandulosa"), array("screwbean mesquite", "Prosopis pubescens"), array("mesquite spp.", "Prosopis spp."), array("velvet mesquite", "Prosopis velutina"), array("Allegheny plum", "Prunus alleghaniensis"), array("American plum", "Prunus americana"), array("Chickasaw plum", "Prunus angustifolia"), array("sweet cherry", "Prunus avium"), array("sour cherry", "Prunus cerasus"), array("European plum", "Prunus domestica"), array("bitter cherry", "Prunus emarginata"), array("Mahaleb cherry", "Prunus mahaleb"), array("beach plum", "Prunus maritima"), array("Japanese apricot", "Prunus mume"), array("Canada plum", "Prunus nigra"), array("pin cherry", "Prunus pensylvanica"), array("peach", "Prunus persica"), array("black cherry", "Prunus serotina"), array("cherry and plum spp.", "Prunus spp."), array("weeping cherry", "Prunus subhirtella"), array("chokecherry", "Prunus virginiana"), array("Chinese quince", "Pseudocydonia sinensis"), array("bigcone Douglas-fir", "Pseudotsuga macrocarpa"), array("Douglas-fir", "Pseudotsuga menziesii"), array("Douglas-fir spp.", "Pseudotsuga spp."), array("buffalo nut", "Pyrularia pubera"), array("pear spp.", "Pyrus spp."), array("California live oak", "Quercus agrifolia"), array("white oak", "Quercus alba"), array("Arizona white oak", "Quercus arizonica"), array("scrub oak", "Quercus berberidifolia"), array("swamp white oak", "Quercus bicolor"), array("Buckley oak", "Quercus buckleyi"), array("canyon live oak", "Quercus chrysolepis"), array("scarlet oak", "Quercus coccinea"), array("blue oak", "Quercus douglasii"), array("northern pin oak", "Quercus ellipsoidalis"), array("Emory oak", "Quercus emoryi"), array("Engelmann oak", "Quercus engelmannii"), array("southern red oak", "Quercus falcata"), array("Gambel oak", "Quercus gambelii"), array("Oregon white oak", "Quercus garryana"), array("Chisos oak", "Quercus graciliformis"), array("Graves oak", "Quercus gravesii"), array("gray oak", "Quercus grisea"), array("silverleaf oak", "Quercus hypoleucoides"), array("scrub oak", "Quercus ilicifolia"), array("shingle oak", "Quercus imbricaria"), array("bluejack oak", "Quercus incana"), array("California black oak", "Quercus kelloggii"), array("Lacey oak", "Quercus laceyi"), array("turkey oak", "Quercus laevis"), array("laurel oak", "Quercus laurifolia"), array("California white oak", "Quercus lobata"), array("overcup oak", "Quercus lyrata"), array("bur oak", "Quercus macrocarpa"), array("sand post oak", "Quercus margarettiae"), array("blackjack oak", "Quercus marilandica"), array("swamp chestnut oak", "Quercus michauxii"), array("dwarf live oak", "Quercus minima"), array("chestnut oak", "Quercus montana"), array("chinkapin oak", "Quercus muehlenbergii"), array("Chinese evergreen oak", "Quercus myrsinifolia"), array("water oak", "Quercus nigra"), array("Mexican blue oak", "Quercus oblongifolia"), array("Oglethorpe oak", "Quercus oglethorpensis"), array("cherrybark oak", "Quercus pagoda"), array("pin oak", "Quercus palustris"), array("willow oak", "Quercus phellos"), array("Mexican white oak", "Quercus polymorpha"), array("dwarf chinkapin oak", "Quercus prinoides"), array("northern red oak", "Quercus rubra"), array("netleaf oak", "Quercus rugosa"), array("Shumard oak", "Quercus shumardii"), array("Delta post oak", "Quercus similis"), array("bastard oak", "Quercus sinuata"), array("oak spp.", "Quercus spp."), array("post oak", "Quercus stellata"), array("Texas red oak", "Quercus texana"), array("black oak", "Quercus velutina"), array("live oak", "Quercus virginiana"), array("interior live oak", "Quercus wislizeni"), array("common buckthorn", "Rhamnus cathartica"), array("buckthorn", "Rhamnus spp."), array("American mangrove", "Rhizophora mangle"), array("coastal azalea", "Rhododendron atlanticum"), array("Florida azalea", "Rhododendron austrinum"), array("Piedmont azalea", "Rhododendron canescens"), array("Catawba rhododendron", "Rhododendron catawbiense"), array("great rhododendron", "Rhododendron maximum"), array("plumleaf azalea", "Rhododendron prunifolium"), array("rhododendron spp.", "Rhododendron spp."), array("smooth sumac", "Rhus glabra"), array("sumac spp.", "Rhus spp."), array("New Mexico locust", "Robinia neomexicana"), array("black locust", "Robinia pseudoacacia"), array("royal palm spp.", "Roystonea spp."), array("Mexican palmetto", "Sabal mexicana"), array("cabbage palmetto", "Sabal palmetto"), array("white willow", "Salix alba"), array("peachleaf willow", "Salix amygdaloides"), array("Bebb willow", "Salix bebbiana"), array("Bonpland willow", "Salix bonplandiana"), array("coastal plain willow", "Salix caroliniana"), array("black willow", "Salix nigra"), array("balsam willow", "Salix pyrifolia"), array("Scouler's willow", "Salix scouleriana"), array("weeping willow", "Salix sepulcralis"), array("willow spp.", "Salix spp."), array("red elderberry", "Sambucus racemosa"), array("elderberry", "Sambucus spp."), array("western soapberry", "Sapindus saponaria"), array("sassafras", "Sassafras albidum"), array("octopus tree", "Schefflera actinophylla"), array("redwood", "Sequoia sempervirens"), array("giant sequoia", "Sequoiadendron giganteum"), array("false mastic", "Sideroxylon foetidissimum"), array("chittamwood", "Sideroxylon lanuginosum"), array("white bully", "Sideroxylon salicifolium"), array("paradisetree", "Simarouba glauca"), array("Texas sophora", "Sophora affinis"), array("American mountain-ash", "Sorbus americana"), array("European mountain-ash", "Sorbus aucuparia"), array("northern mountain-ash", "Sorbus decora"), array("mountain-ash spp.", "Sorbus spp."), array("American bladdernut", "Staphylea trifolia"), array("upright stewartia", "Stewartia rostrata"), array("Japanese snowbell", "Styrax japonicus"), array("west Indian mahogany", "Swietenia mahagoni"), array("common sweetleaf", "Symplocos tinctoria"), array("lilac spp.", "Syringa spp."), array("Java plum", "Syzygium cumini"), array("tamarind", "Tamarindus indica"), array("saltcedar", "Tamarix spp."), array("pond cypress", "Taxodium ascendens"), array("bald cypress", "Taxodium distichum"), array("Montezuma baldcypress", "Taxodium mucronatum"), array("bald cypress spp.", "Taxodium spp."), array("Pacific yew", "Taxus brevifolia"), array("Florida yew", "Taxus floridana"), array("yew spp.", "Taxus spp."), array("key thatch palm", "Thrinax morrisii"), array("Florida thatch palm", "Thrinax radiata"), array("northern white-cedar", "Thuja occidentalis"), array("western redcedar", "Thuja plicata"), array("thuja spp.", "Thuja spp."), array("American basswood", "Tilia americana"), array("littleleaf linden", "Tilia cordata"), array("basswood spp.", "Tilia spp."), array("common linden", "Tilia X europaea"), array("California nutmeg", "Torreya californica"), array("torreya spp.", "Torreya spp."), array("Florida nutmeg", "Torreya taxifolia"), array("Chinese tallowtree", "Triadica sebifera"), array("eastern hemlock", "Tsuga canadensis"), array("Carolina hemlock", "Tsuga caroliniana"), array("western hemlock", "Tsuga heterophylla"), array("mountain hemlock", "Tsuga mertensiana"), array("hemlock spp.", "Tsuga spp."), array("winged elm", "Ulmus alata"), array("American elm", "Ulmus americana"), array("cedar elm", "Ulmus crassifolia"), array("Russian elm", "Ulmus laevis"), array("Siberian elm", "Ulmus pumila"), array("slippery elm", "Ulmus rubra"), array("September elm", "Ulmus serotina"), array("elm spp.", "Ulmus spp."), array("rock elm", "Ulmus thomasii"), array("California-laurel", "Umbellularia californica"), array("New Jersey blueberry", "Vaccinium caesariense"), array("highbush blueberry", "Vaccinium corymbosum"), array("blueberry spp.", "Vaccinium spp."), array("deerberry", "Vaccinium stamineum"), array("tungoil tree", "Vernicia fordii"), array("mapleleaf viburnum", "Viburnum acerifolium"), array("arrowwood", "Viburnum dentatum"), array("linden arrowwood", "Viburnum dilatatum"), array("nannyberry", "Viburnum lentago"), array("possumhaw viburnum", "Viburnum nudum"), array("Japanese snowball", "Viburnum plicatum"), array("blackhaw", "Viburnum prunifolium"), array("rusty viburnum", "Viburnum rufidulum"), array("American cranberrybush", "Viburnum trilobum"), array("Joshua tree", "Yucca brevifolia"));
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
		$code = mysqli_real_escape_string($dbconn, $code);
		mysqli_close($dbconn);
		return $code;
	}
}		
?>
