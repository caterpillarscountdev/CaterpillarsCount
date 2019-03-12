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
		$speciesList = array(array("Swamp cottonwood", "Populus heterophylla"), array("Plains cottonwood", "Populus deltoides"), array("Quaking aspen", "Populus tremuloides"), array("Black cottonwood", "Populus balsamifera"), array("Fremont cottonwood", "Populus fremontii"), array("Narrowleaf cottonwood", "Populus angustifolia"), array("Silver poplar", "Populus alba"), array("Lombardy poplar", "Populus nigra"), array("Mesquite spp.", "Prosopis spp."), array("Honey mesquite ", "Prosopis glandulosa"), array("Velvet mesquite", "Prosopis velutina"), array("Screwbean mesquite", "Prosopis pubescens"), array("Cherry and plum spp.", "Prunus spp."), array("Pin cherry", "Prunus pensylvanica"), array("Black cherry", "Prunus serotina"), array("Chokecherry", "Prunus virginiana"), array("Peach", "Prunus persica"), array("Canada plum", "Prunus nigra"), array("American plum", "Prunus americana"), array("Bitter cherry", "Prunus emarginata"), array("Allegheny plum", "Prunus alleghaniensis"), array("Chickasaw plum", "Prunus angustifolia"), array("Sweet cherry", "Prunus avium"), array("Sour cherry", "Prunus cerasus"), array("European plum", "Prunus domestica"), array("Mahaleb cherry", "Prunus mahaleb"), array("Oak spp.", "Quercus spp."), array("California live oak", "Quercus agrifolia"), array("White oak", "Quercus alba"), array("Arizona white oak", "Quercus arizonica"), array("Swamp white oak", "Quercus bicolor"), array("Canyon live oak", "Quercus chrysolepis"), array("Scarlet oak", "Quercus coccinea"), array("Blue oak", "Quercus douglasii"), array("Durand oak", "Quercus sinuata"), array("Northern pin oak", "Quercus ellipsoidalis"), array("Emory oak", "Quercus emoryi"), array("Engelmann oak", "Quercus engelmannii"), array("Southern red oak", "Quercus falcata"), array("Cherrybark oak", "Quercus pagoda"), array("Gambel oak", "Quercus gambelii"), array("Oregon white oak", "Quercus garryana"), array("Scrub oak", "Quercus ilicifolia"), array("Shingle oak", "Quercus imbricaria"), array("California black oak", "Quercus kelloggii"), array("Turkey oak", "Quercus laevis"), array("Laurel oak", "Quercus laurifolia"), array("California white oak", "Quercus lobata"), array("Overcup oak", "Quercus lyrata"), array("Bur oak", "Quercus macrocarpa"), array("Blackjack oak", "Quercus marilandica"), array("Swamp chestnut oak", "Quercus michauxii"), array("Chinkapin oak", "Quercus muehlenbergii"), array("Water oak", "Quercus nigra"), array("Texas red oak", "Quercus texana"), array("Mexican blue oak", "Quercus oblongifolia"), array("Pin oak", "Quercus palustris"), array("Willow oak", "Quercus phellos"), array("Bigtooth aspen", "Populus grandidentata"), array("Chestnut oak", "Quercus prinus"), array("Northern red oak", "Quercus rubra"), array("Shumard oak", "Quercus shumardii"), array("Post oak", "Quercus stellata"), array("Delta post oak", "Quercus similis"), array("Black oak", "Quercus velutina"), array("Live oak", "Quercus virginiana"), array("Interior live oak", "Quercus wislizeni"), array("Dwarf post oak", "Quercus margarettiae"), array("Dwarf live oak", "Quercus minima"), array("Bluejack oak", "Quercus incana"), array("Silverleaf oak", "Quercus hypoleucoides"), array("Oglethorpe oak", "Quercus oglethorpensis"), array("Dwarf chinkapin oak", "Quercus prinoides"), array("Gray oak", "Quercus grisea"), array("Netleaf oak", "Quercus rugosa"), array("Chisos oak", "Quercus graciliformis"), array("Sea torchwood", "Amyris elemifera"), array("Pond-apple ", "Annona glabra"), array("Gumbo limbo ", "Bursera simaruba"), array("Sheoak spp.", "Casuarina spp."), array("Gray sheoak", "Casuarina glauca"), array("Belah", "Casuarina lepidophloia"), array("Camphortree", "Cinnamomum camphora"), array("Florida fiddlewood", "Citharexylum fruticosum"), array("Citrus spp.", "Citrus spp."), array("Tietongue", "Coccoloba diversifolia"), array("Soldierwood", "Colubrina elliptica"), array("Largeleaf geigertree", "Cordia sebestena"), array("Carrotwood", "Cupaniopsis anacardioides"), array("Bluewood", "Condalia hookeri"), array("Blackbead ebony", "Ebenopsis ebano"), array("Great leucaene", "Leucaena pulverulenta"), array("Texas sophora", "Sophora affinis"), array("Red stopper", "Eugenia rhombea"), array("Butterbough", "Exothea paniculata"), array("Florida strangler fig", "Ficus aurea"), array("Wild banyantree", "Ficus citrifolia"), array("Beeftree", "Guapira discolor"), array("Manchineel", "Hippomane mancinella"), array("False tamarind", "Lysiloma latisiliquum"), array("Mango", "Mangifera indica"), array("Florida poisontree", "Metopium toxiferum"), array("Fishpoison tree", "Piscidia piscipula"), array("Octopus tree", "Schefflera actinophylla"), array("False mastic", "Sideroxylon foetidissimum"), array("White bully", "Sideroxylon salicifolium"), array("Paradisetree", "Simarouba glauca"), array("Java plum", "Syzygium cumini"), array("Tamarind", "Tamarindus indica"), array("Black locust", "Robinia pseudoacacia"), array("New mexico locust", "Robinia neomexicana"), array("Everglades palm", "Acoelorraphe wrightii"), array("Florida silver palm", "Coccothrinax argentata"), array("Coconut palm ", "Cocos nucifera"), array("Royal palm spp.", "Roystonea spp."), array("Mexican palmetto", "Sabal mexicana"), array("Cabbage palmetto", "Sabal palmetto"), array("Key thatch palm", "Thrinax morrisii"), array("Florida thatch palm", "Thrinax radiata"), array("Other palms", "Family arecaceae not listed above"), array("Western soapberry", "Sapindus saponaria"), array("Willow spp.", "Salix spp."), array("Peachleaf willow", "Salix amygdaloides"), array("Black willow", "Salix nigra"), array("Bebb willow", "Salix bebbiana"), array("Bonpland willow", "Salix bonplandiana"), array("Coastal plain willow", "Salix caroliniana"), array("Balsam willow", "Salix pyrifolia"), array("White willow", "Salix alba"), array("Scouler's willow", "Salix scouleriana"), array("Weeping willow", "Salix sepulcralis"), array("Sassafras", "Sassafras albidum"), array("Mountain-ash spp.", "Sorbus spp."), array("American mountain-ash", "Sorbus americana"), array("European mountain-ash", "Sorbus aucuparia"), array("Northern mountain-ash", "Sorbus decora"), array("West indian mahogany", "Swietenia mahagoni"), array("Basswood spp.", "Tilia spp."), array("American basswood", "Tilia americana"), array("White basswood", "Tilia americana"), array("Carolina basswood", "Tilia americana"), array("Elm spp.", "Ulmus spp."), array("Winged elm", "Ulmus alata"), array("American elm", "Ulmus americana"), array("Cedar elm", "Ulmus crassifolia"), array("Siberian elm", "Ulmus pumila"), array("Slippery elm", "Ulmus rubra"), array("September elm", "Ulmus serotina"), array("Rock elm", "Ulmus thomasii"), array("California-laurel", "Umbellularia californica"), array("Joshua tree", "Yucca brevifolia"), array("Black-mangrove", "Avicennia germinans"), array("Buttonwood-mangrove", "Conocarpus erectus"), array("White-mangrove", "Laguncularia racemosa"), array("American mangrove", "Rhizophora mangle"), array("Desert ironwood", "Olneya tesota"), array("Saltcedar", "Tamarix spp."), array("Melaleuca", "Melaleuca quinquenervia"), array("Chinaberry", "Melia azedarach"), array("Chinese tallowtree", "Triadica sebifera"), array("Tungoil tree", "Vernicia fordii"), array("Smoketree", "Cotinus obovatus"), array("Russian-olive", "Elaeagnus angustifolia"), array("Washington hawthorn", "Crataegus phaenopyrum"), array("Fleshy hawthorn", "Crataegus succulenta"), array("Dwarf hawthorn", "Crataegus uniflora"), array("Berlandier ash", "Fraxinus berlandieriana"), array("Avocado", "Persea americana"), array("Graves oak", "Quercus gravesii"), array("Mexican white oak", "Quercus polymorpha"), array("Buckley oak", "Quercus buckleyi"), array("Lacey oak", "Quercus laceyi"), array("Anacahuita", "Cordia boissieri"), array("Fir spp.", "Abies spp."), array("Pacific silver fir", "Abies amabilis"), array("Balsam fir", "Abies balsamea"), array("Santa lucia or bristlecone fir", "Abies bracteata"), array("White fir", "Abies concolor"), array("Fraser fir", "Abies fraseri"), array("Grand fir", "Abies grandis"), array("Corkbark fir", "Abies lasiocarpa"), array("Subalpine fir", "Abies lasiocarpa"), array("California red fir", "Abies magnifica"), array("Shasta red fir", "Abies shastensis"), array("Noble fir", "Abies procera"), array("White-cedar spp.", "Chamaecyparis spp."), array("Port-orford-cedar", "Chamaecyparis lawsoniana"), array("Alaska yellow-cedar", "Chamaecyparis nootkatensis"), array("Atlantic white-cedar", "Chamaecyparis thyoides"), array("Cypress", "Cupressus spp."), array("Arizona cypress", "Cupressus arizonica"), array("Modoc cypress", "Cupressus bakeri"), array("Tecate cypress", "Cupressus forbesii"), array("Monterey cypress", "Cupressus macrocarpa"), array("Sargent's cypress", "Cupressus sargentii"), array("Macnab's cypress", "Cupressus macnabiana"), array("Redcedar/juniper spp.", "Juniperus spp."), array("Pinchot juniper", "Juniperus pinchotii"), array("Redberry juniper", "Juniperus coahuilensis"), array("Drooping juniper", "Juniperus flaccida"), array("Ashe juniper", "Juniperus ashei"), array("California juniper", "Juniperus californica"), array("Alligator juniper", "Juniperus deppeana"), array("Western juniper", "Juniperus occidentalis"), array("Utah juniper", "Juniperus osteosperma"), array("Rocky mountain juniper", "Juniperus scopulorum"), array("Southern redcedar", "Juniperus virginiana"), array("Eastern redcedar", "Juniperus virginiana"), array("Oneseed juniper", "Juniperus monosperma"), array("Larch spp.", "Larix spp."), array("Tamarack (native)", "Larix laricina"), array("Subalpine larch", "Larix lyallii"), array("Western larch", "Larix occidentalis"), array("Incense-cedar", "Calocedrus decurrens"), array("Spruce spp.", "Picea spp."), array("Norway spruce", "Picea abies"), array("Brewer spruce", "Picea breweriana"), array("Engelmann spruce", "Picea engelmannii"), array("White spruce", "Picea glauca"), array("Black spruce", "Picea mariana"), array("Blue spruce", "Picea pungens"), array("Red spruce", "Picea rubens"), array("Sitka spruce", "Picea sitchensis"), array("Pine spp.", "Pinus spp."), array("Whitebark pine", "Pinus albicaulis"), array("Bristlecone pine", "Pinus aristata"), array("Knobcone pine", "Pinus attenuata"), array("Foxtail pine", "Pinus balfouriana"), array("Jack pine", "Pinus banksiana"), array("Common pinyon", "Pinus edulis"), array("Sand pine", "Pinus clausa"), array("Lodgepole pine", "Pinus contorta"), array("Coulter pine", "Pinus coulteri"), array("Shortleaf pine", "Pinus echinata"), array("Slash pine", "Pinus elliottii"), array("Apache pine", "Pinus engelmannii"), array("Limber pine", "Pinus flexilis"), array("Southwestern white pine ", "Pinus strobiformis"), array("Spruce pine", "Pinus glabra"), array("Jeffrey pine", "Pinus jeffreyi"), array("Sugar pine", "Pinus lambertiana"), array("Chihuahua pine", "Pinus leiophylla"), array("Western white pine", "Pinus monticola"), array("Bishop pine", "Pinus muricata"), array("Longleaf pine", "Pinus palustris"), array("Ponderosa pine", "Pinus ponderosa"), array("Table mountain pine", "Pinus pungens"), array("Monterey pine", "Pinus radiata"), array("Red pine", "Pinus resinosa"), array("Pitch pine", "Pinus rigida"), array("Gray or california foothill pine", "Pinus sabiniana"), array("Pond pine", "Pinus serotina"), array("Eastern white pine", "Pinus strobus"), array("Scotch pine", "Pinus sylvestris"), array("Loblolly pine", "Pinus taeda"), array("Virginia pine", "Pinus virginiana"), array("Singleleaf pinyon", "Pinus monophylla"), array("Border pinyon", "Pinus discolor"), array("Arizona pine", "Pinus arizonica"), array("Austrian pine", "Pinus nigra"), array("Washoe pine", "Pinus washoensis"), array("Four-leaf or parry pinyon pine", "Pinus quadrifolia"), array("Torrey pine", "Pinus torreyana"), array("Mexican pinyon pine", "Pinus cembroides"), array("Papershell pinyon pine", "Pinus remota"), array("Great basin bristlecone pine", "Pinus longaeva"), array("Arizona pinyon pine", "Pinus monophylla"), array("Honduras pine", "Pinus elliottii"), array("Douglas-fir spp.", "Pseudotsuga spp."), array("Bigcone douglas-fir", "Pseudotsuga macrocarpa"), array("Douglas-fir", "Pseudotsuga menziesii"), array("Redwood", "Sequoia sempervirens"), array("Giant sequoia", "Sequoiadendron giganteum"), array("Baldcypress spp.", "Taxodium spp."), array("Baldcypress", "Taxodium distichum"), array("Pondcypress", "Taxodium ascendens"), array("Montezuma baldcypress", "Taxodium mucronatum"), array("Yew spp.", "Taxus spp."), array("Pacific yew", "Taxus brevifolia"), array("Florida yew", "Taxus floridana"), array("Thuja spp.", "Thuja spp."), array("Northern white-cedar", "Thuja occidentalis"), array("Western redcedar", "Thuja plicata"), array("Torreya spp.", "Torreya spp."), array("California torreya (nutmeg)", "Torreya californica"), array("Florida torreya (nutmeg)", "Torreya taxifolia"), array("Hemlock spp.", "Tsuga spp."), array("Eastern hemlock", "Tsuga canadensis"), array("Carolina hemlock", "Tsuga caroliniana"), array("Western hemlock", "Tsuga heterophylla"), array("Mountain hemlock", "Tsuga mertensiana"), array("Acacia spp.", "Acacia spp."), array("Sweet acacia", "Acacia farnesiana"), array("Catclaw acacia", "Acacia greggii"), array("Maple spp.", "Acer spp."), array("Florida maple", "Acer barbatum"), array("Bigleaf maple", "Acer macrophyllum"), array("Boxelder", "Acer negundo"), array("Black maple", "Acer nigrum"), array("Striped maple", "Acer pensylvanicum"), array("Red maple", "Acer rubrum"), array("Silver maple", "Acer saccharinum"), array("Sugar maple", "Acer saccharum"), array("Mountain maple", "Acer spicatum"), array("Norway maple", "Acer platanoides"), array("Rocky mountain maple", "Acer glabrum"), array("Bigtooth maple", "Acer grandidentatum"), array("Chalk maple", "Acer leucoderme"), array("Buckeye spp.", "Aesculus spp."), array("Ohio buckeye", "Aesculus glabra"), array("Yellow buckeye", "Aesculus flava"), array("California buckeye", "Aesculus californica"), array("Texas buckeye", "Aesculus glabra"), array("Red buckeye", "Aesculus pavia"), array("Painted buckeye", "Aesculus sylvatica"), array("Ailanthus", "Ailanthus altissima"), array("Mimosa", "Albizia julibrissin"), array("Alder spp.", "Alnus spp."), array("Red alder", "Alnus rubra"), array("White alder", "Alnus rhombifolia"), array("Arizona alder", "Alnus oblongifolia"), array("European alder", "Alnus glutinosa"), array("Serviceberry spp.", "Amelanchier spp."), array("Common serviceberry", "Amelanchier arborea"), array("Roundleaf serviceberry", "Amelanchier sanguinea"), array("Madrone spp.", "Arbutus spp."), array("Pacific madrone", "Arbutus menziesii"), array("Arizona madrone", "Arbutus arizonica"), array("Texas madrone", "Arbutus xalapensis"), array("Pawpaw", "Asimina triloba"), array("Birch spp.", "Betula spp."), array("Yellow birch", "Betula alleghaniensis"), array("Sweet birch", "Betula lenta"), array("River birch", "Betula nigra"), array("Water birch", "Betula occidentalis"), array("Paper birch", "Betula papyrifera"), array("Virginia roundleaf birch", "Betula uber"), array("Northwestern paper birch", "Betula x utahensis"), array("Gray birch", "Betula populifolia"), array("Chittamwood", "Sideroxylon lanuginosum"), array("American hornbeam", "Carpinus caroliniana"), array("Hickory spp.", "Carya spp."), array("Water hickory", "Carya aquatica"), array("Bitternut hickory", "Carya cordiformis"), array("Pignut hickory", "Carya glabra"), array("Pecan", "Carya illinoinensis"), array("Shellbark hickory", "Carya laciniosa"), array("Nutmeg hickory", "Carya myristiciformis"), array("Shagbark hickory", "Carya ovata"), array("Black hickory", "Carya texana"), array("Mockernut hickory", "Carya alba"), array("Sand hickory", "Carya pallida"), array("Scrub hickory", "Carya floridana"), array("Red hickory", "Carya ovalis"), array("Southern shagbark hickory", "Carya carolinae-septentrionalis"), array("Chestnut spp.", "Castanea spp."), array("American chestnut", "Castanea dentata"), array("Allegheny chinkapin", "Castanea pumila"), array("Ozark chinkapin", "Castanea pumila"), array("Chinese chestnut", "Castanea mollissima"), array("Giant chinkapin", "Chrysolepis chrysophylla"), array("Catalpa spp.", "Catalpa spp."), array("Southern catalpa", "Catalpa bignonioides"), array("Northern catalpa", "Catalpa speciosa"), array("Hackberry spp.", "Celtis spp."), array("Sugarberry", "Celtis laevigata"), array("Hackberry", "Celtis occidentalis"), array("Netleaf hackberry", "Celtis laevigata"), array("Eastern redbud", "Cercis canadensis"), array("Curlleaf mountain-mahogany", "Cercocarpus ledifolius"), array("Yellowwood", "Cladrastis kentukea"), array("Dogwood spp.", "Cornus spp."), array("Flowering dogwood", "Cornus florida"), array("Pacific dogwood", "Cornus nuttallii"), array("Hawthorn spp.", "Crataegus spp."), array("Cockspur hawthorn", "Crataegus crus-galli"), array("Downy hawthorn", "Crataegus mollis"), array("Brainerd's hawthorn", "Crataegus brainerdii"), array("Pear hawthorn", "Crataegus calpodendron"), array("Fireberry hawthorn", "Crataegus chrysocarpa"), array("Broadleaf hawthorn", "Crataegus dilatata"), array("Fanleaf hawthorn", "Crataegus flabellata"), array("Oneseed hawthorn", "Crataegus monogyna"), array("Scarlet hawthorn", "Crataegus pedicellata"), array("Eucalyptus spp.", "Eucalyptus spp."), array("Tasmanian bluegum", "Eucalyptus globulus"), array("River redgum", "Eucalyptus camaldulensis"), array("Grand eucalyptus", "Eucalyptus grandis"), array("Swampmahogany", "Eucalyptus robusta"), array("Persimmon spp.", "Diospyros spp."), array("Common persimmon", "Diospyros virginiana"), array("Texas persimmon", "Diospyros texana"), array("Anacua knockaway", "Ehretia anacua"), array("American beech", "Fagus grandifolia"), array("Ash spp.", "Fraxinus spp."), array("White ash", "Fraxinus americana"), array("Oregon ash", "Fraxinus latifolia"), array("Black ash", "Fraxinus nigra"), array("Green ash", "Fraxinus pennsylvanica"), array("Pumpkin ash", "Fraxinus profunda"), array("Blue ash", "Fraxinus quadrangulata"), array("Velvet ash", "Fraxinus velutina"), array("Carolina ash", "Fraxinus caroliniana"), array("Texas ash", "Fraxinus texensis"), array("Honeylocust spp.", "Gleditsia spp."), array("Waterlocust", "Gleditsia aquatica"), array("Honeylocust", "Gleditsia triacanthos"), array("Loblolly-bay", "Gordonia lasianthus"), array("Ginkgo", "Ginkgo biloba"), array("Kentucky coffeetree", "Gymnocladus dioicus"), array("Silverbell spp.", "Halesia spp."), array("Carolina silverbell", "Halesia carolina"), array("Two-wing silverbell", "Halesia diptera"), array("Little silverbell", "Halesia parviflora"), array("American holly", "Ilex opaca"), array("Walnut spp.", "Juglans spp."), array("Butternut", "Juglans cinerea"), array("Black walnut", "Juglans nigra"), array("Northern california black walnut", "Juglans hindsii"), array("Southern california black walnut", "Juglans californica"), array("Texas walnut", "Juglans microcarpa"), array("Arizona walnut", "Juglans major"), array("Sweetgum", "Liquidambar styraciflua"), array("Yellow-poplar", "Liriodendron tulipifera"), array("Tanoak", "Lithocarpus densiflorus"), array("Osage-orange", "Maclura pomifera"), array("Magnolia spp.", "Magnolia spp."), array("Cucumbertree", "Magnolia acuminata"), array("Southern magnolia", "Magnolia grandiflora"), array("Sweetbay", "Magnolia virginiana"), array("Bigleaf magnolia", "Magnolia macrophylla"), array("Mountain or fraser magnolia", "Magnolia fraseri"), array("Pyramid magnolia", "Magnolia pyramidata"), array("Umbrella magnolia", "Magnolia tripetala"), array("Apple spp.", "Malus spp."), array("Oregon crab apple", "Malus fusca"), array("Southern crab apple", "Malus angustifolia"), array("Sweet crab apple", "Malus coronaria"), array("Prairie crab apple", "Malus ioensis"), array("Mulberry spp.", "Morus spp."), array("White mulberry", "Morus alba"), array("Red mulberry", "Morus rubra"), array("Texas mulberry", "Morus microphylla"), array("Black mulberry", "Morus nigra"), array("Tupelo spp.", "Nyssa spp."), array("Water tupelo", "Nyssa aquatica"), array("Ogeechee tupelo", "Nyssa ogeche"), array("Blackgum", "Nyssa sylvatica"), array("Swamp tupelo", "Nyssa biflora"), array("Eastern hophornbeam", "Ostrya virginiana"), array("Sourwood", "Oxydendrum arboreum"), array("Paulownia empress-tree", "Paulownia tomentosa"), array("Bay spp.", "Persea spp."), array("Redbay", "Persea borbonia"), array("Water-elm planertree", "Planera aquatica"), array("Sycamore spp.", "Platanus spp."), array("California sycamore", "Platanus racemosa"), array("American sycamore", "Platanus occidentalis"), array("Arizona sycamore", "Platanus wrightii"), array("Cottonwood and poplar spp.", "Populus spp."), array("Balsam poplar", "Populus balsamifera"), array("Eastern cottonwood", "Populus deltoides"));
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
