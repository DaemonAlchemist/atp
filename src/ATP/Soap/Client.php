<?php

namespace ATP\Soap;

class Client
{
	private $_url = "";
	private $_ns = "";
	private $_headers = array();
	
	public function __construct($url, $namespace)
	{
		$this->_url = $url;
		$this->_ns = $namespace;
	}
	
	public function __setHeaders($headers)
	{
		$this->_headers = is_array($headers) ? $headers : array($headers);
	}
	
	public function __call($func, $params)
	{
		//Assemble the soap request
		$xml = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?>";
		$xml .= "<soap:Envelope";
		$xml .= " xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"";
		$xml .= " xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\"";
		$xml .= " xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\">";
		$xml .= "<soap:Header>";
		foreach($this->_headers as $header)
		{
			$xml .= $header;
		}
		$xml .= "</soap:Header>";
		$xml .= "<soap:Body>";
		$xml .= "<{$func} xmlns=\"{$this->_ns}\">";
		
		if(isset($params[0]))
		{
			foreach($params[0] as $name => $value)
			{
				$xml .= "<{$name}>{$value}</{$name}>";
			}
		}
		
		$xml .= "</{$func}>";
		$xml .= "</soap:Body>";
		$xml .= "</soap:Envelope>";
		
		//Set the HTTP headers
		$headers = array(
			'Content-Type: text/xml; charset="utf-8"',
			'Content-Length: ' . strlen($xml),
			"SOAPAction: \"{$this->_ns}/{$func}\"",
		);

		//Make the request
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_URL, $this->_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$response = curl_exec($ch); 
		curl_close($ch);
		
		//Return the result
		return $response;
	}
}
