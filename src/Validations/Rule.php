<?php

namespace TalentlmsIntegration\Validations;

abstract class Rule{

	protected $value;

	public function __construct($value){
		if(empty($value)){
			throw new \InvalidArgumentException('Value cannot be null');
		}

		$this->value = $value;
		$this->validate();
	}
	protected function validate(): void{
		throw new \InvalidArgumentException("Value is not valid");
	}

	public function getValue(){
		return $this->value;
	}
}