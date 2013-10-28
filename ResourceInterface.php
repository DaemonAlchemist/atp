<?php

namespace ATP;

interface ResourceInterface
{
	public function get($data);
	public function put($data);
	public function post($data);
	public function delete();
}
