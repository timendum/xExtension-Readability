<?php
require_once __DIR__ . "/vendor/autoload.php";


use \fivefilters\Readability\Readability;
use \fivefilters\Readability\Configuration;


class ReadabilityExtension extends Minz_Extension {

    private $feeds;
    private $cats;
    private $rStore;

    public function init() {
        $this->registerHook('entry_before_insert', array($this, 'fetchStuff'));
        if (is_null(FreshRSS_Context::$user_conf->read_ext_readability)) {
		FreshRSS_Context::$user_conf->read_ext_readability = "[]";
	 	FreshRSS_Context::$user_conf->save();
	}

    }

    public function fetchStuff($entry) {
	
	$this->loadConfigValues();
	$host = '';
	$id = $entry->toArray()['id_feed'];

	if (!array_key_exists($id, $this->rStore) ) {
		return $entry;
	}
	$readability = new Readability(new Configuration([
		'fixRelativeURLs' => true,
		'originalURL'     => $entry->link(),
	]));

	$c = curl_init($entry->link());
	$headers[] = 'Accept: text/*';
	curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	$cresult = curl_exec($c);
	$c_status = curl_getinfo($c, CURLINFO_HTTP_CODE);

	Minz_Log::debug(__METHOD__ . ': Curl returned ' . $c_status, LOG_FILENAME);
	if ($c_status !== 200) {
	    return $entry;
	}

	try {
		$readability->parse($cresult);
		$entry->_content($readability->getContent());
		return $entry;
	} catch (Exception $e) {
		Minz_Log::error("Readability error - ".$e);
	}
    }

    /*
     * These are called from configure.phtml, which is controlled by handleConfigureAction(), 
     * thus values are already fetched from userconfig and FeedDAO.
     */

    public function getReadHost() {
	    return $this->readHost;
    }

    public function getFeeds() {
	    return $this->feeds;
    }

    public function getCategories() {
	    return $this->cats;
    }

    /*
    Loading basic variables from user storage
    */
    public function loadConfigValues()
    {
        if (!class_exists('FreshRSS_Context', false) || null === FreshRSS_Context::$user_conf) {
            return;
	}

        $this->rStore = [];
	if (FreshRSS_Context::$user_conf->read_ext_readability != '') {
		try {
            		$this->rStore = json_decode(FreshRSS_Context::$user_conf->read_ext_readability, true);
		} catch (TypeError $e) {
			// ok	
		}
	}
    }

    public function getConfStoreR($id ) {
		return array_key_exists($id, $this->rStore);
    }
    
    /*
     * handleConfigureAction() is only executed on loading and saving the extenstion's configuration page.
     * If the Request type is POST, values are being saved. It looks weird, but I copied it from another example and it works flawlessly.
     */
    public function handleConfigureAction()
    {
	$feedDAO = FreshRSS_Factory::createFeedDao();
	$catDAO = FreshRSS_Factory::createCategoryDao();
	$this->feeds = $feedDAO->listFeeds();
	$this->cats = $catDAO->listCategories(true,false); 

	if (Minz_Request::isPost()) {
	    $rstore = [];
	    foreach ( $this->feeds as $f ) {
	            //I rather encode only a few 'true' entries, than 400+ false entries + the few 'true' entries	    
		    if ((bool)Minz_Request::param("read_".$f->id(), 0)){
			    $rstore[$f->id()] = true;
		    }
	    }
	    // I don't know if it's possible to save arrays, so it's encoded with json
	    FreshRSS_Context::$user_conf->read_ext_readability = (string)json_encode($rstore);

	    FreshRSS_Context::$user_conf->save();
	}


	$this->loadConfigValues();
    }



}
