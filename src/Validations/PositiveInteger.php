<?php

namespace TalentlmsIntegration\Validations;

class PositiveInteger extends Rule{
	public function validate(): void{
		if($this->value <= 0 || !filter_var($this->value, FILTER_VALIDATE_INT)){
			throw new \InvalidArgumentException('Value is not an integer');
		}
	}
}