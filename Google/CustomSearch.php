<?php

namespace ATP\Google;

/* API Template
	https://www.googleapis.com/customsearch/v1?
		key={apiKey}&
		q={searchTerms}&
		cx={cx?}&
		cref={cref?}&
		num={count?}&
		start={startIndex?}&
		lr={language?}&
		safe={safe?}&
		sort={sort?}&
		filter={filter?}&
		gl={gl?}&
		cr={cr?}&
		googlehost={googleHost?}&
		c2coff={disableCnTwTranslation?}&
		hq={hq?}&
		hl={hl?}&
		siteSearch={siteSearch?}&
		siteSearchFilter={siteSearchFilter?}&
		exactTerms={exactTerms?}&
		excludeTerms={excludeTerms?}&
		linkSite={linkSite?}&
		orTerms={orTerms?}&
		relatedSite={relatedSite?}&
		dateRestrict={dateRestrict?}&
		lowRange={lowRange?}&
		highRange={highRange?}&
		searchType={searchType}&
		fileType={fileType?}&
		rights={rights?}&
		imgSize={imgSize?}&
		imgType={imgType?}&
		imgColorType={imgColorType?}&
		imgDominantColor={imgDominantColor?}&
		alt=json
*/

class CustomSearch
{
	private static $_apiUrl = "https://www.googleapis.com/customsearch/v1?";

	private $_data = array();
	
	public function __construct($apiKey)
	{
		$this->key = $apiKey;
	}
	
	public function &__get($key)
	{
		return $this->_data[$key];
	}
	
	public function __set($key, $value)
	{
		$this->_data[$key] = $value;
	}
	
	public function __isset($key)
	{
		return isset($this->_data[$key]);
	}
	
	public function __unset($key)
	{
		unset($this->_data[$key]);
	}
	
	public function query($query, $num = 10, $start = 1)
	{
		$numLeft = 0;
		if($num > 10)
		{
			$numLeft = $num - 10;
			$num = 10;
		}
	
		$this->q = $query;
		$this->num = $num;
		$this->start = $start;
				
		$queryString = \ATP\MapReduce::get()
			->map(function($value, $key){return "{$key}={$value}";})
			->reduce(new \ATP\Reducer\Concatenate("&"))
			->process($this->_data);
			
		$url = self::$_apiUrl . $queryString;
		
		//Set connection parameters
		$clientParams = array(
		);
	
		//Get results
		$html = "";
		$client = new \Zend_Http_Client(\POM\Url::removeSpaces($url),$clientParams);
		$response = json_decode($client->request()->getBody());

		if(!isset($response->items)) return array();
		
		$results = $response->items;
		
		if($numLeft > 0 && count($results) == $num)
		{
			$results = array_merge($results, $this->query($query, $numLeft, $start + 10));
		}
		
		return $results;
	}
}
